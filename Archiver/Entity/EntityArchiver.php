<?php

namespace CL\Bundle\ArchiverBundle\Archiver\Entity;

use Doctrine\Common\Collections\Collection;
use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\Common\Util\ClassUtils;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Mapping\MappingException;
use Symfony\Component\PropertyAccess\PropertyAccess;

/**
 * Class that handles the archiving and unarchiving of entities
 */
class EntityArchiver
{
    /**
     * @var EntityManagerInterface
     */
    protected $manager;

    /**
     * @var array
     */
    protected $entityArchived = array();

    /**
     * @var array
     */
    protected $entitiesInArchive = array();

    /**
     * @param EntityManagerInterface $manager
     */
    public function __construct(EntityManagerInterface $manager)
    {
        $this->manager = $manager;
    }

    /**
     * @param $entity
     * @return bool
     */
    public function isArchivable($entity)
    {
        if (is_object($entity)) {
            $entity = ClassUtils::getClass($entity);
        }

        return array_key_exists($entity, $this->entityArchived);
    }

    /**
     * @param $entity
     * @param $identifier
     * @return bool
     */
    public function isArchived($entity, $identifier)
    {
        if (is_object($entity)) {
            $entity = ClassUtils::getClass($entity);
        }

        return
            isset($this->entitiesInArchive[$entity])
            && in_array($identifier, $this->entitiesInArchive[$entity]);
    }

    /**
     * @param ArchivableEntityInterface $entity The entity that is archivable
     * @param string $archivedEntity Classname of the entity that will be used to archive the entity
     *                                                  (must implement ExtractableEntityInterface)
     *
     * @throws \InvalidArgumentException
     */
    public function addArchivable(ArchivableEntityInterface $entity, $archivedEntity)
    {
        $implements = class_implements($entity);
        $shouldImplement = 'CL\Bundle\ArchiverBundle\Archiver\Entity\ArchivableEntityInterface';
        if (!in_array($shouldImplement, $implements)) {
            throw new \InvalidArgumentException(sprintf('Archivable entity should implement %s', $shouldImplement));
        }

        $implements = class_implements($archivedEntity);
        $shouldImplement = 'CL\Bundle\ArchiverBundle\Archiver\Entity\ExtractableEntityInterface';
        if (!in_array($shouldImplement, $implements)) {
            throw new \InvalidArgumentException(sprintf('Archived entity should implement %s', $shouldImplement));
        }

        $this->entityArchived[get_class($entity)] = $archivedEntity;
    }

    /**
     * @param ArchivableEntityInterface $entity
     * @param bool $flush
     * @param bool $removeOriginal
     * @return bool|ExtractableEntityInterface
     * @throws \Doctrine\ORM\Mapping\MappingException
     */
    public function archive(ArchivableEntityInterface $entity, $flush = true, $removeOriginal = false)
    {
        if (empty($entity) || !is_object($entity)) {
            return false;
        }

        $accessor = PropertyAccess::createPropertyAccessor();

        $em = $this->getEntityManager();
        $data = [];
        $class = get_class($entity);
        $identifier = 'id'; //@todo: make this the proper way
        $metadata = $em->getClassMetadata($class);

        $this->entitiesInArchive[$class][] = $accessor->getValue($entity, $identifier);

        foreach ($metadata->getFieldNames() as $field) {
            if (!in_array($field, [$identifier])) {
                $data[$field] = $accessor->getValue($entity, $field);
            }
        }
        foreach ($metadata->getAssociationNames() as $associationName) {
            $associationMapping = $metadata->getAssociationMapping($associationName);
            $associationValue = $accessor->getValue($entity, $associationName);

            if (
                $associationMapping['isOwningSide']
                || $associationMapping['sourceEntity'] === $class
            ) {
                if (
                    $associationValue instanceof Collection
                    && $this->isArchivable($associationMapping['targetEntity'])
                ) {
                    foreach ($associationValue as $associationValueItem) {
                        $this->archive($associationValueItem);
                    }

                    continue;
                }

                if (
                    is_object($associationValue)
                    && isset($associationMapping['joinColumns'])
                ) {
                    if (
                        $this->isArchivable($associationMapping['targetEntity'])
                        && !$this->isArchived($associationValue, $accessor->getValue($associationValue, $identifier))
                    ) {
                        $this->archive($associationValue);
                    }

                    foreach ($associationMapping['joinColumns'] as $joinColumn) {
                        try {
                            $data[$associationName] = $em
                                ->getClassMetadata(get_class($associationValue))
                                ->getFieldValue($associationValue, $joinColumn['referencedColumnName'])
                            ;
                        } catch (MappingException $exception) {
                            // Object is not an entity, we can log this or just ignore
                        }
                    }
                }
            }
        }

        $id = $entity->getId();
        if (empty($id)) {
            throw new \InvalidArgumentException(
                'Cannot archive an entity without an ID, are you sure it has been persisted already?'
            );
        }

        $archivedEntity = $this->createExtractableEntity($entity);
        $archivedEntity->setOriginalId($entity->getId());
        $archivedEntity->setData($data);

        $em->persist($archivedEntity);

        if ($removeOriginal === true) {
            $em->remove($entity);
        }
        if ($flush === true) {
            $em->flush();
        }

        return $archivedEntity;
    }

