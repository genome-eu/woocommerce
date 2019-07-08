<?php

namespace Genome\Lib\Component;

use Genome\Lib\Exception\GeneralGenomeException;
use Genome\Lib\Exception\NotBooleanException;
use Genome\Lib\Model\BaseButton;
use Genome\Lib\Model\DirectButton;
use Genome\Lib\Model\FrameButton;
use Genome\Lib\Model\IdentityInterface;
use Genome\Lib\Model\PopupButton;
use Genome\Lib\Model\ProductInterface;
use Genome\Lib\Model\RenderableInterface;
use Genome\Lib\Util\Validator;
use Genome\Lib\Util\ValidatorInterface;
use Psr\Log\LoggerInterface;

/**
 * Class ButtonBuilder
 * @package Genome\Lib\Component
 */
class ButtonBuilder extends BaseBuilder
{
    const TYPE_POPUP = 'popup';
    const TYPE_DIRECT = 'direct';
    const TYPE_FRAME = 'frame';

    /** @var ValidatorInterface */
    private $validator;

    /** @var IdentityInterface */
    private $identity;

    /** @var LoggerInterface */
    private $logger;

    /** @var string */
    private $userId;

    /** @var bool */
    private $showButton = true;

    /** @var string */
    private $buttonText = 'Pay';

    /** @var productInterface[] */
    private $customProducts = [];

    /** @var string */
    private $baseHost;

    /** @var string|null */
    private $successUrl;

    /** @var string|null */
    private $declineUrl;

    /**
     * @param IdentityInterface $identity
     * @param string $userId
     * @param LoggerInterface $logger
     * @param string $baseHost
     */
    public function __construct(IdentityInterface $identity, $userId, LoggerInterface $logger, $baseHost)
    {
        parent::__construct($logger);
        $this->validator = new Validator();
        $this->identity = $identity;
        $this->logger = $logger;
        $this->userId = $this->validator->validateString('userId', $userId);
        $this->baseHost = $baseHost;
        $this->logger->info('Button builder successfully initialized');
    }

    /**
     * Set success return url
     *
     * @param string $successUrl
     * @return ButtonBuilder
     * @throws GeneralGenomeException
     */
    public function setSuccessReturnUrl($successUrl)
    {
        try {
            $this->successUrl = $this->validator->validateString('successUrl', $successUrl);
            $this->logger->info('Field `successUrl` successfully set');
            return $this;
        } catch (GeneralGenomeException $e) {
            $this->logger->error(
                'Invalid success url',
                [
                    'exception' => $e,
                ]
            );

            throw $e;
        }
    }

    /**
     * Set success decline url
     *
     * @param string $declineUrl
     * @return ButtonBuilder
     * @throws GeneralGenomeException
     */
    public function setDeclineReturnUrl($declineUrl)
    {
        try {
            $this->declineUrl = $this->validator->validateString('declineUrl', $declineUrl);
            $this->logger->info('Field `declineUrl` successfully set');
            return $this;
        } catch (GeneralGenomeException $e) {
            $this->logger->error(
                'Invalid decline url',
                [
                    'exception' => $e,
                ]
            );

            throw $e;
        }
    }

    /**
     * Show pay button after rendering
     *
     * @param bool $value
     * @return ButtonBuilder
     * @throws GeneralGenomeException
     */
    public function setShowButton($value)
    {
        if (!is_bool($value)) {
            $this->logger->error('Invalid `show button` value, bool expected');
            throw new NotBooleanException('showButton');
        }

        $this->showButton = $value;
        $this->logger->info('Field `showButton` successfully set');
        return $this;
    }

    /**
     * Set text on payment button
     *
     * @param string $buttonText
     * @return ButtonBuilder
     * @throws GeneralGenomeException
     */
    public function setButtonText($buttonText)
    {
        try {
            $this->buttonText = $this->validator->validateString('buttonText', $buttonText);
            $this->logger->info('Field `buttonText` successfully set');
            return $this;
        } catch (GeneralGenomeException $e) {
            $this->logger->error(
                'Invalid button text',
                [
                    'exception' => $e,
                ]
            );

            throw $e;
        }
    }

    /**
     * Set custom product - products will be summarized and displayed on payment page
     *
     * @param ProductInterface[] $products
     * @throws GeneralGenomeException
     * @return ButtonBuilder
     */
    public function setCustomProducts(array $products)
    {
        foreach ($products as $product) {
            if (!$product instanceof ProductInterface) {
                $this->logger->error('Invalid product object given, expected ProductInterface');
                throw new GeneralGenomeException('Invalid product model');
            }

            $this->customProducts[] = $product;
        }
        $this->logger->info('Field `customProduct` successfully set');

        return $this;
    }

    /** @return RenderableInterface */
    public function buildPopup()
    {
        return $this->build(new PopupButton($this->baseHost));
    }

    /**
     * @param string $height
     * @param string $width
     * @throws GeneralGenomeException
     * @return RenderableInterface
     */
    public function buildFrame($height = 'auto', $width = 'auto')
    {
        return $this->build(
            new FrameButton(
                $this->validator->validateString('height', $height),
                $this->validator->validateString('width', $width),
                $this->baseHost
            )
        );
    }

    /** @return RenderableInterface */
    public function buildDirectForm()
    {
        return $this->build(new DirectButton($this->baseHost));
    }

    /**
     * @param BaseButton $button
     * @internal param string $type
     * @return RenderableInterface
     */
    private function build(BaseButton $button)
    {
        $button->setKey($this->identity->getPrivateKey());
        $button->pushValue('key', $this->identity->getPublicKey());
        $button->pushValue('buttontext', $this->buttonText);
        $button->pushValue('uniqueuserid', $this->userId);
        $button->pushValue('displaybuybutton', $this->showButton ? 'true' : 'false');
        if (!is_null($this->successUrl)) {
            $button->pushValue('success_url', $this->successUrl);
        }
        if (!is_null($this->declineUrl)) {
            $button->pushValue('decline_url', $this->declineUrl);
        }
        if (!is_null($this->productId)) {
            $button->pushValue('productpublicid', $this->productId);
        }
        foreach ($this->customParams as $key => $value) {
            $button->pushValue($key, $value);
        }
        if (!is_null($this->userInfo)) {
            foreach ($this->userInfo->toHashMap() as $k => $v) {
                $button->pushValue($k, $v);
            }
        }
        $customProduct = [];
        foreach ($this->customProducts as $product) {
            $customProduct[] = $product->toHashMap();
        }
        if (count($customProduct) > 0) {
            $button->pushValue('customproduct', json_encode($customProduct));
        }

        return $button;
    }
}
