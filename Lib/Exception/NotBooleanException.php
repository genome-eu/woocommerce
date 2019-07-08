<?php

namespace Genome\Lib\Exception;

/**
 * Class NotBooleanException
 * @package Genome\Lib\Exception
 */
class NotBooleanException extends GeneralGenomeException
{
    /** @param string $paramName */
    public function __construct($paramName)
    {
        parent::__construct(
            sprintf('Passed argument `%s` is not bool', $paramName)
        );
    }
}
