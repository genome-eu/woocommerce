<?php

namespace Genome\Lib\Component;

use Genome\Lib\Exception\GeneralGenomeException;
use Genome\Lib\Model\IdentityInterface;
use Genome\Lib\Psr\Log\LoggerInterface;
use Genome\Lib\Util\ClientInterface;
use Genome\Lib\Util\CurlClient;
use Genome\Lib\Util\SignatureHelper;
use Genome\Lib\Util\Validator;

/**
 * Class StopSubscriptionBuilder
 * @package Genome\Lib\Component
 */
class StopSubscriptionBuilder extends BaseBuilder
{
    /** @var string */
    private $action = 'api/cancel';

    /** @var IdentityInterface */
    private $identity;

    /** @var string */
    private $userId;

    /** @var string */
    private $transactionId;

    /** @var ClientInterface */
    private $client;

    /** @var SignatureHelper */
    private $signatureHelper;

	/**
	 * @param IdentityInterface $identity
	 * @param string $userId
	 * @param string $transactionId
	 * @param LoggerInterface $logger
	 * @param string $baseHost
	 * @throws GeneralGenomeException
	 */
    public function __construct(
        IdentityInterface $identity,
        $userId,
        $transactionId,
        LoggerInterface $logger,
        $baseHost
    ) {
        parent::__construct($logger);

        $validator = new Validator();
        $this->identity = $identity;
        $this->userId = $validator->validateString('userId', $userId);
        $this->transactionId = $validator->validateString('transactionId', $transactionId);
        $this->client = new CurlClient($baseHost . $this->action, $logger);
        $this->signatureHelper  = new SignatureHelper();

        $logger->info('Stop subscription builder successfully initialized');
    }

    /**
     * @return array
     * @throws GeneralGenomeException
     */
    public function send()
    {
        $data = array(
            'uniqueUserId' => $this->userId,
            'transactionId' => $this->transactionId,
            'publicKey' => $this->identity->getPublicKey()
        );

        $data['signature'] = $this->signatureHelper->generate(
            $data,
            $this->identity->getPrivateKey(),
            true
        );

        return $this->prepareAnswer($this->client->send($data));
    }
}
