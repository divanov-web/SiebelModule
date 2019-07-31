<?php
/**
 * Created by PhpStorm.
 * User: a.vartan
 * Date: 19.07.2018
 * Time: 10:19
 */

namespace Taber\Siebel\Application;


use Taber\Siebel\Log;
use Taber\Siebel\Methods\QueryPhone;
use Taber\Siebel\Utils\AuthorisationClientSmsSession;
use Taber\Siebel\Utils\Phone;
use Taber\Siebel\Utils\PinCode;
use Taber\Siebel\Utils\SiebelClient;
use Taber\Siebel\Utils\WebClient;

/**
 * Class AuthorizationPhoneChecker
 * @package Taber\Siebel\Utils
 */
class AuthorizationPhoneChecker
{
    private $phone;
    private $_webClient;
    private $_queryPhone;
    private $_smsSession;
    /**
     * сколько смс в день может запросить пользователь
     */
    const DAY_SMS_COUNT = 5;
    /**
     * сколько раз пользователь может запросить проверку одного PIN кода
     */
    const SMS_VALIDATION_TRY = 10;
    /**
     * интервал запроса пользователем нового смс в секундах
     */
    const SMS_RESEND_TIME_DELTA = 30;

    public function __construct(Phone $phone, WebClient $webClient)
    {
        $this->phone = $phone;
        $this->_webClient = $webClient;
    }

    /**
     * выслать клиенту пин код
     * @return PinCode
     * @throws \Exception
     */
    public function sendPinCodeToClient(): PinCode
    {
        $queryPhoneParams = [
            "SiebelId" => false,
            "PhoneNumber" => $this->phone->getPhone(), // format +79998886655
            "PINCode" => false,
            "PhoneConfirmedData" => false,
        ];
        $this->_queryPhone = new QueryPhone($queryPhoneParams);
        $this->_smsSession = new AuthorisationClientSmsSession($this->_webClient);
        $this->_queryPhone->execute();
        $smsCode = $this->_queryPhone->getPinCodeValue();
        if ($smsCode) {
            $this->_smsSession->updateSmsCount();
            $pinCode = new PinCode($this->_webClient, $smsCode);
        } else {
            throw new \Exception();  // todo: исключение код не выслан
        }
        return $pinCode;
    }

    public function getExistingSiebelClient(): SiebelClient
    {
        if ($this->_queryPhone->getSiebelID()) {
            return new SiebelClient($this->_queryPhone->getSiebelID());
        } else {
            return new SiebelClient();
        }
    }

    public function checkExistingClient()
    {
        $shortClientInfo = $this->_queryPhone->getShortClientInfo();
        if ($shortClientInfo) {
            $html = '<div class="modal-container js-modal-block order-auth-modal" style="display: none">
            <div class="modal-container-content">
                <div class="form-modal form-modal--auth">
                    <p class="order-auth-modal__text">Найден клиент с данным номером телефона:</p>
                    <p class="order-auth-modal__text">' . $shortClientInfo["FirstName"] . ' ' . $shortClientInfo["LastName"] . ' пол: ж., 23.02.1988 г., <br/>Последняя покупка 01.06.2017 г. в магазине "Подружка" по адресу:<br/>Москва, Чертановская ул., д. 32, стр.1.</p>
                    <p class="order-auth-modal__text">Ваши дисконтные карты:  **********6767,  **********6767</p>
                    <div class="order-auth-modal__fields _half">
                        <div class="form-modal__footer">
                            <input type="button" value="Нет, это не мои данные" class="button button--default order-auth-modal__recover"/>
                        </div>
                        <div class="form-modal__footer">
                            <input type="button" value="Да, это мои данные" class="button button--default order-auth-modal__recover _dark"/>
                        </div>
                    </div>
                </div>
            </div>
        </div>';
        }
        return $html ?? '';
    }

    /**
     * @return bool
     */
    public function isClientExist()
    {
        return $this->_queryPhone->getShortClientInfo() ? true : false;
    }

    /**
     * @param PinCode $pinCode
     * @param $code
     * @return bool
     */
    public function checkPinCode(PinCode $pinCode, $code)
    {
        $correctCode = false;
        if ($pinCode->getCode()) {
            if ($pinCode->getCode() == $code) {
                $correctCode = true;
            } else {
                Log::addCodeValidationToLogFile($pinCode, $code);
            }
        }
//        $this->_smsSession->updateSmsInsertCount();  // todo проверку
        return $correctCode;
    }

    public function getLastPurchaseDate()
    {
        return $this->_queryPhone->getLastPurchaseDate();
    }

    public function getLastPurchasePlace()
    {
        return $this->_queryPhone->getLastPurchasePlace();
    }

    public function getUseWallet()
    {
        return $this->_queryPhone->getUseWallet();
    }

    public function getFirstName()
    {
        return $this->_queryPhone->getFirstName();
    }

    public function getLastName()
    {
        return $this->_queryPhone->getLastName();
    }

    public function getSex()
    {
        return $this->_queryPhone->getSex();
    }

    public function getBirthDay()
    {
        return $this->_queryPhone->getBirthDay();
    }

    public function getSiebelId()
    {
        return $this->_queryPhone->getSiebelId();
    }
}