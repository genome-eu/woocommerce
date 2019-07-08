<?php

namespace Genome\Lib\Component;

use Genome\Lib\Exception\GeneralGenomeException;
use Genome\Lib\Exception\NotBooleanException;
use Genome\Lib\Model\IdentityInterface;
use Genome\Lib\Util\ClientInterface;
use Genome\Lib\Util\CurlClient;
use Genome\Lib\Util\SignatureHelper;
use Genome\Lib\Util\Validator;
use Genome\Lib\Util\ValidatorInterface;
use Psr\Log\LoggerInterface;

/**
 * Class RefundBuilder
 * @package Genome\Lib\Component
 */
class RefundBuilder extends BaseBuilder
{
    /** @var string */
    private $action = 'api/refund';

    /** @var IdentityInterface */
    private $identity;

    /** @var ValidatorInterface */
    private $validator;

    /** @var LoggerInterface */
    private $logger;

    /** @var string */
    private $baseHost;

    /** @var string */
    private $transactionId;

    /** @var ClientInterface */
    private $client;

    /** @var SignatureHelper */
    private $signatureHelper;

    /**
     * @param IdentityInterface $identity
     * @param string $transactionId
     * @param LoggerInterface $logger
     * @param string $baseHost
     * @throws GeneralGenomeException
     */
    public function __construct(
        IdentityInterface $identity,
        $transactionId,
        LoggerInterface $logger,
        $baseHost
    ) {
        parent::__construct($logger);

        $this->validator = new Validator();
        $this->identity = $identity;
        $this->logger = $logger;
        $this->transactionId = $this->validator->validateString('transactionId', $transactionId);
        $this->baseHost = $baseHost;
        $this->client = new CurlClient($this->baseHost . $this->action, $logger);
        $this->signatureHelper  = new SignatureHelper();

        $this->logger->info('Refund builder successfully initialized');
    }

    /**
     * @return array
     * @throws GeneralGenomeException
     */
    public function send()
    {
        $data = [
            'transactionId' => $this->transactionId,
            'publicKey' => $this->identity->getPublicKey()
        ];

        $data['signature'] = $this->signatureHelper->generate(
            $data,
            $this->identity->getPrivateKey(),
            true
        );

        return $this->prepareAnswer($this->client->send($data));
    }
}
