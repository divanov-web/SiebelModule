<?php
/**
 * Created by PhpStorm.
 * User: a.vartan
 * Date: 20.07.2018
 * Time: 10:46
 */

namespace Taber\Siebel\Methods;


class CreateUpdateClient extends AuthSoapMethod
{
    private $client = [];
    private $siebelId;
    private $clientInfo;
    /**
     * @var array
     * Описание возможных аттрибутов полей:
     * require (value) - поле обязательно для заполнения и отправки в Siebel
     * default (key => value) - значение по умолчанию
     * subarray (key => array) - подмассив, будет сформирован как подуровень в xml
     * additional(value) - значение этого поля будет взято из массива $arAvailableAdditionalParams с таким же ключом. Используется только для набора элементов, цикл по товарам и тп
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
            "default" => "podrygka.ru"  // "Siebel" не создает виртуальную карту
        ],
        "InputIO" => [
            "hideOnMap",
            "subarray" => [
                "Client" => [
                    "hideOnMap",
                    "subarray" => [
                        "SiebelId" => false,
                        "CardNumber" => false,
                        "ClientInfo" => [
                            "hideOnMap", "subarray" => [
                                "WebId" => false,
                                "FirstName" => false,
                                "LastName" => false,
                                "MiddleName" => false,
                                "BirthDay" => false,
                                "BirthDayLastUpdate" => false,
                                "Sex" => false,
                                "ToneSkin" => false,
                                "EyeСolor" => false,
                                "TypeSkin" => false,
                                "NotifyType" => false,
//                                "ClientType" => false,
                                "ClientType" => [
                                    "require",
                                    "default" => "Client"  // для создания клиента с корректной скидкой
                                ],
                                "ListOfEmail" => [
                                    "require", "additional" => [
                                        "Email" => ["hideOnMap", "repeat" =>
                                            [
                                                "Email" => ["required"],
                                                "EmailConfirmedDate" => false,
                                                "EmailNotify" => false,
                                                "EmailSource" => [
                                                    "require",
                                                    "default" => "podrygka.ru"
                                                ],
                                            ]
                                        ]
                                    ]
                                ],
                                "ListOfPhone" => [
                                    "require", "additional" => [
                                        "Phone" => ["hideOnMap", "repeat" =>
                                            [
                                                "PhoneNumber" => ["required"],
                                                "PhoneConfirmedDate" => false,
                                                "SMSNotify" => false,
                                            ]
                                        ]
                                    ]
                                ],
                                "ListOfMobileApp" => [
                                    "additional" => [
                                        "MobileApp" => ["hideOnMap", "repeat" =>
                                            [
                                                "MobileApp" => false,
                                            ]
                                        ]
                                    ]
                                ]
                            ]
                        ]
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
        if (array_key_exists('Client', $output)) {
            $this->client = $output["Client"];
            if (array_key_exists("SiebelId", $this->client)) {
                $this->siebelId = $this->client["SiebelId"];
            }
            if (array_key_exists("ClientInfo", $this->client)) {
                $this->clientInfo = $this->client["ClientInfo"];
            }
        }
    }

    public function getSiebelId()
    {
        return $this->siebelId ?? 'не пришел';
    }

    public function getClientInfo()
    {
        return $this->clientInfo;
    }

    public function getClientActiveCard()
    {
        return $this->getClientInfo()["ListOfMember"]["Member"]["MemberInfo"]["ListOfCard"];
    }

    public function getClient()
    {
        return $this->client ?? [];
    }
}