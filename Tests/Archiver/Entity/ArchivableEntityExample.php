<?php

namespace CL\Bundle\ArchiverBundle\Tests\Archiver\Entity;

use CL\Bundle\ArchiverBundle\Archiver\Entity\ArchivableEntityInterface;

class ArchivableEntityExample implements ArchivableEntityInterface
{
    /**
     * {@inheritdoc}
     */
    public function getId()
    {
        return 101;
    }
}
