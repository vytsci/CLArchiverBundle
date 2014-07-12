<?php

namespace CL\Bundle\ArchiverBundle\Tests\Archiver\Entity;

/**
 * Tests the FileArchiver class
 */
class FileArchiverTest extends \PHPUnit_Framework_TestCase
{
    public function testArchive()
    {
        $this->markTestSkipped(
            'Not sure if and what we should test here, Zippy does all of the work and has it\'s own tests
            for each kind of format, which on their turn depend on the correct binaries to be available'
        );
    }

    public function testExtract()
    {
        $this->markTestSkipped('Extraction');
    }
}
