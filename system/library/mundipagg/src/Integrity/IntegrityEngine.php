<?php

namespace Mundipagg\Integrity;

class IntegrityEngine
{
    const MODMAN_CHECK = 'modman';
    const INTEGRITY_CHECK = 'integrityCheck';

    private function hasPermissions($file)
    {
        if (!file_exists($file)) {
            echo "<pre>File <strong>'$file'</strong> does not exists!</pre>\n";
            return false;
        }

        if (!is_readable($file)) {
            echo "<pre>File <strong>'$file'</strong> is not readable!</pre>\n";
            return false;
        }
        return true;
    }

    public function generateMD5FromArray($data)
    {
        $md5Files = [];
        $filesError = [];
        foreach ($data as $file) {
            if(!$this->hasPermissions($file)) {
                $filesError[] = $file;
                continue;
            }
            $md5Files[$file] = md5_file($file);
        }

        return $md5Files;
    }

    public function generateFilesPath($dir, $ignored)
    {
        if(
            $this->isDirectoriesIgnored($ignored, $dir) ||
            !$this->hasPermissions($dir)
        ) {
            return [];
        }

        if (!is_dir($dir)) {
            return  [
                $dir => file_exists($dir)
            ];
        }

        $files = scandir($dir);
        $md5 = [];
        foreach ($files as $file) {
            if ($file !== '.' && $file !== '..' && !$this->isDirectoriesIgnored($ignored, $file) ) {
                $file = $dir . DIRECTORY_SEPARATOR . $file;
                $md5[$file] = $this->generateFilesPath($file, $ignored);
            }
        }

        return $md5;
    }

    public function listFilesOnDir($dir, $ignored = []) {
        $rawLines = is_array($dir) ? $dir : [$dir];

        $md5s = [];
        foreach ($rawLines as $line) {
            $md5s = array_merge($md5s, $this->filterFilename(
                $this->generateFilesPath($line, $ignored)
            ));
        }

        return $md5s;
    }

    public function filterFilename($checkSumArray)
    {
        if (count($checkSumArray) === 1) {
            return [key($checkSumArray)];
        }
        $data = serialize($checkSumArray);
        $data = explode('";b:1;}', $data);

        $files = [];
        foreach ($data as $index => $line) {
            $raw = explode('"', $line);
            if (count($raw) > 1 && is_file(end($raw))) {
                $files[] = end($raw);
            }
        }

        return $files;
    }

    public function getFileList($modmanFilePath, $integrityCheckFilePath, $ignoredDir = [])
    {
        if ($this->hasPermissions($modmanFilePath)) {
            $modmanRawData = file_get_contents($modmanFilePath);
            $rawLines = explode("\n", $modmanRawData);

            return $this->getFileListFromArrayData($rawLines, $ignoredDir, self::MODMAN_CHECK);
        }

        if ($this->hasPermissions($integrityCheckFilePath)) {
            $integrityCheckRawData = file_get_contents($integrityCheckFilePath);
            $data = json_decode($integrityCheckRawData, true);
            $rawLines = array_keys($data);

            return $this->getFileListFromArrayData($rawLines, $ignoredDir, self::INTEGRITY_CHECK);
        }

        return [];
    }

    public function getFileListFromArrayData($array, $ignoredDir = [], $fileOrigin = self::MODMAN_CHECK)
    {
        $list = [];
        foreach ($array as $rawLine) {
            if (
                substr($rawLine,0,1) !== '#' &&
                strlen($rawLine) > 0
            ) {

                $line = $rawLine;
                if ($fileOrigin == self::MODMAN_CHECK) {
                    $line = array_values(array_filter(explode(' ', $rawLine)));
                    if ($line[0] == 'modman'){
                        continue;
                    }
                    $line = './' . $line[1];
                }

                $list = array_merge($list, $this->listFilesOnDir($line, $ignoredDir));
            }
        }
        return $list;
    }

    public function isDirectoriesIgnored(array $directories, $line)
    {

        return in_array($line, $directories);
    }

    public function verifyIntegrity($modmanFilePath, $integrityCheckFilePath, $ignoreList = [])
    {
        $newFiles = [];
        $unreadableFiles = [];
        $alteredFiles = [];

        $integrityData = json_decode(file_get_contents($integrityCheckFilePath),true);

        $listFiles = $this->getFileList($modmanFilePath, $integrityCheckFilePath, $ignoreList);
        $files = $this->generateMD5FromArray($listFiles);

        foreach ($ignoreList as $filePath) {
            unset($files[$filePath]);
        }

        //validating files
        foreach ($files as $fileName => $md5) {
            if (substr($fileName, -strlen('integrityCheck')) == 'integrityCheck') {
                //skip validation of integrityCheck file
                continue;
            }
            if ($md5 === false) {
                $unreadableFiles[] = $fileName;
                continue;
            }
            if(isset($integrityData[$fileName])) {
                if ($md5 != $integrityData[$fileName]) {
                    $alteredFiles[] = $fileName;
                }
                continue;
            }
            $newFiles[$fileName] = $md5;
        }

        return [
            'files' => $files,
            'newFiles' => $newFiles,
            'unreadableFiles' => $unreadableFiles,
            'alteredFiles' => $alteredFiles
        ];
    }
}
