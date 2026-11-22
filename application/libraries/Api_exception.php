<?php

defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Transport-neutral API error. Controllers catch it and render a JSON
 * envelope; nothing leaks a stack trace to the client.
 */
class Api_exception extends Exception
{
    /** @var int */
    private $status;

    /** @var array */
    private $details;

    /** @var string */
    private $errorCode;

    public function __construct($errorCode, $message, $status = 400, array $details = [])
    {
        parent::__construct($message);

        $this->errorCode = $errorCode;
        $this->status    = (int) $status;
        $this->details   = $details;
    }

    public function getStatus()
    {
        return $this->status;
    }

    public function getErrorCode()
    {
        return $this->errorCode;
    }

    public function getDetails()
    {
        return $this->details;
    }
}
