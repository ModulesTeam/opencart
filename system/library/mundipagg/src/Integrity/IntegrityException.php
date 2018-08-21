<?php

namespace Mundipagg\Integrity;

use Throwable;

class IntegrityException extends \Exception
{
    protected $header;

    function __construct($header = "", $message = "", $code = 0, Throwable $previous = null)
    {
        $this->header = $header;
        parent::__construct($message, $code, $previous);
    }

    /**
     * @return mixed
     */
    public function getHeader()
    {
        return $this->header;
    }
}