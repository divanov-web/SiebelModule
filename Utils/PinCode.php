<?php
/**
 * Created by PhpStorm.
 * User: a.vartan
 * Date: 20.07.2018
 * Time: 14:36
 */

namespace Taber\Siebel\Utils;

/**
 * Class PinCode
 * @package Taber\Siebel\Utils
 */
class PinCode
{
    private $pinCode;
    private $_webClient;

    /**
     * PinCode constructor.
     * @param WebClient $webClient
     * @param int $code
     * @throws \Bitrix\Main\DB\SqlQueryException
     */
    public function __construct(WebClient $webClient, int $code = 0)
    {
        $this->_webClient = $webClient;
        if ($code) {
            $this->pinCode = $code;
            self::saveCode();
        } else {
            $this->pinCode = PinCodeTable::get($this)["code"];
        }
    }

    /**
     * @return mixed
     */
    public function getCode()
    {
        return $this->pinCode;
    }

    /**
     * @return int|null
     */
    public function getWebClientId()
    {
        return $this->_webClient->getWebClientId();
    }

    /**
     * @throws \Bitrix\Main\DB\SqlQueryException
     */
    public function saveCode()
    {
        PinCodeTable::write($this);
    }
}