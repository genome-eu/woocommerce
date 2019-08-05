<?php

namespace Genome\Lib\Model;

use Genome\Lib\Exception\GeneralGenomeException;
use Genome\Lib\Util\SignatureHelper;
use Genome\Lib\Util\StringHelper;

/**
 * Class BaseButton
 * @package Genome\Lib\Model
 */
abstract class BaseButton implements RenderableInterface
{
    /** @var string */
    protected $builderScriptName = 'paymentPage';

    /** @var string[] */
    protected $fieldList = array();

    /** @var string[] */
    private $unsafeFieldList = array();

    /** @var string */
    protected $buttonCode = '';

    /** @var string */
    private $key = '';

    /**
     * @param string $name
     * @param mixed $value
     */
    public function pushValue($name, $value)
    {
        $this->unsafeFieldList[$name] = $value;
    }

    /** @param string $key */
    public function setKey($key)
    {
        $this->key = $key;
    }

    private function setSignature()
    {
        $signatureHelper = new SignatureHelper();
        $this->pushValue(
            'signature',
            $signatureHelper->generate(
                $this->unsafeFieldList,
                $this->key,
                true
            )
        );
    }

    /** @return void */
    abstract public function build();

	/**
	 * @return string
	 * @throws GeneralGenomeException
	 */
    public function asString()
    {
        $this->setSignature();
        $stringHelper = new StringHelper();
        foreach ($this->unsafeFieldList as $k => $v) {
            $this->fieldList[
                $stringHelper->encodeHtmlAttribute($k)
            ] = $stringHelper->encodeHtmlAttribute($v);
        }
        $this->build();
        return $this->buttonCode;
    }

	/**
	 * @return void
	 * @throws GeneralGenomeException
	 */
    public function display()
    {
        echo $this->asString();
    }

	/**
	 * @return string
	 * @throws GeneralGenomeException
	 */
    public function __toString()
    {
        return $this->asString();
    }
}
