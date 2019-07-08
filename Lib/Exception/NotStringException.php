<?php

namespace Genome\Lib\Exception;

/**
 * Class NotStringException
 * @package Genome\Lib\Exception
 */
class NotStringException extends GeneralGenomeException
{
    /** @param string $paramName */
    public function __construct($paramName)
    {
        parent::__construct(
            sprintf('Passed argument `%s` is not string', $paramName)
        );
    }
}
