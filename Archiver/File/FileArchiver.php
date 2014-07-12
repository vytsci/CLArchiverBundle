<?php

namespace CL\Bundle\ArchiverBundle\Archiver;

use Alchemy\Zippy\Exception\RuntimeException;
use Alchemy\Zippy\Zippy;

/**
 * Class that handles the archiving and unarchiving of files using the Zippy library.
 */
class FileArchiver
{
    /**
     * @var Zippy
     */
    protected $zippy;

    /**
     * @param Zippy $zippy
     */
    public function __construct(Zippy $zippy)
    {
        $zippy->load();
        $this->zippy = $zippy;
    }

    /**
     * @param string      $path           The path to the file(s) or directory that need to be archived.
     * @param string      $destination    Destination of the archive to create (the extension determines the archive format).
     * @param null|string $rootDir        If you want your files to be archived in a certain subdirectory, name it here.
     * @param bool        $removeOriginal Whether the original file(s) or directory should be removed after successful extraction.
     *
     * @throws \RuntimeException When archiving failed.
     */
    public function archive($path, $destination, $rootDir = null, $removeOriginal = false)
    {
        $files = [];
        if ($rootDir !== null) {
            $files[$rootDir] = $path;
        } else {
            $files[] = $path;
        }

        try {
            $this->zippy->create($destination, $files, true);
        } catch (RuntimeException $e) {
            // abstract away the zippy exception
            throw new \RuntimeException($e->getMessage(), null, $e);
        }

        if ($removeOriginal === true) {
            unlink($removeOriginal);
        }
    }

    /**
     * @param string $path          The path to the archive.
     * @param string $destination   The destination where files will be extracted to.
     * @param bool   $removeArchive Whether the archive itself should be removed after successful extraction.
     *
     * @throws \RuntimeException When extraction failed.
     */
    public function extract($path, $destination, $removeArchive = false)
    {
        try {
            $archive = $this->zippy->open($path);
            $archive->extract($destination);
        } catch (RuntimeException $e) {
            throw new \RuntimeException($e->getMessage(), null, $e);
        }

        if ($removeArchive === true) {
            unlink($path);
        }
    }
}
