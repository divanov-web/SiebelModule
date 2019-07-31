<?php
/**
 * Created by PhpStorm.
 * User: a.vartan
 * Date: 20.07.2018
 * Time: 10:52
 */

namespace Taber\Siebel\Methods;

/**
 * Class GetClientInfo
 * @package Taber\Siebel\Methods
 */
class GetClientInfo extends AuthSoapMethod
{
    private $siebelId;
    private $clientInfo;
    private $clientPhones = [];
    private $clientEmails = [];
    private $clientMobileApp = [];
    private $clientMember = [];
    /**
     * @var array
     * Описание возможных аттрибутов полей:
     * require (value) - поле обязательно для заполнения и отправки в Siebel
     * default (key => value) - значение по умолчанию
     * subarray (key => array) - подмассив, будет сформирован как подуровень в xml
     * additional(value) - значение этого поля будет взято из массива $arAvaibleAdditionalParams с таким же ключом. Используется только для набора элементов, цикл по товарам и тп
     * repeat (key => array) - массив входящий значчений будет выведен в цикле
     * auto - поле генерируется автоматически в коде, не используется в коде, только чтобы правило было понятно
     * hideOnMap - этот параметр не будет выведен в отображении карты требуемых параметров (то есть его не надо передавать в метод отправки)
     * false - просто поле без особенностей
     */
    protected static $arAvailableParams = [
        "IntegrationId" => [
            "require",
            "auto",
            "hideOnMap"
        ],
        "SysType" => [
            "require",
            "default" => "podrygka.ru"
        ],
        "InputIO" => [
            "hideOnMap",
            "subarray" => [
                "Client" => [
                    "hideOnMap",
                    "subarray" => [
                        "SiebelId" => false,
                        "CardNumber" => false,
                    ]
                ]
            ]
        ]
    ];

    /**
     * получаем ответ от Siebel и записываем его в поля
     *
     * @throws \Taber\Siebel\SiebelException\SiebelErrorRequestException
     * @throws \Taber\Siebel\SiebelException\SiebelErrorResponceException
     */
    public function execute(): void
    {
        parent::execute();
        $output = $this->getOutput();
        $this->siebelId = $output["Client"]["SiebelId"];  // todo: не приходит WebId
        if (array_key_exists('ClientInfo', $output["Client"])) {
            $this->clientInfo = $output["Client"]["ClientInfo"];
            if (array_key_exists("ListOfEmail", $output["Client"]["ClientInfo"])) {
                if (array_key_exists("Email", $output["Client"]["ClientInfo"]["ListOfEmail"]) && !array_key_exists("Email", $output["Client"]["ClientInfo"]["ListOfEmail"]["Email"])) {
                    $this->clientEmails = $output["Client"]["ClientInfo"]["ListOfEmail"]["Email"];
                } else {
                    $this->clientEmails = $output["Client"]["ClientInfo"]["ListOfEmail"];
                }
            }
            if (array_key_exists("ListOfPhone", $output["Client"]["ClientInfo"])) {
                foreach ($output["Client"]["ClientInfo"]["ListOfPhone"] as $clientPhone) {
                    if ($clientPhone) {
                        $this->clientPhones[] = $clientPhone;
                    }
                }
            }
            if (array_key_exists("ListOfMobileApp", $output["Client"]["ClientInfo"])) {
                foreach (self::repairItemArray($output["Client"]["ClientInfo"]["ListOfMobileApp"]) as $clientMobileApp) {
                    if ($clientMobileApp["MobileApp"]) {
                        $this->clientMobileApp[] = $clientMobileApp["MobileApp"];
                    }
                }
            }
            if (array_key_exists("ListOfMember", $output["Client"]["ClientInfo"])) {
                foreach ($output["Client"]["ClientInfo"]["ListOfMember"] as $clientMember) {
                    if ($clientMember["MemberId"]) {
                        $this->clientMember[$clientMember["MemberId"]]["MemberInfo"] = $clientMember["MemberInfo"];
                        foreach ($clientMember["MemberInfo"]["ListOfCard"]["Card"] as $card) {
                            $this->clientMember[$clientMember["MemberId"]]["Cards"][] = $card;
                        }
                    }
                }
            }
        }
    }

    /**
     * @return array
     */
    public function getClientPhones()
    {
        return $this->clientPhones;
    }

    /**
     * @return array
     */
    public function getClientMember()
    {
        return current($this->clientMember)["MemberInfo"];
    }

    /**
     * @return mixed
     */
    public function getClientInfo()
    {
        return $this->clientInfo;
    }

    /**
     * @return array
     */
    public function getClientEmails()
    {
        return $this->clientEmails;
    }

    /**
     * @param $phone
     * @return bool
     */
    public function checkPhoneConfirmed($phone)
    {
        foreach ($this->clientPhones as $siebelPhone) {
            if ($siebelPhone["PhoneNumber"] == $phone && $siebelPhone["PhoneConfirmedDate"]) {
                return true;
            }
        }
        return false;
    }
}