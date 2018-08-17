<?php

require_once DIR_SYSTEM . 'library/mundipagg/vendor/autoload.php';

use Mundipagg\Helper\OpencartOrderInfo;
use Mundipagg\Helper\OpencartSystemInfo;
use Mundipagg\Integrity\IntegrityController;

class ControllerExtensionPaymentMundipaggMaintenance extends Controller
{

    public function index()
    {
        try {
            $this->getIntegrityController()->renderSystemInfo();
        } catch (\Mundipagg\Integrity\IntegrityExceptioneption $e) {
            $this->getResponse()
                ->setBody($e->getMessage())
                ->setHeader($e->getHeader(), $e->getCode(), true);
            return;
        }
    }

    public function logs()
    {
        try {
            $this->getIntegrityController()->renderLogInfo();
        } catch (\Mundipagg\Integrity\IntegrityExceptioneption $e) {
            $this->getResponse()
                ->setBody($e->getMessage())
                ->setHeader($e->getHeader(), $e->getCode(), true);
            return;
        }
    }

    public function downloadLog()
    {
        try {
            $this->getIntegrityController()->downloadLogFile();
        } catch (IntegrityException $e) {
            $this->getResponse()
                ->setBody($e->getMessage())
                ->setHeader($e->getHeader(), $e->getCode(), true);
            return;
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