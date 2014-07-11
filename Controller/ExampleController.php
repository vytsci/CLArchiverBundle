<?php

namespace CL\Bundle\ArchiverBundle\Controller;

use CL\Bundle\ArchiverBundle\Entity\ArchivableEntityExample;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;

class ExampleController extends Controller
{
    public function archiveAction()
    {
        $archiver         = $this->get('cl_archiver.archiver.entity');
        $archivableEntity = new ArchivableEntityExample();
        $archivableEntity->setName('test');
        $archivableEntity->setTitle('Dit is een test');
        $this->get('doctrine.orm.entity_manager')->persist($archivableEntity);
        $this->get('doctrine.orm.entity_manager')->flush($archivableEntity);
        $archivedEntity   = $archiver->archive($archivableEntity);

        $unarchivedEntity = $archiver->unarchive($archivedEntity);

        if ($archivableEntity !== $unarchivedEntity) {
            var_dump($archivableEntity);
            var_dump($unarchivedEntity);
        }

        var_dump($archivedEntity);
        exit;
    }
}
