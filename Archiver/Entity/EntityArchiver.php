<?php

namespace CL\Bundle\ArchiverBundle\Archiver\Entity;

use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\ORM\EntityManager;

/**
 * Class that handles the archiving and unarchiving of entities
 */
class EntityArchiver
{
    /**
     * @var ManagerRegistry
     */
    protected $managerRegistry;

    /**
     * @var array
     */
    protected $entityArchived = array();

    /**
     * @param ManagerRegistry $managerRegistry
     */
    public function __construct(ManagerRegistry $managerRegistry)
    {
        $this->managerRegistry = $managerRegistry;
    }

    /**
     * @param ArchivableEntityInterface $entity         The entity that is archivable
     * @param string                    $archivedEntity Classname of the entity that will be used to archive the entity
     *                                                  (must implement ExtractableEntityInterface)
     *
     * @throws \InvalidArgumentException
     */
    public function addArchivable(ArchivableEntityInterface $entity, $archivedEntity)
    {
        $implements      = class_implements($entity);
        $shouldImplement = 'CL\Bundle\ArchiverBundle\Archiver\Entity\ArchivableEntityInterface';
        if (!in_array($shouldImplement, $implements)) {
            throw new \InvalidArgumentException(sprintf('Archivable entity should implement %s', $shouldImplement));
        }

        $implements      = class_implements($archivedEntity);
        $shouldImplement = 'CL\Bundle\ArchiverBundle\Archiver\Entity\ExtractableEntityInterface';
        if (!in_array($shouldImplement, $implements)) {
            throw new \InvalidArgumentException(sprintf('Archived entity should implement %s', $shouldImplement));
        }

        $this->entityArchived[get_class($entity)] = $archivedEntity;
    }

    /**
     * Archives the given entity by copying it's data into another entity that implements ExtractableEntityInterface
     *
     * @param ArchivableEntityInterface $entity         The entity to be archived
     * @param bool                      $flush          Whether the archived entity should be flushed to
     *                                                  the database automatically
     * @param bool                      $removeOriginal Whether the original entity should be removed after
     *                                                  successful archiving (requires $persistAndFlush to be true)
     *
     * @return ExtractableEntityInterface
     *
     * @throws \InvalidArgumentException If an entity was given that has not been persisted to the database yet
     */
    public function archive(ArchivableEntityInterface $entity, $flush = true, $removeOriginal = false)
    {
        $em       = $this->getEntityManager();
        $data     = [];
        $metadata = $em->getClassMetadata(get_class($entity));
        foreach ($metadata->getFieldNames() as $field) {
            if (!in_array($field, ['id'])) {
                $data[$field] = $metadata->getFieldValue($entity, $field);
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
     * @param bool                       $create         Whether a new entity should be constructed if the
     *                                                   original entity no longer exists.
     * @param bool                       $removeArchive  Whether the archived entity should be removed after extraction.
     *
     * @return ExtractableEntityInterface The (original) unarchived entity.
     *
     * @throws \LogicException If the original entity could not be found and $create is false
     */
    public function extract(ExtractableEntityInterface $archivedEntity, $create = true, $removeArchive = true)
    {
        $em             = $this->getEntityManager();
        $originalEntity = $this->findOriginalEntity($archivedEntity);
        if ($originalEntity === null) {
            if ($create !== true) {
                throw new \LogicException(
                    'Could not find an existing entity that matches the archived ' .
                    'entity\'s original ID (and $create is false)'
                );
            }
            $originalEntity = $this->createOriginalEntity($archivedEntity);
            $em->persist($originalEntity);
        }
        $data     = $archivedEntity->getData();
        $metadata = $em->getClassMetadata(get_class($originalEntity));
        foreach ($data as $field => $value) {
            $metadata->setFieldValue($originalEntity, $field, $value);
        }
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
        $class               = array_search(get_class($archivedEntity), $this->entityArchived);
        if ($class !== false) {
            return new $class();
        }

        throw new \InvalidArgumentException(sprintf(
            'There is no original entity set for that archived entity class: %s',
            $archivedEntityClass
        ));
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
        $entityClass = get_class($entity);
        if (array_key_exists($entityClass, $this->entityArchived)) {
            $class = $this->entityArchived[$entityClass];

            return new $class();
        }

        throw new \InvalidArgumentException(sprintf(
            'There is no archiveable entity set for that entity class: %s',
            $entityClass
        ));
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
            throw new \InvalidArgumentException(sprintf(
                'There is no original entity mapped to that archived entity name: %s',
                $archivedEntityName
            ));
        }

        return $originalEntityName;
    }

    /**
     * @return EntityManager
     */
    protected function getEntityManager()
    {
        return $this->managerRegistry->getManager('entity_manager');
    }
}
