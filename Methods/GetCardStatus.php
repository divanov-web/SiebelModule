<?php
/**
 * Created by PhpStorm.
 * User: d.ivanov
 * Date: 22.06.2018
 * Time: 16:05
 */

namespace Taber\Siebel\Methods;


class GetCardStatus extends OrderSoapMethod
{
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
        "IntegrationId" => ["require", "auto", "hideOnMap"],
        "SysType" => ["require", "default" => "Online"],
        "InputIO" => ["hideOnMap", "subarray" =>
            [
                "POS" => ["hideOnMap", "subarray" =>
                    [
                        "ChequeId" => false,
                        "ChequeNumber" => false,
                        "ShopIndex" => false,
                        "ShiftNumber" => false,
                        "CashNumber" => false,
                        "ChequeOpenDate" => false,
                        "CashierNumber" => false,
                        /*RequestType: 0 – добавление ПС в чек при продаже;
                        1 – запрос информации по ДК/купону/ПС по кнопке/в чеке;
                        2 - верификация карты по номеру телефона;
                        3 - запрос информации по ДК при выдаче в чеке.
                        4 - добавление ПС в чек при оплате.*/
                        "RequestType" => ["require"],
                        "Card" => ["hideOnMap", "subarray" =>
                            [
                                "CardNumber" => false,
                                "CardStatus" => false,
                                "PhoneNumber" => false,
                                "PINCode" => false,
                                "CardRate" => false,
                                "ValidCard" => false,
                                "CardItem" => false,
                            ]
                        ]
                    ]
                ]
            ]
        ]
    ];

    /**
     * Возвращает информацию о валидности карты в ответе от зибеля
     * @return bool
     */
    public function isCardValid() {
        return $this->getOutput()["POS"]["Card"]["ValidCard"] == 1;
    }
}