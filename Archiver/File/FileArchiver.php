<?php

namespace CL\Bundle\ArchiverBundle\Archiver\File;

class FileArchiver
{
    /**
     * Archives files with the given path
     * If the path is a directory, the entire directory will be archived
     *
     * @param string $path            The path to the file(s) that need to be archived
     * @param string $destinationPath The path to the archive
     * @param string $format          The format to archive the given files with
     * @param bool   $removeFiles     Whether the original files need to be removed after successful archival
     *
     * @return bool True if archival was successful, false otherwise
     */
    public function archive($path, $destinationPath, $format = 'zip', $removeFiles = false)
    {
        // @TODO implement
    }

    /**
     * @param string $pathToArchive The location of the archive that need to be extracted
     * @param string $destination   The destination of the files after extraction
     * @param bool   $removeArchive Whether the archive needs to be removed after successful extraction
     *
     * @return bool True if extraction was successful, false otherwise
     */
    public function unarchive($pathToArchive, $destination, $removeArchive = false)
    {
        // @TODO implement
    }
}
