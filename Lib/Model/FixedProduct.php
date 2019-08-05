<?php

namespace Genome\Lib\Model;

use Genome\Lib\Exception\GeneralGenomeException;

/**
 * Class FixedProduct
 *
 * @package Genome\Lib\Model
 */
class FixedProduct extends BaseProduct
{
    /**
     * @param string $productId
     * @param string $productName
     * @param string $amount
     * @param string $currency
     * @param int|float|null $discount
     * @param string|null $discountType
     * @param string|null $productDescription
     * @throws GeneralGenomeException
     */
    public function __construct(
        $productId,
        $productName,
        $amount,
        $currency,
        $discount = null,
        $discountType = null,
        $productDescription = null
    ) {
        parent::__construct(
            self::TYPE_FIXED,
            $productId,
            $productName,
            $currency,
            $amount,
            $discount,
            $discountType,
            $productDescription
        );
    }
}
