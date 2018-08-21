<?php

namespace Mundipagg\Integrity;

class IntegrityController
{
    /** @var SystemInfoInterface */
    protected $systemInfo;

    /** @var OrderInfoInterface  */
    protected $orderInfo;

    /** @var IntegrityViewer */
    public $viewer;

    public function __construct(SystemInfoInterface $systemInfo, OrderInfoInterface $orderInfo)
    {
        $this->systemInfo = $systemInfo;
        $this->orderInfo = $orderInfo;

        $this->viewer = $this->getIntegrityViewer();
    }

    public function __call($name, $arguments)
    {
        if (!$this->checkMaintenanceRouteAccessPermition()) {
            throw new IntegrityException('HTTP/1.0 401 Unauthorized', 'Unauthorized', 401);
        }

        if (!method_exists($this, $name)) {
            throw new IntegrityException('HTTP/1.0 404 Not Found', 'Method not found', 404);
        }

        return call_user_func_array([$this, $name], $arguments);
    }

    protected function renderOrderInfo()
    {
        $orderId = $this->systemInfo->getRequestParam('orderID');
        if (empty($orderId)) {
            throw new IntegrityException('HTTP/1.0 404 Not Found', 'Resource not found', 404);
        }
        $this->orderInfo->loadOrder($orderId);

        $this->viewer->handleDefaultInfoView("#Order", $this->orderInfo->getOrder());
        $this->viewer->handleDefaultInfoView("#History", $this->orderInfo->getOrderHistory());
        $this->viewer->handleDefaultInfoView("#Charges", $this->orderInfo->getOrderCharges());
        $this->viewer->handleDefaultInfoView("#Invoices", $this->orderInfo->getOrderInvoices());
    }

    protected function renderLogInfo()
    {
        $params = $this->systemInfo->getRequestParams();

        $this->viewer->handleLogListView(
            $this->listLogFiles(),
            $this->systemInfo->getDownloadRouter(),
            $params
        );
    }

    protected function renderSystemInfo()
    {
        $integrityCheck = $this->getIntegrityCheck();

        $this->viewer->handleDefaultInfoView(
            "Module info",
            $this->getSystemInformation()
        );
        $this->viewer->handleNonEmptyInfoView(
            "Warning! New files were added to module directories!",
            $integrityCheck['newFiles']
        );

        $this->viewer->handleNonEmptyInfoView(
            "Warning! Module files were modified!",
            $integrityCheck['alteredFiles']
        );

        $this->viewer->handleNonEmptyInfoView(
            "Warning! Module files become unreadable!",
            $integrityCheck['unreadableFiles']
        );

        $this->viewer->handleDefaultInfoView(
            'File List ('.count($integrityCheck['files']).')',
            $integrityCheck['files']
        );

        echo '<h3>phpinfo()</h3>';
        phpinfo();
    }

    protected function downloadLogFile()
    {
        $file = $this->systemInfo->getRequestParam('file');
        if (!$file) {
            throw new IntegrityException('HTTP/1.0 404 Not Found', 'Resource not found', 404);
        }

        $file = base64_decode($file);

        if (!is_readable($file) || !in_array($file, $this->listLogFiles())) {
            throw new IntegrityException('HTTP/1.0 403 Forbidden', 'Forbidden', 403);
        }

        if (!$this->compactFile($file)) {
            throw new IntegrityException(
                'HTTP/1.0 500 Internal Server Error',
                'Zip encoding failure',
                500);
        }
    }

    public function getSystemInformation()
    {
        $generalInformation =  [
            'integrityScript' => __FILE__,
            'modmanFilePath' => $this->systemInfo->getModmanPath(),
            'integrityCheckFile' => $this->systemInfo->getIntegrityCheckPath(),
            'phpVersion' => phpversion(),
            'moduleVersion' => $this->systemInfo->getModuleVersion(),
            'platformVersion' => $this->systemInfo->getPlatformVersion(),
            'platformRootDir' => $this->systemInfo->getPlatformRootDir(),
            'directoriesIgnored' => $this->systemInfo->getDirectoriesIgnored(),
            'installType' => $this->systemInfo->getInstallType(),
        ];

        $generalInformation['moduleCheckSum'] = md5(json_encode($this->listLogFiles()));
        $generalInformation = array_merge($generalInformation, $this->getLogInfo());

        return $generalInformation;
    }

    public function getIntegrityCheck()
    {
        $integrityEngine = new IntegrityEngine();

        return $integrityEngine->verifyIntegrity(
            $this->systemInfo->getModmanPath(),
            $this->systemInfo->getIntegrityCheckPath(),
            $this->systemInfo->getDirectoriesIgnored()
        );
    }

    public function getLogInfo()
    {
        return [
            'logConfigs' => $this->getLogConfigs(),
            'magentoLogsDirectory' => $this->systemInfo->getDefaultLogDir(),
            'moduleLogsDirectory' => $this->systemInfo->getModuleLogDir()
        ];
    }

    public function getLogConfigs()
    {
       return [
           'includes' => $this->systemInfo->getDefaultLogFiles(),
           'moduleFilenamePrefix' => $this->systemInfo->getModuleLogFilenamePrefix()
       ];
    }

    public function listLogFiles()
    {
        $integrityEngine = new IntegrityEngine();
        $listLogFiles = [];
        foreach (array_unique($this->systemInfo->getLogsDirs()) as $dir) {
            $listLogFiles = array_merge($listLogFiles, $integrityEngine->listFilesOnDir($dir));
        }
        return $listLogFiles;
    }

    public function compactFile($file)
    {
        $compactor = new FileCompactor($file);
        return $compactor->compact();
    }

    public function checkMaintenanceRouteAccessPermition()
    {
        $secretKey = $this->systemInfo->getSecretKey();
        $secretKeyHashEncoded = base64_encode(hash('sha512',$secretKey));

        $urlParams = $this->systemInfo->getRequestParams();
        if (!isset($urlParams['token'])) {
            return false;
        }

        return $urlParams['token'] === $secretKeyHashEncoded && strlen($secretKey) > 1;
    }

    protected function getIntegrityViewer()
    {
        return new IntegrityViewer();
    }
}
