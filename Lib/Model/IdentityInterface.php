<?php

namespace Genome\Lib\Model;

/**
 * Interface IdentityInterface
 * @package Genome\Lib\Model
 */
interface IdentityInterface
{
    /** @return string */
    public function getPublicKey();

    /** @return string */
    public function getPrivateKey();
}
