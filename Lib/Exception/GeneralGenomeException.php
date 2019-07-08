<?php

namespace Genome\Lib\Exception;

/**
 * General Genome exception
 *
 * Class GeneralGenomeException
 * @package Genome\Lib\Exception
 */
class GeneralGenomeException extends \Exception
{
    public function __construct($message = "", \Exception $previous = null, $code = 0)
    {
        \Exception::__construct($message, $code, $previous);
    }
}
