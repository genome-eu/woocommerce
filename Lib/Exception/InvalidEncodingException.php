<?php

namespace Genome\Lib\Exception;

/**
 * Class InvalidEncodingException
 * @package Genome\Lib\Exception
 */
class InvalidEncodingException extends GeneralGenomeException
{
    /** @param string $paramName */
    public function __construct($paramName)
    {
        parent::__construct(
            sprintf('Passed argument `%s` has wrong encoding', $paramName)
        );
    }
}
