<?php

require_once DIR_SYSTEM . 'library/mundipagg/vendor/autoload.php';

use Mundipagg\Helper\OpencartOrderInfo;
use Mundipagg\Helper\OpencartSystemInfo;
use Mundipagg\Integrity\IntegrityController;
use Mundipagg\Integrity\IntegrityException;

class ControllerExtensionPaymentMundipaggMaintenance extends Controller
{

    public function version()
    {
        try {
            $this->getIntegrityController()->renderSystemInfo();
        } catch (IntegrityException $e) {
            $this->response->addHeader($e->getHeader());
            return $this->response->setOutput($e->getMessage());
        }
    }

    public function logs()
    {
        try {
            $this->getIntegrityController()->renderLogInfo();
        } catch (IntegrityException $e) {
            $this->response->addHeader($e->getHeader());
            return $this->response->setOutput($e->getMessage());
        }
    }

    public function downloadLog()
    {
        try {
            $this->getIntegrityController()->downloadLogFile();
        } catch (IntegrityException $e) {
            $this->response->addHeader($e->getHeader());
            return $this->response->setOutput($e->getMessage());
        }
    }

    public function order()
    {
        try{
            $this->getIntegrityController()->renderOrderInfo();
        }catch (IntegrityException $e) {
            $this->response->addHeader($e->getHeader());
            return $this->response->setOutput($e->getMessage());
        }
    }


    protected function getIntegrityController()
    {
        return new IntegrityController(
            new OpencartSystemInfo($this),
            new OpencartOrderInfo($this)
        );
    }
}