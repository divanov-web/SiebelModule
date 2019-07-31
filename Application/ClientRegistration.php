<?php

namespace Taber\Siebel\Application;

use Bitrix\Main\Diag\Debug;
use Taber\Siebel\Utils\Card;
use Taber\Siebel\Utils\Phone;
use Taber\Siebel\Utils\WebClient;

/**
 * Class ClientRegistration
 * @package Taber\Siebel\Application
 */
class ClientRegistration
{
    /**
     * @var WebClient
     */
    private $_webClient;

    /**
     * ClientRegistration constructor.
     * @param WebClient $webClient
     */
    public function __construct(WebClient $webClient)
    {
        $this->_webClient = $webClient;
    }

    /**
     * @return int|null
     * @throws \Bitrix\Main\ObjectException
     */
    public function addWebClient()
    {
        $userId = $this->_webClient->save();
        return $userId;
    }

    /**
     * @return mixed
     */
    public function getExistingWebUser()
    {
        $user = \CUser::GetByLogin($this->_webClient->getPersonalPhone());
        return $user->fetch()["ID"];
    }

    /**
     * записываем siebelId вебклиенту
     */
    public function updateWebClient()
    {
        $webUser = new \CUser();
        $webUser->Update($this->_webClient->getWebClientId(), [
            "UF_SIEBEL_ID" => $this->_webClient->getSiebelId(),
        ]);
    }

    /**
     * @return string
     * @throws \Bitrix\Main\ObjectException
     * @throws \ReflectionException
     * @throws \SoapFault
     * @throws \Taber\Siebel\SiebelException\SiebelErrorRequestException
     * @throws \Taber\Siebel\SiebelException\SiebelErrorResponceException
     * @throws \Taber\Siebel\SiebelException\SiebelRequiredFieldException
     * @throws \Taber\Siebel\SiebelException\SiebelWrongDataException
     */
    public function createSiebelUser(): string
    {
        $userPhone = new Phone($this->_webClient->getPersonalPhone());
        $createUpdateFields = [
            "FirstName" => $this->_webClient->getName(),
            "LastName" => $this->_webClient->getLastName(),
            "WebId" => $this->_webClient->getWebClientId(),
            "SiebelId" => '',
            "BirthDay" => $this->_webClient->getBirthDay(),
            "Sex" => $this->_webClient->getSex(),
            "ListOfEmail" => [
                [
                    "Email" => $this->_webClient->getEmail(),
                    "EmailNotify" => $this->_webClient->getAgreePersonal() ? 'Y' : 'N'
                ],
            ],
            "ListOfPhone" => [
                [
                    "PhoneNumber" => $userPhone->getPhone(),
                    "PhoneConfirmedDate" => date('d.m.Y H:i', time()),
                    "SMSNotify" => $this->_webClient->getAgreePersonal() ? 'Y' : 'N'

                ],
            ]
        ];
        //Если этот пользователь только для сохранения в зибель, то добавляем в запрос карту
        if($this->_webClient->isOnlySiebelUser()) {
            $createUpdateFields['CardNumber'] = $this->_webClient->getCardNumber();
        }
        $createUpdateClient = new \Taber\Siebel\Methods\CreateUpdateClient($createUpdateFields);
        $createUpdateClient->execute();
        $siebelId = $createUpdateClient->getSiebelId();
        if(!$this->_webClient->isOnlySiebelUser())
            self::updateWebUserCard($createUpdateClient);
        return $siebelId;
    }

    /**
     * Затираем нашу карту клиента, и пишем нам карту клиента из siebel
     * если нет пластиковой карты в списке, то записываем к нам активную виртуальную карту
     * @param $createUpdateClient
     * @throws \Bitrix\Main\ObjectException
     */
    public function updateWebUserCard($createUpdateClient)
    {
        $clientCards = $createUpdateClient->getClientActiveCard();
        foreach ($clientCards as $clientCard) {
            // todo: переделать на обьекты Card
            if ($clientCard["Status"] == Card::CARD_STATUS_ACTIVE && $clientCard["CardType"] == Card::CARD_TYPE_PLASTIC) {
                $this->_webClient->setCardNumber($clientCard["Barcode"]);
                $this->_webClient->save();
                break;
            } elseif ($clientCard["Status"] == Card::CARD_STATUS_ACTIVE && $clientCard["CardType"] == Card::CARD_TYPE_VIRTUAL) {
                $this->_webClient->setCardNumber($clientCard["Barcode"]);
                $this->_webClient->save();
            }
        }
    }

    /**
     * @return string
     * @throws \Bitrix\Main\ObjectException
     * @throws \ReflectionException
     * @throws \SoapFault
     * @throws \Taber\Siebel\SiebelException\SiebelErrorRequestException
     * @throws \Taber\Siebel\SiebelException\SiebelErrorResponceException
     * @throws \Taber\Siebel\SiebelException\SiebelRequiredFieldException
     * @throws \Taber\Siebel\SiebelException\SiebelWrongDataException
     */
    public function updateSiebelUser(): string
    {
        $getClient = [
            "SiebelId" => $this->_webClient->getSiebelId(),
            "CardNumber" => false
        ];
        $getClientInfo = new \Taber\Siebel\Methods\GetClientInfo($getClient);
        $getClientInfo->execute();
        $userPhone = (new Phone($this->_webClient->getPersonalPhone()))->getPhone();
        $createUpdateFields = [
            "WebId" => $this->_webClient->getWebClientId(),
            "SiebelId" => $this->_webClient->getSiebelId(),
            "ListOfEmail" => [
                [
                    "Email" => $this->_webClient->getEmail(),
                    "EmailNotify" => $this->_webClient->getAgreePersonal() ? 'Y' : 'N'
                ],
            ],
            "ListOfPhone" => [
                [
                    "PhoneNumber" => $userPhone,
                    "PhoneConfirmedDate" => date('d.m.Y H:i', time()),
                    "SMSNotify" => $this->_webClient->getAgreePersonal() ? 'Y' : 'N'

                ],
            ]
        ];
        $createUpdateClient = new \Taber\Siebel\Methods\CreateUpdateClient($createUpdateFields);
        $createUpdateClient->execute();
        $siebelId = $createUpdateClient->getSiebelId();
        self::updateWebUserCard($createUpdateClient);
        return $siebelId ?? $this->_webClient->getSiebelId();
    }

    /**
     * @param $siebelId
     * @param $phone
     */
    public function activateWebUser($siebelId, $phone)
    {
        $this->_webClient->activateWebClient($siebelId, $phone);
    }
}