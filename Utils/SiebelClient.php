<?php
/**
 * Created by PhpStorm.
 * User: a.vartan
 * Date: 20.07.2018
 * Time: 11:10
 */

namespace Taber\Siebel\Utils;

use Taber\Siebel\Methods\SoapMethod;
use Taber\Siebel\SiebelException\SiebelException;

/**
 * Клиент хранящийся на стороне Siebel
 * Class SiebelClient
 * @package Taber\Siebel\Utils
 */
class SiebelClient
{
    /**
     * @var int
     */
    private $siebelId;
    private $webId;
    private $siebelClientError;
    private $firstName;
    private $lastName;
    private $middleName;
    private $birthDay;
    private $birthDayLastUpdate;
    private $sex;
    private $toneSkin;
    private $notifyType;
    private $clientType;
    private $emails = [];
    private $phones = [];
    private $cards = [];
    private $discount;
    /**
     * @var array
     */
    private $siebelClient;

    /**
     * SiebelClient constructor.
     * @param null $siebelId
     */
    public function __construct($siebelId = null)
    {
        $this->siebelId = $siebelId ?? '';
        if ($this->siebelId) {
            $this->siebelClient = self::readSiebelClient();
        }
    }

    /**
     * @return mixed
     */
    public function getSex()
    {
        return $this->sex;
    }

    /**
     * @param mixed $lastName
     */
    public function setLastName($lastName): void
    {
        $this->lastName = $lastName;
    }

    /**
     * @param $webId
     */
    public function setWebId($webId)
    {
        $this->webId = $webId;
    }

    /**
     * @param $siebelId
     */
    public function setSiebelId($siebelId): void
    {
        $this->siebelId = $siebelId;
    }

    /**
     * @return mixed
     */
    public function getSiebelClientError()
    {
        return $this->siebelClientError;
    }

    /**
     * @param mixed $middleName
     */
    public function setMiddleName($middleName): void
    {
        $this->middleName = $middleName;
    }

    /**
     * @param $sex
     */
    public function setSex($sex)
    {
        $this->sex = $sex;
    }

    /**
     * @param mixed $firstName
     */
    public function setFirstName($firstName): void
    {
        $this->firstName = $firstName;
    }

    private function readSiebelClient()
    {
        if ($this->siebelId) {
            $getClient = [
                "SiebelId" => $this->siebelId,
                "CardNumber" => false
            ];
            $getClientInfo = new \Taber\Siebel\Methods\GetClientInfo($getClient);
            try {
                $getClientInfo->execute();
                $this->phones = $getClientInfo->getClientPhones();
                $this->emails = $getClientInfo->getClientEmails();
                $this->firstName = $getClientInfo->getClientInfo()["FirstName"];
                $this->lastName = $getClientInfo->getClientInfo()["LastName"];
                $this->middleName = $getClientInfo->getClientInfo()["MiddleName"];
                $this->birthDay = $getClientInfo->getClientInfo()["BirthDay"];
                $this->birthDayLastUpdate = $getClientInfo->getClientInfo()["BirthDayLastUpdate"];
                $this->sex = $getClientInfo->getClientInfo()["Sex"];
                $this->toneSkin = $getClientInfo->getClientInfo()["ToneSkin"];
                $this->notifyType = $getClientInfo->getClientInfo()["NotifyType"];
                $this->clientType = $getClientInfo->getClientInfo()["ClientType"];
                $this->setCards($getClientInfo);
                $this->setDiscount($getClientInfo);
            } catch (SiebelException $e) {
                $this->siebelId = '';
                $this->siebelClientError = $e->getMessage();
            }
        }
    }

    /**
     * @return array
     */
    public function getPhones(): array
    {
        return $this->phones;
    }

    /**
     * @param $phoneNumber
     * @return string
     */
    public function getPhoneConfirmed($phoneNumber){
        $confirmed = '';
        foreach ($this->phones as $phone){
            if ($phone["PhoneNumber"] == $phoneNumber){
                $confirmed = $phone["PhoneConfirmedDate"];
            }
        }
        return $confirmed;
    }

    /**
     * @return array
     */
    public function getEmails(): array
    {
        return $this->emails;
    }

    /**
     * @return string
     */
    public function getFirstName(): string
    {
        return $this->firstName;
    }

    /**
     * @return string
     */
    public function getLastName(): string
    {
        return $this->lastName;
    }

