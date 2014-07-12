<?php

namespace CL\Bundle\ArchiverBundle\Tests\Archiver\Entity;

use CL\Bundle\ArchiverBundle\Archiver\File\FileArchiver;

/**
 * Tests the FileArchiver class
 */
class FileArchiverTest extends \PHPUnit_Framework_TestCase
{
    public function testArchive()
    {
        $archiver = $this->getArchiverMock();
        $archiver->archive('mydir', 'mydestination.myformat');
        $this->markTestIncomplete('Test actual file creation?');
    }

    public function testExtract()
    {
        $archiver = $this->getArchiverMock();
        $archiver->extract('myfile', 'mydestination');
        $this->markTestIncomplete('Test actual extraction?');
    }

    /**
     * @return FileArchiver|\PHPUnit_Framework_MockObject_MockObject
     */
    protected function getArchiverMock()
    {
        return $this->getMockBuilder('\CL\Bundle\ArchiverBundle\Archiver\File\FileArchiver')
            ->disableOriginalConstructor()
            ->getMock();
    }
}
