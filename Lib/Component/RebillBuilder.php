<?php

namespace Genome\Lib\Component;

use Genome\Lib\Exception\GeneralGenomeException;
use Genome\Lib\Model\IdentityInterface;
use Genome\Lib\Model\ProductInterface;
use Genome\Lib\Util\CurlClient;
use Genome\Lib\Util\SignatureHelper;
use Genome\Lib\Util\Validator;
use Genome\Lib\Util\ValidatorInterface;
use Psr\Log\LoggerInterface;

/**
 * Class RebillBuilder
 * @package Genome\Lib\Component
 */
class RebillBuilder extends BaseBuilder
{
    /** @var string */
    private $action = 'api/rebilling';

    /** @var string */
    private $baseHost;

    /** @var ValidatorInterface */
    private $validator;

    /** @var IdentityInterface */
    private $identity;

    /** @var LoggerInterface */
    private $logger;

    /** @var string */
    private $billToken;

    /** @var string */
    private $userId;

    /** @var ProductInterface|null */
    private $customProduct;

    /** @var SignatureHelper */
    private $signatureHelper;

    /** @var CurlClient */
    private $client;

    /**
     * @param IdentityInterface $identity
     * @param string $billToken
     * @param string $userId
     * @param string $baseHost
     * @param LoggerInterface $logger
     */
    public function __construct(IdentityInterface $identity, $billToken, $userId, LoggerInterface $logger, $baseHost)
    {
        parent::__construct($logger);
        $this->validator = new Validator();
        $this->identity = $identity;
        $this->logger = $logger;
        $this->baseHost = $this->validator->validateString('baseHost', $baseHost);
        $this->billToken = $this->validator->validateString('billToken', $billToken);
        $this->userId = $this->validator->validateString('userId', $userId);
        $this->signatureHelper  = new SignatureHelper();
        $this->client = new CurlClient($this->baseHost . $this->action, $logger);

        $this->logger->info('Rebill builder successfully initialized');
    }

    /**
     * Setup a custom product
     *
     * @param ProductInterface $product
     * @return RebillBuilder
     */
    public function setCustomProduct(ProductInterface $product)
    {
        $this->customProduct = $product;
        $this->logger->info('Custom product successfully set');

        return $this;
    }

    /**
     * @throws GeneralGenomeException
     * @return mixed[]
     */
    public function send()
    {
        $preparedData = [
            'publicKey' => $this->identity->getPublicKey(),
            'uniqueUserId' => $this->userId,
            'rebillToken' => $this->billToken,
        ];

        if (!is_null($this->productId)) {
            $preparedData['productId'] = $this->productId;
        }

        if (!is_null($this->userInfo)) {
            $preparedData = array_merge($preparedData, $this->userInfo->toHashMap());
        }

        if (is_array($this->customParams)) {
            foreach ($this->customParams as $k => $v) {
                $preparedData[$k] = $v;
            }
        }

        if (!is_null($this->customProduct)) {
            $preparedData = array_merge($preparedData, $this->customProduct->toHashMap());
        }

        $preparedData['signature'] = $this->signatureHelper->generate(
            $preparedData,
            $this->identity->getPrivateKey(),
            true
        );

        return $this->prepareAnswer($this->client->send($preparedData));
    }
}
