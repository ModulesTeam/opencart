<?php

namespace Mundipagg\Helper;

use Mundipagg\Integrity\SystemInfoInterface;
use Mundipagg\Settings\General;

class OpencartSystemInfo implements SystemInfoInterface
{
    private $openCart;

    public function __construct($openCart)
    {
        $this->openCart = $openCart;
    }

    public function getModuleVersion()
    {
        $generalConfig = new General($this->openCart);
        return $generalConfig->getModuleVersion();
    }

    public function getPlatformVersion()
    {
        return VERSION;
    }

    public function getPlatformRootDir()
    {
        return DIR_SYSTEM;
    }

    public function getDirectoriesIgnored()
    {
        return [
            './system/library/mundipagg//vendor'
        ];
    }

    public function getModmanPath()
    {
        return DIR_SYSTEM . 'library/mundipagg/src/Integrity/modman';
    }

    public function getIntegrityCheckPath()
    {
        return DIR_SYSTEM . 'library/mundipagg/src/Integrity/integrityCheck';
    }

    public function getInstallType()
    {
        $installType = 'package';
        if (is_dir('./.modman')) {
            $installType = 'modman';
        }

        return $installType;
    }

    public function getLogsDirs()
    {
        return [ DIR_LOGS ];
    }

    public function getDefaultLogDir()
    {
        return [ DIR_LOGS ];
    }

    public function getModuleLogDir()
    {
        return [ DIR_LOGS ];
    }

    public function getDefaultLogFiles()
    {
        return [
            DIR_LOGS . '/error'
        ];
    }

    public function getModuleLogFilenamePrefix()
    {
        return 'Mundipagg_opencart_';
    }

    public function getSecretKey()
    {
        $config = new General($this->openCart);
        return $config->getSecretKey();
    }

    public function getRequestParams()
    {
        return $this->openCart->request->get;
    }

    public function getRequestParam($param)
    {
        if (!empty($this->openCart->request->get[$param])) {
            return $this->openCart->request->get[$param];
        }
        return null;
    }

    public function getDownloadRouter()
    {
        return 'index.php?route=extension/payment/mundipagg_maintenance/downloadLog&';
    }
}