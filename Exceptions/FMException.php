<?php

namespace MSDev\DoctrineFileMakerDriver\Exceptions;

use Exception;

class FMException extends Exception
{
    public function __construct($message = "", $code = 0, Exception $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}