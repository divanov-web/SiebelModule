<?php
/**
 * Created by PhpStorm.
 * User: a.vartan
 * Date: 20.07.2018
 * Time: 11:11
 */

namespace Taber\Siebel\Utils;

use Bitrix\Main\Type\Date;
use Taber\Koshelek\Koshelek;

/**
 * Клиент хранящийся на нашем сервере
 * Class WebClient
 * @package Taber\Siebel\Utils
 */
class WebClient
{
    /**
     * @var int
     */
    private $webId;
    /**
     * @var string
     */
    private $personalPhone;
    /**
     * @var
     */
    private $agreePersonal;
    /**
     * @var
     */
    private $agreeSms;
    /**
     * @var string
     */
    private $login;
    /**
     * @var string
     */
    private $name;
    /**
     * @var string
     */
    private $lastName;
    /**
     * @var string
     */
    private $middleName;
    /**
     * @var string
     */
    private $password;
    /**
     * @var string
     */
    private $confirmPassword;
    /**
     * @var string
     */
    private $email;
    /**
     * @var string
     */
    private $cardNumber;
    /**
     * @var array
     */
    private $groupId;
    /**
     * @var string
     */
    private $active;
    /**
     * @var bool
     */
    private $phoneConfirmed;
    /**
     * @var string
     */
    private $lid;
    /**
     * @var string
     */
    private $sex;
    /**
     * @var array
     */
    private $siebelId;
    private $birthDay;

    private $webClient;

    /**
     * Этого пользователя заводим только в зибель, в битрикс сохранять нельзя
     * @var bool
     */
    private $onlySiebelUser = false;

    /**
     * WebClient constructor.
     * @param null $webId
     * @throws \Bitrix\Main\ArgumentNullException
     * @throws \Bitrix\Main\ArgumentOutOfRangeException
     * @throws \Bitrix\Main\SystemException
     */
    public function __construct($webId = null)
    {
        $this->webId = $webId ?? 0;
        self::setGroupId();
        self::setLid();
        if ($this->webId) {
            $this->webClient = self::readWebClient();
        } else {
            self::setActive();
        }
    }

    /**
     * @return mixed
     */
    public function getAgreePersonal()
    {
        return $this->agreePersonal;
    }

    /**
     * @param mixed $agreePersonal
     */
    public function setAgreePersonal($agreePersonal): void
    {
        $this->agreePersonal = $agreePersonal;
    }

    /**
     * @return mixed
     */
    public function getAgreeSms()
    {
        return $this->agreeSms;
    }

    /**
     * @param mixed $agreeSms
     */
    public function setAgreeSms($agreeSms): void
    {
        $this->agreeSms = $agreeSms;
    }

    /**
     * @param string $lastName
     */
    public function setLastName(string $lastName): void
    {
        $this->lastName = $lastName;
    }

    /**
     * @param string $middleName
     */
    public function setMiddleName(string $middleName): void
    {
        $this->middleName = $middleName;
    }

    /**
     * @return int
     */
    public function getWebId(): int
    {
        return $this->webId;
    }

    /**
     * @return string
     */
    public function getMiddleName()
    {
        return $this->middleName;
    }

    /**
     * @return string
     */
    public function getCardNumber()
    {
        return $this->cardNumber;
    }

    /**
     * @param string $cardNumber
     */
    public function setCardNumber(string $cardNumber)
    {
        $this->cardNumber = $cardNumber;
    }

    /**
     * @return bool
     */
    public function getPhoneConfirmed()
    {
        return $this->phoneConfirmed;
    }

    /**
     * @return bool
     */
    public function isOnlySiebelUser(): bool
    {
        return $this->onlySiebelUser;
    }

    /**
     * Устанавливаем флаг сохранения только в Зибель
     */
    public function doNotSaveBitrix() {
        $this->onlySiebelUser = true;
    }


    private function readWebClient()
    {
        $user = \CUser::GetByID($this->webId)->fetch();
        $this->name = $user["NAME"];
        $this->lastName = $user["LAST_NAME"];
        $this->agreeSms = $user["UF_GET_NEWS"];
        $this->agreePersonal = $user["UF_IS_AGREE"];
        $this->middleName = $user["SECOND_NAME"];
        $this->sex = $user["PERSONAL_GENDER"];
        $this->email = $user["EMAIL"];
        $this->active = $user["ACTIVE"];
        $this->personalPhone = $user["PERSONAL_PHONE"];
        $this->birthDay = $user["PERSONAL_BIRTHDAY"];
        $this->cardNumber = $user["UF_DISCOUNT_CARD"];
        $this->login = $this->personalPhone;
        $this->siebelId = $user["UF_SIEBEL_ID"] ?? '';
        $this->phoneConfirmed = ($user["UF_PHONE_CONFIRMED"] && $this->siebelId) ? true : false;
    }

    /**
     * @return mixed
     */
    public function getBirthDay()
    {
        return $this->birthDay;
    }

    /**
     * @param mixed $birthDay
     */
    public function setBirthDay($birthDay)
    {
        $this->birthDay = $birthDay;
    }

    /**
     * @return string
     */
    public function getSiebelId()
    {
        return $this->siebelId ?? '';
    }

    public function setSex($sex)
    {
        $this->sex = $sex;
    }

    public function getSex()
    {
        return $this->sex;
    }

    /**
     * @param $siebelId
     */
    public function setSiebelId($siebelId)
    {
        $this->siebelId = $siebelId;
    }

    /**
     * @return string
     */
    public function getLastName()
    {
        return $this->lastName;
    }

    /**
     * @param int $id
     */
    public function setWebClientId(int $id)
    {
        $this->webId = $id;
    }

    /**
     * @return int|null
     */
    public function getWebClientId()
    {
        return $this->webId;
    }

