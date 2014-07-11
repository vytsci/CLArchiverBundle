<?php

namespace CL\Bundle\ArchiverBundle\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

class RegisterArchivableEntitiesPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container)
    {
        $definition = $container->getDefinition('cl_archiver.archiver.entity');

        foreach ($container->findTaggedServiceIds('cl_archiver.archivable_entity') as $id => $archivableEntities) {
            foreach ($archivableEntities as $archivableEntity) {
                if (!isset($archivableEntity['archived_entity'])) {
                    throw new \InvalidArgumentException(sprintf('Service "%s" must define the "archived_entity" attribute on "cl_archiver.archivable_entity" tags.', $id));
                }

                $definition->addMethodCall('addArchivable', array(new Reference($id), $archivableEntity['archived_entity']));
            }
        }
    }
}
