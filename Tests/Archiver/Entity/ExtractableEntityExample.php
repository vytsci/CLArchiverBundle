<?php

namespace CL\Bundle\ArchiverBundle\Tests\Archiver\Entity;

use CL\Bundle\ArchiverBundle\Archiver\Entity\ExtractableEntityInterface;

class ExtractableEntityExample implements ExtractableEntityInterface
{
    /**
     * @var int
     */
    protected $originalId;

    /**
     * @var array
     */
    protected $data = [];

    /**
     * {@inheritdoc}
     */
    public function setOriginalId($originalId)
    {
        $this->originalId = $originalId;
    }

    /**
     * {@inheritdoc}
     */
    public function getOriginalId()
    {
        return $this->originalId;
    }

    /**
     * {@inheritdoc}
     */
    public function setData(array $data)
    {
        $this->data = $data;
    }

    /**
     * {@inheritdoc}
     */
    public function getData()
    {
        return $this->data;
    }
}
