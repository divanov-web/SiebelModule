<?php
/**
 * Created by PhpStorm.
 * User: a.vartan
 * Date: 20.07.2018
 * Time: 11:19
 */

namespace Taber\Siebel\Application;

use Taber\Siebel\Utils\Card;
use Taber\Siebel\Utils\Phone;
use Taber\Siebel\Utils\SiebelClient;
use Taber\Siebel\Utils\WebClient;

/**
 * Class ClientsMerger
 * @package Taber\Siebel\Application
 */
class ClientsMerger
{
    private $_webClient;
    private $_siebelClient;

    /**
     * ClientsMerger constructor.
     * @param WebClient $webClient
     * @param SiebelClient $siebelClient
     */
    public function __construct(WebClient $webClient, SiebelClient $siebelClient)
    {
        $this->_webClient = $webClient;
        $this->_siebelClient = $siebelClient;
    }

    /**
     * При несовпадении siebelId текущего пользователя и данных пришедших в queryPhone
     * вызываем метод mergeClients для мерджа клиентов в siebel
     * отправляем ему на вход siebelId из нашего ЛК и siebelId из queryPhone
     * @param $childSiebelId
     * @throws \ReflectionException
     * @throws \SoapFault
     * @throws \Taber\Siebel\SiebelException\SiebelErrorRequestException
     * @throws \Taber\Siebel\SiebelException\SiebelErrorResponceException
     * @throws \Taber\Siebel\SiebelException\SiebelRequiredFieldException
     * @throws \Taber\Siebel\SiebelException\SiebelWrongDataException
     */
    public function merge($childSiebelId)
    {
        $mergeClientsParams = [
            "ParentSiebelId" => $this->_webClient->getSiebelId(),  // siebelId из нашего ЛК
            "ChildSiebelId" => $childSiebelId,  // siebelId из QueryPhone
        ];
        $mergeClients = new \Taber\Siebel\Methods\MergeClients($mergeClientsParams);
        $mergeClients->execute();
    }

    /**
     * сохраняем на своей стороне свежие данные от siebel для нашего юзера с указанием даты обновления данных
     * при отсутствии ответа от siebel в личном кабинете выводятся именно данные из нашего ЛК с пометкой
     * "актуально на ДД.ММ.ГГГГ"
     * Так же используется для апдейта siebel юзера если он ответил "Да, это я"
     */
    public function synchronizeSiebelProfileWithWebProfile()
    {
        $this->_siebelClient->setWebId($this->_webClient->getWebId());
        $this->_siebelClient->setFirstName($this->_webClient->getName());
        $this->_siebelClient->setLastName($this->_webClient->getLastName());
        $this->_siebelClient->setBirthDay($this->_webClient->getBirthDay());
        $this->_siebelClient->setSex($this->_webClient->getSex());
        $this->_siebelClient->setMiddleName($this->_webClient->getMiddleName());
        $this->_siebelClient->setPhones($this->_webClient);
        $this->_siebelClient->setEmails($this->_webClient);
        $this->_siebelClient->setSiebelId($this->_webClient->getSiebelId());
        $this->_siebelClient->save();
    }

    public function synchronizeWebProfileWithSiebelProfile()
    {
        $this->_webClient->setName($this->_siebelClient->getFirstName());
        $this->_webClient->setLastName($this->_siebelClient->getLastName());
        $this->_webClient->setMiddleName($this->_siebelClient->getMiddleName());
        $this->_webClient->setBirthDay($this->_siebelClient->getBirthDay());
        $this->_webClient->setSex($this->_siebelClient->getSex());
        foreach ($this->_siebelClient->getCards() as $card) {
            if ($card->getStatus() == Card::CARD_STATUS_ACTIVE) {
                if ($card->getCardType() == Card::CARD_TYPE_PLASTIC) {
                    $this->_webClient->setCardNumber($card->getBarcode());
                    break;
                } elseif ($card->getCardType() == Card::CARD_TYPE_VIRTUAL) {
                    $this->_webClient->setCardNumber($card->getBarcode());
                }
            }
        }
        self::checkPhone();
        $this->_webClient->save();
    }

    public function checkPhone()
    {
        $webPhone = $this->_webClient->getPersonalPhone();
        $siebelPhone = $this->_siebelClient->getPhones()[0]["PhoneNumber"];
        if ($webPhone != $siebelPhone) {
            $this->_webClient->setPersonalPhone(new Phone($siebelPhone));
            /**
             * ищем юзеров с данным номером телефона и деактивируем их
             */
            $users = \CUser::GetList(($by = "id"), ($order = "desc"), ["ACTIVE" => "Y", "LOGIN" => $siebelPhone], []);
            $userToUpdate = new \CUser();
            while ($user = $users->GetNext()) {
                $id = $userToUpdate->update($user["ID"], ["ACTIVE" => "N", "LOGIN" => $siebelPhone . '.double.' . time()]);
                \Bitrix\Main\Diag\Debug::dumpToFile([$this->_webClient->getWebClientId(), $id], '', '/_log/user_update_siebel_phone.txt');
                break;
            }
        }
    }

    /**
     * при найденном юзере в siebel записываем ему предположительный siebelId
     */
    public function setExistingSiebelId()
    {
        $clientRegistration = new ClientRegistration($this->_webClient);
        $clientRegistration->updateWebClient();
    }

    /**
     * при отказе от существующего в siebel юзере ансетим его предположительный siebelId
     */
    public function unsetExistingSiebelId()
    {
        $this->_webClient->setSiebelId('');
        $clientRegistration = new ClientRegistration($this->_webClient);
        $clientRegistration->updateWebClient();
    }
}