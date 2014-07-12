<?php

namespace CL\Bundle\ArchiverBundle;

use CL\Bundle\ArchiverBundle\DependencyInjection\Compiler\RegisterArchivableEntitiesPass;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;

/**
 * A Symfony bundle that allows you to (un)archive your files and entities in various formats.
 */
class CLArchiverBundle extends Bundle
{
    /**
     * @param ContainerBuilder $container
     */
    public function build(ContainerBuilder $container)
    {
        $container->addCompilerPass(new RegisterArchivableEntitiesPass());
    }
}
