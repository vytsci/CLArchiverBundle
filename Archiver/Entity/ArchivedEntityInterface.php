<?php

namespace CL\Bundle\ArchiverBundle\Archiver\Entity;

/**
 * Interface ArchivedEntityInterface
 */
interface ArchivedEntityInterface
{
    /**
     * @param int $originalId
     */
    public function setOriginalId($originalId);

    /**
     * @return int
     */
    public function getOriginalId();

    /**
     * @param array $data
     */
    public function setData(array $data);

    /**
     * @return array
     */
    public function getData();
}
