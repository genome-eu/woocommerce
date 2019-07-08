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
        if (!in_array($type, [self::TYPE_SUBSCRIPTION, self::TYPE_TRIAL, self::TYPE_FIXED])) {
            throw new GeneralGenomeException('Invalid product type given');
        }

        $this->type = $type;
        $this->productId = $validator->validateString('productId', $productId);
        $this->productName = $validator->validateString('productName', $productName);
        $this->currency = $validator->validateString('currency', $currency, 3, 3);
        $this->amount = $validator->validateNumeric('amount', $amount);
        $this->productDescription = is_null($productDescription) ?
            null :
            $validator->validateString('productDescription', $productDescription);

        $this->discount = is_null($discount) ? null : $validator->validateNumeric('discount', $discount);

        if (!is_null($discountType)) {
            $discountType = $validator->validateString('discountType', $discountType);
            if (!in_array($discountType, [self::DISCOUNT_AMOUNT, self::DISCOUNT_PERCENT])) {
                throw new GeneralGenomeException('Invalid discount type given');
            }
            $this->discountType = $discountType;
        }

        if (!is_null($subscriptionLength) && $this->type === self::TYPE_SUBSCRIPTION) {
            $this->subscriptionLength = $validator->validateNumeric('subscriptionLength', $subscriptionLength);
            $subscriptionPeriod = $validator->validateString('subscriptionPeriod', $subscriptionPeriod);

            if (!in_array(
                $subscriptionPeriod,
                [
                    self::SUBSCRIPTION_24H,
                    self::SUBSCRIPTION_7D,
                    self::SUBSCRIPTION_30D,
                    self::SUBSCRIPTION_365D
                ]
            )) {
                throw new GeneralGenomeException('Invalid subscription period given');
            }

            $this->subscriptionPeriod = $subscriptionPeriod;

            $this->subscriptionBillingCycles = is_null($subscriptionBillingCycles) ?
            null :
            $validator->validateNumeric('subscriptionBillingCycles', $subscriptionBillingCycles);


            $this->subscriptionEndDate = is_null($subscriptionEndDate) ?
            null :
            $validator->validateNumeric('subscriptionEndDate', $subscriptionEndDate);
        }

        if (!is_null($postTrialProductId) && $this->type === self::TYPE_TRIAL) {
            $this->postTrialProductId = $validator->validateString('postTrialProductId', $postTrialProductId);
            $this->postTrialLength = $validator->validateNumeric('postTrialLength', $postTrialLength);
            $postTrialPeriod = $validator->validateString('postTrialPeriod', $postTrialPeriod);
            if (!in_array($postTrialPeriod, [self::TRIAL_24H, self::TRIAL_7D, self::TRIAL_30D, self::TRIAL_365D])) {
                throw new GeneralGenomeException('Invalid post trial period given');
            }
            $this->postTrialPeriod = $postTrialPeriod;
        }
    }

    /** @return array */
    public function toHashMap()
    {
        $result = [
            'productType' => $this->type,
            'productId' => $this->productId,
            'productName' => $this->productName,
            'currency' => $this->currency,
            'amount' => $this->amount
        ];

        if (!is_null($this->discount) && !is_null($this->discountType)) {
            $result['discount'] = $this->discount;
            $result['discountType'] = $this->discountType;
        }

        if (!is_null($this->productDescription)) {
            $result['productDescription'] = $this->productDescription;
        }

        //Subscription section
        if (!is_null($this->subscriptionLength)) {
            $result['subscriptionLength'] = intval($this->subscriptionLength);
        }
        if (!is_null($this->subscriptionPeriod)) {
            $result['subscriptionPeriod'] = $this->subscriptionPeriod;
        }
        if (!is_null($this->subscriptionBillingCycles)) {
            $result['subscriptionBillingCycles'] = intval($this->subscriptionBillingCycles);
        }
        if (!is_null($this->subscriptionEndDate)) {
            $result['subscriptionEndDate'] = $this->subscriptionEndDate;
        }

        //Post trial section
        if (!is_null($this->postTrialProductId)) {
            $result['postTrialProductId'] = $this->postTrialProductId;
        }
        if (!is_null($this->postTrialLength)) {
            $result['trialLength'] = intval($this->postTrialLength);
        }
        if (!is_null($this->postTrialPeriod)) {
            $result['trialPeriod'] = $this->postTrialPeriod;
        }

        return $result;
    }
}
