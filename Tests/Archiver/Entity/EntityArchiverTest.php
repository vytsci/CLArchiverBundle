<?php

namespace CL\Bundle\ArchiverBundle\Tests\Archiver\Entity;

use CL\Bundle\ArchiverBundle\Archiver\Entity\EntityArchiver;

/**
 * Tests the EntityArchiver class
 */
class EntityArchiverTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var EntityArchiver
     */
    protected $archiver;

    public function setUp()
    {
        // First, mock the object to be used in the test
        $archivable = $this->getMock('\CL\Bundle\ArchiverBundle\Tests\Entity\ArchivableEntityExample');
        $archivable->expects($this->any())->method('getId')->will($this->returnValue(101));

        // Now, mock the repository so it returns the mock of the archivable entity
        $archivableRepository = $this->getMockBuilder('\Doctrine\ORM\EntityRepository')->disableOriginalConstructor()->getMock();
        $archivableRepository
            ->expects($this->any())
            ->method('find')
            ->will($this->returnValue($archivable));

        $archivableMetadata = $this->getMockBuilder('\Doctrine\ORM\Mapping\ClassMetadata')->disableOriginalConstructor()->getMock();
        $archivableMetadata
            ->expects($this->any())
            ->method('getFieldNames')
            ->will($this->returnValue(['id', 'title']));

        // Last, mock the EntityManager to return the mock of the repository
        $entityManager = $this->getMockBuilder('\Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->getMock();
        $entityManager
            ->expects($this->any())
            ->method('getRepository')
            ->will($this->returnValue($archivableRepository));
        $entityManager
            ->expects($this->any())
            ->method('getClassMetadata')
            ->will($this->returnValue($archivableMetadata));

        $managerRegistry = $this->getMockBuilder('\Doctrine\Persistance\ManagerRegistry')
            ->disableOriginalConstructor()
            ->getMock();
        $managerRegistry
            ->expects($this->any())
            ->method('getManager')
            ->will($this->returnValue($entityManager));

        $this->archiver = new EntityArchiver($managerRegistry);
    }

    public function testAddArchivable()
    {
        $this->addArchivableForTests();
    }

    public function testArchive()
    {
        $this->addArchivableForTests();
        $extractable = $this->archiver->archive($this->getArchivableEntity());
        $this->assertInstanceOf('CL\Bundle\ArchiverBundle\Archiver\Entity\ExtractableEntityInterface', $extractable);
    }

    public function testExtract()
    {
        $this->addArchivableForTests();
        $expectedArchivable = $this->getArchivableEntity();
        $extractable        = $this->getExtractableEntity();
        $actualArchivable   = $this->archiver->extract($extractable);
        $this->assertEquals($expectedArchivable, $actualArchivable);
    }

    public function testArchiveAndExtract()
    {
        $this->addArchivableForTests();
        $archivable        = $this->getArchivableEntity();
        $actualExtractable = $this->archiver->archive($archivable);
        $actualArchivable  = $this->archiver->extract($actualExtractable);
        $this->assertEquals($archivable, $actualArchivable);
    }

    /**
     * @expectedException \InvalidArgumentException
     */
    public function testArchiveWithoutArchivables()
    {
        $this->archiver->archive($this->getArchivableEntity());
    }

    /**
     * @expectedException \InvalidArgumentException
     */
    public function testExtractWithoutExtractables()
    {
        $this->archiver->extract($this->getExtractableEntity());
    }

    protected function addArchivableForTests()
    {
        $this->archiver->addArchivable($this->getArchivableEntity(), get_class($this->getExtractableEntity()));
    }

    protected function getArchivableEntity()
    {
        return new ArchivableEntityExample();
    }

    protected function getExtractableEntity()
    {
        return new ExtractableEntityExample();
    }
}