    /**
     * @param Phone $phone
     */
    public function setPersonalPhone(Phone $phone)
    {
        $this->personalPhone = $phone->getPhone();
        self::setLogin();
    }

    /**
     * @return string
     */
    public function getPersonalPhone()
    {
        return $this->personalPhone ?? '';
    }

    private function setLogin()
    {
        $this->login = $this->personalPhone;
    }

    /**
     * @return string
     */
    public function getLogin()
    {
        return $this->login;
    }

    /**
     * @param string $name
     */
    public function setName(string $name)
    {
        $this->name = $name;
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->name ?? '';
    }

    /**
     * @param string $password
     */
    public function setPassword(string $password)
    {
        $this->password = $password;
    }

    /**
     * @return string
     */
    public function getPassword()
    {
        return $this->password ?? '';
    }

    /**
     * @param string $confirmPassword
     */
    public function setConfirmPassword(string $confirmPassword)
    {
        $this->confirmPassword = $confirmPassword;
    }

    /**
     * @return string
     */
    public function getConfirmPassword()
    {
        return $this->confirmPassword ?? '';
    }

    /**
     * @param string $email
     */
    public function setEmail(string $email)
    {
        $this->email = $email;
    }

    /**
     * @return string
     */
    public function getEmail()
    {
        return $this->email ?? '';
    }

    /**
     * @throws \Bitrix\Main\ArgumentNullException
     * @throws \Bitrix\Main\ArgumentOutOfRangeException
     */
    public function setGroupId()
    {
        $sDefGroup = \Bitrix\Main\Config\Option::get('main', 'new_user_registration_def_group', '');
        $arGroupId = array();
        if (strlen($sDefGroup)) {
            $arGroupId = explode(',', $sDefGroup);
        }
        $userGroups = \CUser::GetUserGroup($this->webId);
        $this->groupId = array_merge($arGroupId, $userGroups);
    }

    /**
     * @return array
     */
    public function getGroupId()
    {
        return $this->groupId ?? [];
    }

    /**
     * @param string $active
     */
    public function setActive(string $active = "N")
    {
        $this->active = $active;
    }

    /**
     * @return string
     */
    public function getActive()
    {
        return $this->active;
    }

    /**
     * @throws \Bitrix\Main\SystemException
     */
    public function setLid()
    {
        $this->lid = \Bitrix\Main\Application::getInstance()->getContext()->getSite();
    }

    /**
     * @return string
     */
    public function getLid()
    {
        return $this->lid ?? '';
    }

    /**
     * @return int|null
     * @throws \Bitrix\Main\ObjectException
     */
    public function save()
    {
        $webUser = new \CUser();
        $webUserByPhone = $webUser::GetByLogin($this->getLogin())->Fetch(); // todo: проверка по телефону по мылу склеивание юзеров
        if ($webUserByPhone["ID"]) {
            $this->setWebClientId($webUserByPhone["ID"]);
        }
        $userPhone = new Phone($this->personalPhone);
        $webUserFields = [
            'UF_SIEBEL_UPDATE' => date('d.m.Y H:i'),
            'UF_IS_AGREE' => $this->agreePersonal,
            'UF_GET_NEWS' => $this->agreeSms,
            'LOGIN' => $userPhone->getPhone(),
            'PERSONAL_GENDER' => $this->getSex(),
            'NAME' => $this->getName(),
            'EMAIL' => $this->getEmail(),
            'GROUP_ID' => $this->getGroupId(),
            'LID' => $this->getLid(),
            'PERSONAL_PHONE' => $userPhone->getPhone(),
        ];
        if ($this->cardNumber) {
            $this->addClientCard();
        }
        $this->getSiebelId() ? $webUserFields["UF_SIEBEL_ID"] = $this->getSiebelId() : false;
        $this->cardNumber ? $webUserFields["UF_DISCOUNT_CARD"] = $this->cardNumber : false;
        $this->getBirthDay() ? $webUserFields["PERSONAL_BIRTHDAY"] = new Date($this->getBirthDay()) : false;
        $this->getPassword() ? $webUserFields["PASSWORD"] = $this->getPassword() : false;
        $this->getConfirmPassword() ? $webUserFields["CONFIRM_PASSWORD"] = $this->getConfirmPassword() : false;
        if ($this->getWebClientId()) {
            $webUser->Update($this->getWebClientId(), $webUserFields);
            $userId = $this->getWebClientId();
        } else {
            $webUserFields["ACTIVE"] = $this->getActive();
            $userId = intval($webUser->Add($webUserFields));
        }
        return $userId ?? 0;
    }

    private function addClientCard()
    {
        Koshelek::addCard($this->cardNumber);
    }

    /**
     * @param $siebelId
     * @param $phone
     */
    public function activateWebClient($siebelId, $phone)
    {
        $webUser = new \CUser();
        $webUser->Update($this->getWebClientId(), [
            "LOGIN" => $phone->getPhone(),
            "PERSONAL_PHONE" => $phone->getPhone(),
            "UF_SIEBEL_ID" => $siebelId,
            "UF_PHONE_CONFIRMED" => 1,
            "ACTIVE" => "Y"
        ]);
    }

    /**
     * @param $email
     * @return bool
     */
    public function checkUniqueEmail($email)
    {
        $user = \CUser::GetByLogin($email);
        $userId = $user->fetch()["ID"];
        if (!$userId){
            $user = \CUser::GetList(($by = "NAME"), ($order = "desc"), ["=EMAIL" => $email, "ACTIVE" => "Y"]);
            while ($arUser = $user->Fetch()) {
                $userId = $arUser["ID"];
                break;
                /**
                 * здесь можно сделать зачистку повторяющихся клиентов
                 */
            }
        }
        return $userId ? false : true;
    }
}