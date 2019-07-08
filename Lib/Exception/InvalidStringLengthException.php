<?php

namespace Genome\Lib\Exception;

class InvalidStringLengthException extends GeneralGenomeException
{
    /**
     * @param string $paramName
     * @param int $maxLength
     * @param int $minLength
     */
    public function __construct($paramName, $maxLength, $minLength)
    {
        parent::__construct(
            sprintf(
                'Passed argument `%s` exceeds allowed length, allowed length: from `%d` to `%d`',
                $paramName,
                $minLength,
                $maxLength
            )
        );
    }
}
