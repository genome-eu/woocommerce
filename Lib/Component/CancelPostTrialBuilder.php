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
 * Class CancelPostTrialBuilder
 * @package Genome\Lib\Component
 */
class CancelPostTrialBuilder extends BaseBuilder
{
    /** @var IdentityInterface */
    private $identity;

    /** @var string */
    private $action = 'api/cancel_post_trial';

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

        $validator = new Validator();
        $this->identity = $identity;
        $this->transactionId = $validator->validateString('transactionId', $transactionId);
        $validator->validateString('baseHost', $baseHost);
        $this->signatureHelper  = new SignatureHelper();

        $this->client = new CurlClient($baseHost . $this->action, $logger);
        $logger->info('Cancel post trial builder successfully initialized');
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
