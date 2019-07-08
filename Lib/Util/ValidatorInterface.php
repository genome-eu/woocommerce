<?php

namespace Genome\Lib\Util;

use Genome\Lib\Exception\GeneralGenomeException;

/**
 * Interface ValidatorInterface
 * @package Genome\Lib\Util
 */
interface ValidatorInterface
{
    /**
     * Method will return valid value or throw exception
     *
     * @param string $paramName
     * @param string $value
     * @param int $minLength
     * @param int|null $maxLength
     * @throws GeneralGenomeException
     * @return string
     */
    public function validateString($paramName, $value, $minLength = 1, $maxLength = null);

    /**
     * @param string $paramName
     * @param float|int $value
     * @throws GeneralGenomeException
     * @return float|int
     */
    public function validateNumeric($paramName, $value);

    /** @return string */
    public function getDefaultEncoding();
}