    /**
     * @param ExtractableEntityInterface $archivedEntity The entity that was used to archive another entity.
     * @param bool $create Whether a new entity should be constructed if the
     *                                                   original entity no longer exists.
     * @param bool $removeArchive Whether the archived entity should be removed after extraction.
     *
     * @return ExtractableEntityInterface The (original) unarchived entity.
     *
     * @throws \LogicException If the original entity could not be found and $create is false
     */
    public function extract(ExtractableEntityInterface $archivedEntity, $create = true, $removeArchive = true)
    {
        $em = $this->getEntityManager();
        $originalEntity = $this->findOriginalEntity($archivedEntity);
        if ($originalEntity === null) {
            if ($create !== true) {
                throw new \LogicException(
                    'Could not find an existing entity that matches the archived entity\'s original ID
                    (and $create is false)'
                );
            }
            $originalEntity = $this->createOriginalEntity($archivedEntity);
            $em->persist($originalEntity);
        }
        $data = $archivedEntity->getData();
        $metadata = $em->getClassMetadata(get_class($originalEntity));
        foreach ($data as $field => $value) {
            $metadata->setFieldValue($originalEntity, $field, $value);
        }
        //@todo: we need to extract associations here
        $em->flush($originalEntity);
        if ($removeArchive === true) {
            $em->remove($archivedEntity);
        }

        return $originalEntity;
    }

    /**
     * @param ExtractableEntityInterface $archivedEntity Archived entity from which
     *                                                   the original entity should be determined
     *
     * @return ArchivableEntityInterface|null The existing original entity object,
     *                                        or null if it could not be found
     *
     * @throws \InvalidArgumentException
     */
    protected function findOriginalEntity(ExtractableEntityInterface $archivedEntity)
    {
        $originalEntityName = array_search(get_class($archivedEntity), $this->entityArchived);

        return $this->getEntityManager()->find($originalEntityName, $archivedEntity->getOriginalId());
    }

    /**
     * @param ExtractableEntityInterface $archivedEntity Archived entity from which
     *                                                   the original entity should be determined
     *
     * @return ArchivableEntityInterface The original entity object
     *
     * @throws \InvalidArgumentException If there is no original entity mapped to the given archived entity
     */
    protected function createOriginalEntity(ExtractableEntityInterface $archivedEntity)
    {
        $archivedEntityClass = get_class($archivedEntity);
        $class = array_search(get_class($archivedEntity), $this->entityArchived);
        if ($class !== false) {
            return new $class();
        }

        throw new \InvalidArgumentException(
            sprintf(
                'There is no original entity set for that archived entity class: %s',
                $archivedEntityClass
            )
        );
    }

    /**
     * @param ArchivableEntityInterface $entity The entity for which the archivable entity should be determined
     *
     * @return ExtractableEntityInterface The archived entity
     *
     * @throws \InvalidArgumentException
     */
    protected function createExtractableEntity($entity)
    {
        $entityClass = ClassUtils::getClass($entity);
        if ($this->isArchivable($entityClass)) {
            $class = $this->entityArchived[$entityClass];

            return new $class();
        }

        throw new \InvalidArgumentException(
            sprintf(
                'There is no archiveable entity set for that entity class: %s',
                $entityClass
            )
        );
    }

    /**
     * @param string $archivedEntityName The (class)name of the entity in an archived state
     *
     * @return string The (class)name of the original entity in an extracted state
     *
     * @throws \InvalidArgumentException When there is no extracted entity mapped to the given archived entity
     */
    protected function getOriginalEntityName($archivedEntityName)
    {
        $originalEntityName = array_search($archivedEntityName, $this->entityArchived);
        if ($originalEntityName === false) {
            throw new \InvalidArgumentException(
                sprintf(
                    'There is no original entity mapped to that archived entity name: %s',
                    $archivedEntityName
                )
            );
        }

        return $originalEntityName;
    }

    /**
     * @return EntityManagerInterface
     */
    protected function getEntityManager()
    {
        return $this->manager;
    }
}
