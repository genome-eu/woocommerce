<?php

namespace Genome\Lib\Component;

use Genome\Lib\Exception\GeneralGenomeException;
use Genome\Lib\Model\IdentityInterface;
use Genome\Lib\Model\ProductInterface;
use Genome\Lib\Psr\Log\LoggerInterface;
use Genome\Lib\Util\CurlClient;
use Genome\Lib\Util\SignatureHelper;
use Genome\Lib\Util\Validator;

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
	 * @throws GeneralGenomeException
	 */
    public function __construct(IdentityInterface $identity, $billToken, $userId, LoggerInterface $logger, $baseHost)
    {
        parent::__construct($logger);
        $validator = new Validator();
        $this->identity = $identity;
        $this->logger = $logger;
        $validator->validateString('baseHost', $baseHost);
        $this->billToken = $validator->validateString('billToken', $billToken);
        $this->userId = $validator->validateString('userId', $userId);
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
        $preparedData = array(
            'publicKey' => $this->identity->getPublicKey(),
            'uniqueUserId' => $this->userId,
            'rebillToken' => $this->billToken,
        );

        if ( $this->productId !== null ) {
            $preparedData['productId'] = $this->productId;
        }

        if ( $this->userInfo !== null ) {
            $preparedData = array_merge($preparedData, $this->userInfo->toHashMap());
        }

        if (is_array($this->customParams)) {
            foreach ($this->customParams as $k => $v) {
                $preparedData[$k] = $v;
            }
        }

        if ( $this->customProduct !== null ) {
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
