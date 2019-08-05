<?php

namespace Genome\Lib\Exception;

/**
 * General Genome exception
 *
 * Class GeneralGenomeException
 *
 * @package Genome\Lib\Exception
 */
class GeneralGenomeException extends \Exception
{
	/**
	 * @param string $message
	 * @param \Exception|null $previous
	 * @param int $code
	 */
	public function __construct($message = '', \Exception $previous = null, $code = 0)
    {
        \Exception::__construct($message, $code, $previous);
    }
}
