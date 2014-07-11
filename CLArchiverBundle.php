<?php

namespace CL\Bundle\ArchiverBundle;

use CL\Bundle\ArchiverBundle\DependencyInjection\Compiler\RegisterArchivableEntitiesPass;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;

class CLArchiverBundle extends Bundle
{
    public function build(ContainerBuilder $container)
    {
        $container->addCompilerPass(new RegisterArchivableEntitiesPass());
    }
}
