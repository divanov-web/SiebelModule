<?php
/**
 * Created by PhpStorm.
 * User: a.vartan
 * Date: 19.07.2018
 * Time: 10:10
 */

namespace Taber\Siebel\Methods;

/**
 * Class QueryPhone
 * @package Taber\Siebel\Methods
 */
class QueryPhone extends AuthSoapMethod
{
    /**
     * @var string
     */
    private $phone;
    /**
     * @var array массив сопоставленных id Siebel и нашей БД
     */
    private $client = [];
    /**
     * @var array краткое описание клиента(имя фамилия ..)
     */
    private $shortClientInfo = [];
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
                "Phone" => [
                    "hideOnMap",
                    "subarray" => [
                        "SiebelId" => false,
                        "PhoneNumber" => ["require"], // format +79998886655
                        "PINCode" => false,
                        "PhoneConfirmedData" => false,  // format '09.09.2009 00:00:00'
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
        $this->phone = $output["Phone"];
        if (array_key_exists('Client', $output["Phone"])) {
            $this->client = $output["Phone"]["Client"];
            if (array_key_exists("ShortClientInfo", $output["Phone"]["Client"])) {
                $this->shortClientInfo = $output["Phone"]["Client"]["ShortClientInfo"];
            }
        }
    }

    /**
     * проверка введеного клиентом PIN кода
     *
     * @return int
     */
    public function getPinCodeValue(): int
    {
        return $this->phone["PINCode"];
    }

    /**
     * Если в ответе вернулось непустое поле даты валидации телефона
     * то данный телефон уже проверен в siebel
     *
     * @return bool
     */
    public function checkPhoneConfirmed(): bool
    {
        $phoneConfirmed = $this->phone["PhoneConfirmedData"] ? true : false;
        return $phoneConfirmed;
    }

    /**
     * @return string
     */
    public function getSiebelID(): string
    {
        return $this->client["SiebelId"] ?? '';
    }

    /**
     * @return string
     */
    public function getWebID(): string
    {
        return $this->client["WebId"] ?? '';
    }

    /**
     * @return string
     */
    public function getPhoneNumber(): string
    {
        return $this->phone["PhoneNumber"];
    }

    /**
     * @return array
     */
    public function getClient(): array
    {
        return $this->client ?? [];
    }

    /**
     * @return array
     */
    public function getShortClientInfo(): array
    {
        return $this->shortClientInfo ?? [];
    }

    /**
     * @return string
     */
    public function getLastPurchaseDate()
    {
        return $this->shortClientInfo["LastPurchaseDate"];
    }

    /**
     * @return string
     */
    public function getUseWallet()
    {
        return $this->shortClientInfo["UseWallet"];
    }

    /**
     * @return string
     */
    public function getLastPurchasePlace()
    {
        return $this->shortClientInfo["LastPurchasePlace"];
    }

    public function getFirstName()
    {
        return $this->shortClientInfo["FirstName"];
    }

    public function getLastName()
    {
        return $this->shortClientInfo["LastName"];
    }

    public function getSex()
    {
        return $this->shortClientInfo["Sex"];
    }

    public function getBirthDay()
    {
        return $this->shortClientInfo["BirthDay"];
    }
}