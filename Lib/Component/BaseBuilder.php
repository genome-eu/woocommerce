<?php

namespace Genome\Lib\Component;

use Genome\Lib\Exception\EmptyArgumentException;
use Genome\Lib\Exception\GeneralGenomeException;
use Genome\Lib\Model\UserInfoInterface;
use Genome\Lib\Util\Validator;
use Genome\Lib\Util\ValidatorInterface;
use Psr\Log\LoggerInterface;

/**
 * Class BaseBuilder
 * @package Genome\Lib\Component
 */
abstract class BaseBuilder
{
    const CUSTOM_PARAM_PREFIX = 'custom_';

    /** @var UserInfoInterface|null */
    protected $userInfo;

    /** @var array */
    protected $customParams = [];

    /** @var string|null */
    protected $productId;

    /** @var LoggerInterface */
    private $logger;

    /** @var ValidatorInterface */
    private $validator;

    /** @param LoggerInterface $logger */
    public function __construct(LoggerInterface $logger)
    {
        $this->validator = new Validator();
        $this->logger = $logger;
    }

    /**
     * Set up user information
     *
     * @param UserInfoInterface $userInfo
     * @return static
     */
    public function setUserInfo(UserInfoInterface $userInfo)
    {
        $this->userInfo = $userInfo;
        $this->logger->info('Field `userInfo` successfully set');
        return $this;
    }

    /**
     * Set custom params - params will be returned in callback
     * e.g. ['custom_some_param' => 'some value']
     *
     * @param array $params
     * @return static
     * @throws GeneralGenomeException
     */
    public function setCustomParams(array $params)
    {
        if (count($params) === 0) {
            throw new EmptyArgumentException('customParams');
        }

        foreach ($params as $paramName => $paramValue) {
            if (mb_substr($paramName, 0, 7) !== self::CUSTOM_PARAM_PREFIX) {
                $this->logger->error('Custom param name must start with prefix - ' . self::CUSTOM_PARAM_PREFIX);
                throw new GeneralGenomeException('Invalid custom param key');
            }
            $this->customParams[
                $this->validator->validateString('customParamName', $paramName)
            ] = $this->validator->validateString('customParamValue', $paramValue);
        }

        $this->logger->info('Field `customParams` successfully set');
        return $this;
    }

    /**
     * Setup a product that exists in Merchant Portal
     *
     * @param string $productId
     * @return static
     * @throws GeneralGenomeException
     */
    public function setProductId($productId)
    {
        try {
            $this->productId = $this->validator->validateString('productId', $productId);
            $this->logger->info('Field `productId` successfully set');
            return $this;
        } catch (GeneralGenomeException $e) {
            $this->logger->error(
                'Invalid product id',
                [
                    'exception' => $e,
                ]
            );

            throw $e;
        }
    }

    /**
     * @param array $response
     * @return array
     * @throws GeneralGenomeException
     */
    public function prepareAnswer($response)
    {
        if (!is_array($response) || !isset($response['requestSuccess'])) {
            $e = new GeneralGenomeException('Invalid response format');
            $this->logger->error(
                $e->getMessage(),
                ['exception' => $e]
            );

            throw $e;
        }

        if ($response['requestSuccess'] === false) {
            $e = new GeneralGenomeException(
                isset($response['description']) ? $response['description'] : 'Invalid request'
            );
            $this->logger->error(
                $e->getMessage(),
                ['exception' => $e]
            );

            throw $e;
        }

        if ($response['requestSuccess'] === true && !isset($response['payload'])) {
            $e = new GeneralGenomeException('Invalid response format from server');
            $this->logger->error(
                $e->getMessage(),
                ['exception' => $e]
            );

            throw $e;
        }

        return $response['payload'];
    }
}
