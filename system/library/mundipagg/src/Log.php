<?php

namespace Mundipagg;

use Mundipagg\LogMessages;

class Log
{
    private $logFile = null;
    private $headerMessage = '';

    public static function create()
    {
        return new Log;
    }

    /**
     * Warning log messages
     *
     * @param String $message
     * @param String $caller
     * @return $this
     */
    public function warning($message, $caller)
    {
        $this->logIt($this->getHeader('WARNING', $caller) . "$message\n");
        return $this;
    }

    /**
     * Notice log messages
     *
     * @param String $message
     * @param String $caller
     * @return $this
     */
    public function notice($message, $caller)
    {
        $this->logIt($this->getHeader('NOTICE', $caller) . "$message\n");
        return $this;
    }

    /**
     * Error log messages
     *
     * @param String $message
     * @param String $caller
     * @return $this
     */
    public function error($message, $caller)
    {
        $this->logIt($this->getHeader('ERROR', $caller) . "$message\n");
        return $this;
    }

    /**
     * Debug log messages
     *
     * @param String $message
     * @param String $caller
     * @return $this
     */
    public function debug($message, $caller)
    {
        $this->logIt($this->getHeader('DEBUG', $caller) . "$message\n");
        return $this;
    }

    /**
     * Warning log messages
     *
     * @param String $message
     * @param String $caller
     * @return $this
     */
    public function info($message, $caller)
    {
        $this->logIt($this->getHeader('INFO', $caller) . "$message\n");
        return $this;
    }

    /**
     * Add Stack Traceback information to log
     *
     * @return $this
     */
    public function withBackTraceInfo()
    {
        $this->logIt("- Stack Traceback\n" . print_r(debug_backtrace(), true) . "\n");
        return $this;
    }

    /**
     * Add Order id to log
     *
     * @param Integer $orderId
     * @return $this
     */
    public function withOrderId($orderId)
    {
        $this->logIt('- Order #' . $orderId . "\n");
        return $this;
    }


    /**
     * Add Charge id to log
     *
     * @param Integer $chargeId
     * @return $this
     */
    public function withChargeId($chargeId)
    {
        $this->logIt('- Charge #' . $chargeId . "\n");
        return $this;
    }

    /**
     * @param $mundiOrderId
     * @return $this
     */
    public function withMundiOrderId($mundiOrderId)
    {
        $this->logIt('- Mundipagg Order #' . $mundiOrderId . "\n");
        return $this;
    }

    /**
     * Add Order status to log
     *
     * @param String $orderStatus
     * @return $this
     */
    public function withOrderStatus($orderStatus)
    {
        $this->logIt('- Order Status: ' . $orderStatus . "\n");
        return $this;
    }

    /**
     * @param $query
     * @return $this
     */
    public function withQuery($query)
    {
        $this->logIt('- Query: ' . $query . "\n");
        return $this;
    }

    /**
     * @param $exception
     * @return $this
     */
    public function withException($exception)
    {
        $this->logIt("- Exception\n" . $exception->getMessage() . "\n");
        return $this;
    }

    /**
     * Add Order status from Mundipagg to log
     *
     * @param String $orderStatus
     * @return $this
     */
    public function withOrderStatusFromMP($orderStatus)
    {
        $this->logIt('- Order Status received from Mundipagg: ' . $orderStatus . "\n");
        return $this;
    }

    /**
     * Add Charge status to log
     *
     * @param String $chargeStatus
     * @return $this
     */
    public function withChargeStatus($chargeStatus)
    {
        $this->logIt('- Charge Status: ' . $chargeStatus . "\n");
        return $this;
    }

    /**
     * Add Charge status from Mundipagg to log
     *
     * @param String $chargeStatus
     * @return $this
     */
    public function withChargeStatusFromMP($chargeStatus)
    {
        $this->logIt('- Charge Status received from Mundipagg: ' . $chargeStatus . "\n");
        return $this;
    }

    /**
     * Add Web hook information to log
     *
     * @param string $webHook
     * @return $this
     */
    public function withWebHook($webHook)
    {
        $prettyWebHook = json_encode($webHook, JSON_PRETTY_PRINT);

        $this->logIt('- Web Hook received from Mundipagg: ' . $prettyWebHook . "\n");
        return $this;
    }

    /**
     * Add WebHook id to log
     *
     * @param string $webHookId
     * @return $this
     */
    public function withWebHookId($webHookId)
    {
        $this->logIt('- WebHook Id: ' . $webHookId . "\n");
        return $this;
    }

    /**
     * Add WebHook status to log
     *
     * @param string $status
     * @return $this
     */
    public function withWebHookStatus($status)
    {
        $this->logIt('- WebHook status: ' . $status . "\n");
        return $this;
    }

    /**
     * Add Request sent to Mundipagg to log
     *
     * @param String $request Array or json encoded request
     * @return $this
     */
    public function withRequest($request)
    {
        if (is_array($request)) {
            $request = json_encode($request, JSON_PRETTY_PRINT);
        }

        $this->logIt("- Store -> Mundipagg\n" . $request . "\n");
        return $this;
    }

    /**
     * Add Response sent to Store to log
     *
     * @param String $response
     * @return $this
     */
    public function withResponse($response)
    {
        $this->logIt("- Mundipagg -> Store\n" . $response . "\n");
        return $this;
    }

    /**
     * @param $responseStatus
     * @return $this
     */
    public function withResponseStatus($responseStatus)
    {
        $this->logIt("- Mundipagg -> Store\n" . $responseStatus . "\n");
        return $this;
    }

    /**
     * @param $lineNumber
     * @return $this
     */
    public function withLineNumber($lineNumber)
    {
        $this->logIt('- Line number: ' . $lineNumber . "\n");
        return $this;
    }

    /**
     * Return a file pointer to the log file
     *
     * @return Resource
     */
    private function getLogFile()
    {
        if (is_resource($this->logFile)) {
            return $this->logFile;
        }

        $this->logFile = fopen($this->getLogFilePath(), 'a');
        return $this->logFile;
    }

    /**
     * Return the log file path (one for every day)
     *
     * @return String
     */
    private function getLogFilePath()
    {
        return DIR_LOGS . 'Mundipagg_opencart_' . date('Y-m-d') . '.log';
    }

    /**
     * Return the header log message
     *
     * @param String $type
     * @param String $caller
     * @return String
     */
    private function getHeader($type, $caller)
    {
        if (!empty($this->headerMessage)) {
            return $this->headerMessage;
        }

        $label =  date('H:i:s') . ' - ' . '[' . $type . ']' . ' ';
        $phpVersion = '[PHP version ' . PHP_MAJOR_VERSION . '.' . PHP_MINOR_VERSION . ']';
        $this->headerMessage = $label . $phpVersion . LogMessages::LOG_HEADER . '[' . $caller . '] ';

        return $this->headerMessage;
    }

    /**
     * Append a message to the log file
     *
     * @param String $message
     * @return Void
     */
    private function logIt($message)
    {
        fwrite($this->getLogFile(), $message);
        fclose($this->logFile);
    }
}
