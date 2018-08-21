<?php

namespace Mundipagg\Integrity;

class FileCompactor
{
    protected $file;

    public function __construct($file)
    {
        $this->file = $file;
    }

    public function compact()
    {
        $zip = $this->getZipArchive();
        $zipFileName = tempnam(sys_get_temp_dir(), 'MP_');
        $zipSuccess = false;
        $downloadFileName = str_replace(DIRECTORY_SEPARATOR, "_", $this->file);
        if ($zip->open($zipFileName) === TRUE) {
            $zip->addFile($this->file, $downloadFileName);
            $zip->close();
            $zipSuccess = true;
        }

        if ($zipSuccess) {
            header('Content-Type: application/zip');
            header('Content-Disposition: attachment; filename='.$downloadFileName.'.zip');
            header('Pragma: no-cache');
            readfile($zipFileName);
            return true;
        }

        return false;
    }

    public function getZipArchive()
    {
        return new \ZipArchive;
    }
}