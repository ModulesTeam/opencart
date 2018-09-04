<?php

namespace Mundipagg\Integrity;

interface SystemInfoInterface
{
    public function getModuleVersion();

    public function getPlatformVersion();

    public function getPlatformRootDir();

    public function getDirectoriesIgnored();

    public function getModmanPath();

    public function getIntegrityCheckPath();

    public function getInstallType();

    public function getLogsDirs();

    public function getDefaultLogDir();

    public function getModuleLogDir();

    public function getDefaultLogFiles();

    public function getModuleLogFilenamePrefix();

    public function getSecretKey();

    public function getRequestParams();

    public function getRequestParam($param);

    public function getDownloadRouter();
}
