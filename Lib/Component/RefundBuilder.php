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
 * Class RefundBuilder
 * @package Genome\Lib\Component
 */
class RefundBuilder extends BaseBuilder
{
    /** @var string */
    private $action = 'api/refund';

    /** @var IdentityInterface */
    private $identity;

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
     * @throws GeneralGenomeException
     */
    public function __construct(
        IdentityInterface $identity,
        $transactionId,
        LoggerInterface $logger
    ) {
        parent::__construct($logger);

        $validator = new Validator();
        $this->identity = $identity;
        $this->transactionId = $validator->validateString('transactionId', $transactionId);
        $this->client = new CurlClient($this->baseHost . $this->action, $logger);
        $this->signatureHelper  = new SignatureHelper();

        $logger->info('Refund builder successfully initialized');
    }

    /**
     * @return array
     * @throws GeneralGenomeException
     */
    public function send()
    {
        $data = array(
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
