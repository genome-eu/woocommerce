<?php

namespace Genome\Lib\Exception;

/**
 * Class EmptyArgumentException
 * @package Genome\Lib\Exception
 */
class EmptyArgumentException extends GeneralGenomeException
{
    /** @param string $paramName */
    public function __construct($paramName)
    {
        parent::__construct(
            sprintf('Passed argument `%s` is empty', $paramName)
        );
    }
}
