<?php

namespace CL\Bundle\ArchiverBundle\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

/**
 * Compiler pass that adds archivable entities to the EntityArchiver
 */
class RegisterArchivableEntitiesPass implements CompilerPassInterface
{
    /**
     * {@inheritdoc}
     *
     * Adds archivable entities to the EntityArchiver
     *
     * @throws \InvalidArgumentException
     */
    public function process(ContainerBuilder $container)
    {
        $definition = $container->getDefinition('cl_archiver.archiver.entity');
        $tag        = 'cl_archiver.archivable_entity';

        foreach ($container->findTaggedServiceIds($tag) as $id => $archivableEntities) {
            foreach ($archivableEntities as $archivableEntity) {
                if (!isset($archivableEntity['archived_entity'])) {
                    throw new \InvalidArgumentException(sprintf('Service "%s" must define the "archived_entity" attribute on "%s" tags.', $id, $tag));
                }

                $definition->addMethodCall('addArchivable', array(new Reference($id), $archivableEntity['archived_entity']));
            }
        }
    }
}
