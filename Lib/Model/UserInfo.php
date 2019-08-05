<?php

namespace Genome\Lib\Model;

use Genome\Lib\Exception\GeneralGenomeException;
use Genome\Lib\Util\Validator;

/**
 * Class UserInfo
 * @package Genome\Lib\Model
 */
class UserInfo implements UserInfoInterface
{
    /** @var string */
    private $email;

    /** @var string|null */
    private $firstName;

    /** @var string|null */
    private $lastName;

    /** @var string|null */
    private $ISO3Country;

    /** @var string|null */
    private $city;

    /** @var string|null */
    private $postalCode;

    /** @var string|null */
    private $address;

    /** @var string|null */
    private $phone;

    /**
     * @param string $email
     * @param string|null $firstName
     * @param string|null $lastName
     * @param string|null $ISO3Country
     * @param string|null $city
     * @param string|null $postalCode
     * @param string|null $address
     * @param string|null $phone
     * @throws GeneralGenomeException
     */
    public function __construct(
        $email,
        $firstName = null,
        $lastName = null,
        $ISO3Country = null,
        $city = null,
        $postalCode = null,
        $address = null,
        $phone = null
    ) {
        $validator = new Validator();
        $this->email = $validator->validateString('email', $email);
        if ( $firstName !== null ) {
            $this->firstName = $validator->validateString('firstName', $firstName);
        }
        if ( $lastName !== null ) {
            $this->lastName = $validator->validateString('lastName', $lastName);
        }
        if ( $ISO3Country !== null ) {
            $this->ISO3Country = $validator->validateString('ISO3Country', $ISO3Country, 3, 3);
        }
        if ( $city !== null ) {
            $this->city = $validator->validateString('city', $city);
        }
        if ( $postalCode !== null ) {
            $this->postalCode = $validator->validateString('postalCode', $postalCode);
        }
        if ( $address !== null ) {
            $this->address = $validator->validateString('address', $address);
        }
        if ( $phone !== null ) {
            $this->phone = $validator->validateString('phone', $phone);
        }
    }


    /** @return array */
    public function toHashMap()
    {
        $result = array(
            'email' => $this->email,
        );

        if ( $this->firstName !== null ) {
            $result['firstName'] = $this->firstName;
        }
        if ( $this->lastName !== null ) {
            $result['lastName'] = $this->lastName;
        }
        if ( $this->ISO3Country !== null ) {
            $result['country'] = $this->ISO3Country;
        }
        if ( $this->city !== null ) {
            $result['city'] = $this->city;
        }
        if ( $this->address !== null ) {
            $result['address'] = $this->address;
        }
        if ( $this->postalCode !== null ) {
            $result['zip'] = $this->postalCode;
        }
        if ( $this->phone !== null ) {
            $result['phone'] = $this->phone;
        }

        return $result;
    }
}
