<?php
/**
 * Created by PhpStorm.
 * User: SteveWinter
 * Date: 06/04/2017
 * Time: 08:40
 */

namespace MSDev\DoctrineFileMakerDriver\Exceptions;


class MethodNotSupportedExcpetion extends FMException
{
    public function __construct($method) {
        $msg = sprintf('FileMaker does not support the %s method.', $method);

        parent::__construct($msg);
    }
}