    /**
     * @return string
     */
    public function getMiddleName(): string
    {
        return $this->middleName;
    }

    /**
     * @return string
     */
    public function getBirthDay(): string
    {
        return $this->birthDay;
    }

    /**
     * @param $birthDay
     */
    public function setBirthDay($birthDay)
    {
        $this->birthDay = $birthDay;
    }

    /**
     * @return string
     */
    public function getSiebelId(): string
    {
        return $this->siebelId;
    }

    /**
     * @param $getClientInfo
     */
    private function setDiscount($getClientInfo): void
    {
        $this->discount = new Discount();
        $this->discount->setNextMonthDiscount($getClientInfo->getClientMember()["NextMonthDiscount"]);
        $this->discount->setCurrentMonthDiscount($getClientInfo->getClientMember()["CurrentMonthDiscount"]);
        $this->discount->setCurrentMonthSum($getClientInfo->getClientMember()["CurrentMonthSum"]);
        $this->discount->setLastMonthSum($getClientInfo->getClientMember()["LastMonthSum"]);
    }

    public function getDiscount()
    {
        return $this->discount;
    }

    public function getCards()
    {
        return $this->cards;
    }

    public function getBirthdayLastUpdate()
    {
        return $this->birthDayLastUpdate;
    }

    public function setPhones(WebClient $webClient)
    {
        $isInList = false;
        $userPhone = (new Phone($webClient->getPersonalPhone()))->getPhone();
        foreach ($this->phones as $key => $phone) {
            if ($phone["PhoneNumber"] == $userPhone){
                $isInList = true;
                $this->phones[$key] = [
                    "PhoneNumber" => $userPhone,
                    "PhoneConfirmedDate" => date('d.m.Y H:i', time()),
                    "SMSNotify" => $webClient->getAgreePersonal() ? 'Y' : 'N'
                ];
            }
        }
        if(!$isInList){
            $this->phones[] = [
                "PhoneNumber" => $userPhone,
                "PhoneConfirmedDate" => date('d.m.Y H:i', time()),
                "SMSNotify" => $webClient->getAgreePersonal() ? 'Y' : 'N'
            ];
        }
    }

    public function setEmails(WebClient $webClient)
    {
        $this->emails[] = [
            "Email" => $webClient->getEmail(),
            "EmailNotify" => $webClient->getAgreePersonal() ? 'Y' : 'N',
        ];
    }

    /**
     * @param $getClientInfo
     */
    private function setCards($getClientInfo): void
    {
        foreach (SoapMethod::repairItemArray($getClientInfo->getClientMember()["ListOfCard"]["Card"]) as $card) {
            $siebelCard = new Card();
            $siebelCard->setActivationPlace($card["ActivationPlace"]);
            $siebelCard->setActivationDate($card["ActivationDate"]);
            $siebelCard->setBarcode($card["Barcode"]);
            $siebelCard->setCardType($card["CardType"]);
            $siebelCard->setBlockingReason($card["BlockingReason"]);
            $siebelCard->setIssueDate($card["IssueDate"]);
            $siebelCard->setIssuePlace($card["IssuePlace"]);
            $siebelCard->setStatus($card["Status"]);
            $siebelCard->setStatusBeforeBlocking($card["StatusBeforeBlocking"]);
            $siebelCard->setChequeId($card["ChequeId"]);
            $this->cards[] = $siebelCard;
        }
    }

    public function save()
    {
        $createUpdate = [
            "FirstName" => $this->firstName,
            "LastName" => $this->lastName,
            "MiddleName" => $this->middleName,
            "WebId" => $this->webId,
            "SiebelId" => $this->siebelId,
            "BirthDay" => $this->birthDay,
            "Sex" => $this->sex,
        ];
        if (count($this->phones) > 0) {
            $createUpdate["ListOfPhone"] = $this->phones;
        }
        // узнать как будут синхронизироваться карты клиентов, какие данные мы
        // им отправляем при уже существующей карте пользователя в его ЛК
        /**
         * карты всегда апдейтятся из siebel к нам, т.е. верные данные по картам всегда приходят из siebel
         */
        $createUpdate["ListOfEmail"] = $this->getEmails();
        $createUpdate["ListOfPhone"] = $this->getPhones();
        $createUpdateClient = new \Taber\Siebel\Methods\CreateUpdateClient($createUpdate);
        $createUpdateClient->execute();
    }
}