<?php

namespace Genome\Lib\Exception;

/**
 * Class NotNumericException
 * @package Genome\Lib\Exception
 */
class NotNumericException extends GeneralGenomeException
{
    /** @param string $paramName */
    public function __construct($paramName)
    {
        parent::__construct(
            sprintf('Passed argument `%s` is not numeric expected int or float value', $paramName)
        );
    }
}
