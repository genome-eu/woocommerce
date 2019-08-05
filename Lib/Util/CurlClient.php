<?php

namespace Genome\Lib\Util;

use Genome\Lib\Exception\GeneralGenomeException;
use Genome\Lib\Psr\Log\LoggerInterface;

/**
 * Class CurlClient
 * @package Genome\Lib\Util
 */
class CurlClient implements ClientInterface
{
    /** @var string */
    private $url;

    /** @var LoggerInterface */
    private $logger;

    /** @var ValidatorInterface */
    private $validator;

    const DEFAULT_CONNECT_TIMEOUT = 7500;

    /**
     * @param $url
     * @param LoggerInterface $logger
     * @throws GeneralGenomeException
     */
    public function __construct($url, LoggerInterface $logger)
    {
        $this->validator = new Validator();
        $this->url = $this->validator->validateString('url', $url);
        $this->logger = $logger;
    }

    /**
     * @param mixed[] $data
     * @throws GeneralGenomeException
     * @return mixed[]
     */
    public function send(array $data)
    {
        $start = microtime(true);
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, $this->url);
        curl_setopt($curl, CURLOPT_CONNECTTIMEOUT_MS, self::DEFAULT_CONNECT_TIMEOUT);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_POST, true);
        curl_setopt($curl, CURLOPT_POSTFIELDS, $data);

        $result = curl_exec($curl);

        $status = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        $errno  = curl_errno($curl);
        $error  = curl_error($curl);
        curl_close($curl);

        $this->logger->info(
            'Received answer',
            array(
                'packetSize' => strlen($result),
                'time' => microtime(true) - $start,
            )
        );

	    if ( $errno === CURLE_OPERATION_TIMEOUTED ) {
	        $e = new GeneralGenomeException('Client timeout');
	        $this->logger->error(
	            $e->getMessage(),
	            array(
	                'exception' => $e
	            )
	        );

	        throw $e;
	    }

	    if ( $errno === CURLE_SSL_CACERT
		     || $errno === CURLE_SSL_CERTPROBLEM
		     || $errno === CURLE_SSL_CIPHER
		     || $errno === CURLE_SSL_CONNECT_ERROR
		     || $errno === CURLE_SSL_PEER_CERTIFICATE
		     || $errno === CURLE_SSL_ENGINE_NOTFOUND
		     || $errno === CURLE_SSL_ENGINE_SETFAILED ) {
			     $e = new GeneralGenomeException('Client SSL error, code ' . $error, null, $errno );
			     $this->logger->error(
			         $e->getMessage(),
			         array( 'exception' => $e, 'errno' => $errno )
			     );

			     throw $e;
			 }

	    if ( $errno !== CURLE_OK ) {
	        $e = new GeneralGenomeException('Client error ' . $error, null, $errno );
	        $this->logger->error(
	            $e->getMessage(),
	            array( 'exception' => $e, 'errno' => $errno )
	        );

	        throw $e;
	    }

	    if ($result === false) {
            $e = new GeneralGenomeException(sprintf('Curl error. Received status %s, curl error %s', $status, $error));
            $this->logger->error(
                $e->getMessage(),
                array( 'exception' => $e, 'status' => $status )
            );

            throw $e;
        }

        try {
            $result = $this->decode($result);

        } catch (\Exception $exception) {
            $error = new GeneralGenomeException('Failed to decode answer', $exception);
            $this->logger->error(
                $error->getMessage(),
                array( 'exception' => $error )
            );
            throw $error;
        }

        return $result;
    }

    /**
     * @param string $stringAnswer
     * @return mixed[]
     * @throws GeneralGenomeException
     */
    private function decode($stringAnswer)
    {
        $stringAnswer = $this->validator->validateString('answer', $stringAnswer);

        $data  = json_decode($stringAnswer, true);
        if ($data === null && json_last_error() !== JSON_ERROR_NONE) {
            $message = 'JSON unserialization error';
            if (function_exists('json_last_error_msg')) {
                $message .= ' ' . json_last_error_msg();
            }

            $e = new GeneralGenomeException($message, null, json_last_error());
            $this->logger->error(
                $e->getMessage(),
                array( 'exception' => $e )
            );

            throw $e;
        }

        $this->logger->info('Packet successfully decoded' );

        return $data;
    }
}
