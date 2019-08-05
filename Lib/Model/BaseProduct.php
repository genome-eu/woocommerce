<?php

namespace Genome\Lib\Model;

use Genome\Lib\Exception\GeneralGenomeException;
use Genome\Lib\Util\Validator;

/**
 * Class BaseProduct
 * @package Genome\Lib\Model
 */
class BaseProduct implements ProductInterface
{
    const TYPE_SUBSCRIPTION = 'subscriptionProduct';
    const TYPE_FIXED = 'fixedProduct';
    const TYPE_TRIAL = 'trialProduct';

    const DISCOUNT_AMOUNT = 'amountOff';
    const DISCOUNT_PERCENT = 'percentOff';

    const SUBSCRIPTION_24H   = '24H';
    const SUBSCRIPTION_7D    = '7D';
    const SUBSCRIPTION_30D   = '30D';
    const SUBSCRIPTION_365D  = '365D';

    const TRIAL_24H  = '24H';
    const TRIAL_7D   = '7D';
    const TRIAL_30D  = '30D';
    const TRIAL_365D = '365D';

    /** @var string */
    private $type;

    /** @var string */
    private $productId;

    /** @var string */
    private $productName;

    /** @var string|null */
    private $productDescription;

    /** @var string */
    private $currency;

    /** @var int|float */
    private $amount;

    /** @var int|float|null */
    private $discount;

    /** @var string|null */
    private $discountType;

    /** @var int|null */
    private $subscriptionLength;

    /** @var string|null */
    private $subscriptionPeriod;

    /** @var float|null */
    private $subscriptionEndDate;

    /** @var int|null */
    private $subscriptionBillingCycles;

    /** @var string|null  */
    private $postTrialProductId;

    /** @var int|null  */
    private $postTrialLength;

    /** @var string|null  */
    private $postTrialPeriod;

    /**
     * @param string $type
     * @param string $productId
     * @param string $productName
     * @param string $currency
     * @param int|float $amount
     * @param int|float|null $discount
     * @param string|null $discountType
     * @param string|null $productDescription
     * @param int|null $subscriptionLength
     * @param string|null $subscriptionPeriod
     * @param int|null $subscriptionBillingCycles
     * @param float|null $subscriptionEndDate
     * @param string|null $postTrialProductId
     * @param int|null $postTrialLength
     * @param string|null $postTrialPeriod
     * @throws GeneralGenomeException
     */
    public function __construct(
        $type,
        $productId,
        $productName,
        $currency,
        $amount,
        $discount = null,
        $discountType = null,
        $productDescription = null,
        $subscriptionLength = null,
        $subscriptionPeriod = null,
        $subscriptionBillingCycles = null,
        $subscriptionEndDate = null,
        $postTrialProductId = null,
        $postTrialLength = null,
        $postTrialPeriod = null
    ) {
        $validator = new Validator();
        $type = $validator->validateString('productType', $type);
        if (! in_array( $type, array( self::TYPE_SUBSCRIPTION, self::TYPE_TRIAL, self::TYPE_FIXED ), true ) ) {
            throw new GeneralGenomeException('Invalid product type given');
        }

        $this->type = $type;
        $this->productId = $validator->validateString('productId', $productId);
        $this->productName = $validator->validateString('productName', $productName);
        $this->currency = $validator->validateString('currency', $currency, 3, 3);
        $this->amount = $validator->validateNumeric('amount', $amount);
        $this->productDescription = $productDescription === null ?
            null :
            $validator->validateString('productDescription', $productDescription);

        $this->discount = $discount === null ? null : $validator->validateNumeric('discount', $discount);

        if ( $discountType !== null ) {
            $discountType = $validator->validateString('discountType', $discountType);
            if (! in_array( $discountType, array( self::DISCOUNT_AMOUNT, self::DISCOUNT_PERCENT ), true ) ) {
                throw new GeneralGenomeException('Invalid discount type given');
            }
            $this->discountType = $discountType;
        }

        if ( $subscriptionLength !== null && $this->type === self::TYPE_SUBSCRIPTION) {
            $this->subscriptionLength = $validator->validateNumeric('subscriptionLength', $subscriptionLength);
            $subscriptionPeriod = $validator->validateString('subscriptionPeriod', $subscriptionPeriod);

            if (! in_array( $subscriptionPeriod,
	            array(
		            self::SUBSCRIPTION_24H,
		            self::SUBSCRIPTION_7D,
		            self::SUBSCRIPTION_30D,
		            self::SUBSCRIPTION_365D
	            ),
	            true ) ) {
                throw new GeneralGenomeException('Invalid subscription period given');
            }

            $this->subscriptionPeriod = $subscriptionPeriod;

            $this->subscriptionBillingCycles = $subscriptionBillingCycles === null ?
            null :
            $validator->validateNumeric('subscriptionBillingCycles', $subscriptionBillingCycles);


            $this->subscriptionEndDate = $subscriptionEndDate === null ?
            null :
            $validator->validateNumeric('subscriptionEndDate', $subscriptionEndDate);
        }

        if ( $postTrialProductId !== null && $this->type === self::TYPE_TRIAL) {
            $this->postTrialProductId = $validator->validateString('postTrialProductId', $postTrialProductId);
            $this->postTrialLength = $validator->validateNumeric('postTrialLength', $postTrialLength);
            $postTrialPeriod = $validator->validateString('postTrialPeriod', $postTrialPeriod);
            if (! in_array( $postTrialPeriod, array( self::TRIAL_24H, self::TRIAL_7D, self::TRIAL_30D, self::TRIAL_365D ), true ) ) {
                throw new GeneralGenomeException('Invalid post trial period given');
            }
            $this->postTrialPeriod = $postTrialPeriod;
        }
    }

    /** @return array */
    public function toHashMap()
    {
        $result = array(
            'productType' => $this->type,
            'productId' => $this->productId,
            'productName' => $this->productName,
            'currency' => $this->currency,
            'amount' => $this->amount
        );

        if ( $this->discount !== null && $this->discountType !== null ) {
            $result['discount'] = $this->discount;
            $result['discountType'] = $this->discountType;
        }

        if ( $this->productDescription !== null ) {
            $result['productDescription'] = $this->productDescription;
        }

        //Subscription section
        if ( $this->subscriptionLength !== null ) {
            $result['subscriptionLength'] = (int) $this->subscriptionLength;
        }
        if ( $this->subscriptionPeriod !== null ) {
            $result['subscriptionPeriod'] = $this->subscriptionPeriod;
        }
        if ( $this->subscriptionBillingCycles !== null ) {
            $result['subscriptionBillingCycles'] = (int) $this->subscriptionBillingCycles;
        }
        if ( $this->subscriptionEndDate !== null ) {
            $result['subscriptionEndDate'] = $this->subscriptionEndDate;
        }

        //Post trial section
        if ( $this->postTrialProductId !== null ) {
            $result['postTrialProductId'] = $this->postTrialProductId;
        }
        if ( $this->postTrialLength !== null ) {
            $result['trialLength'] = (int) $this->postTrialLength;
        }
        if ( $this->postTrialPeriod !== null ) {
            $result['trialPeriod'] = $this->postTrialPeriod;
        }

        return $result;
    }
}
