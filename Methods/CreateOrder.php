<?php
/**
 * Created by PhpStorm.
 * User: d.ivanov
 * Date: 06.06.2018
 * Time: 14:48
 */

namespace Taber\Siebel\Methods;

/**
 * Class CreateOrder
 * @package Taber\Siebel\Methods
 */
class CreateOrder extends CalculateOrder
{
    /**
     * Префикс к ID заказам в зибеле, конкатинируется с ID заказом битрикса
     */
    const CHEQUE_ID_PREFIX = "ORDER-";

    /**
     * Константы статусов заказа в зибеле
     */
    const CHEQUE_STATUS_WAITING_PAYMENT = 1;
    const CHEQUE_STATUS_WAITING_ACCEPT = 2;
    const CHEQUE_STATUS_ACCEPTED = 3;
    const CHEQUE_STATUS_SHIPPED = 4;
    const CHEQUE_STATUS_FINISHED = 5;
    const CHEQUE_STATUS_CANCELED = 6;

    /**
     * Константы статусов заказа в Битриксе
     */
    const BITRIX_STATUS_WAITING_PAYMENT = "N";
    const BITRIX_STATUS_WAITING_ACCEPT = "C";
    const BITRIX_STATUS_WAITING_AUTO_CALL = "H";
    const BITRIX_STATUS_ACCEPTED = "E";
    const BITRIX_STATUS_SHIPPED = "G";
    const BITRIX_STATUS_FINISHED = "F";
    const BITRIX_STATUS_CANCELED = "D";
    const BITRIX_STATUS_DELIVERY_CANCELED = "DC";

    /**
     * Константы методов оплаты
     */
    const PAYMENT_METHOD_OFFLINE = 0;
    const PAYMENT_METHOD_ONLINE = 1;

    /**
     * Константы способов оплаты
     */
    const PAYMENT_TYPE_CASH = 0; //0 – наличные;
    const PAYMENT_TYPE_ONLINE = 1;//1 – безналичные;
    const PAYMENT_TYPE_CERTIFICATE = 2;//2 - ПС;
    const PAYMENT_TYPE_COURIER = 3;//3 - курьерская компания.

    /**
     * Константы типы карт
     */
    const CARD_TYPE_DISCOUNT_CARD = 1; //1 - ДК;
    const CARD_TYPE_COUPON = 2; //2 – купон;
    const CARD_TYPE_SOCIAL_CARD = 3; //3 - соц. карта.

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
				"OrderHeader" => ["hideOnMap", "subarray" =>
					[
						"SiebelId" => false,
						"CardNumber" => false,
						"ChequeId" => ["require"],
                        "ChequeNumber" => ["require"],
                        "ShopIndex" => ["require"],
                        "ShiftNumber" => ["require"],
                        "CashNumber" => ["require"],
                        "ChequeOpenDate" => ["require"],
                        "CashierNumber" => false,
						"Operation" => ["require"],
						"ChequeStatus" => ["require"],
						"ChequeSum" => ["require"],
						"IssueCard" => false,
						//"PurchaseChequeId" => ["require"], //14.11.2018 Убрано по просбе филИТ. Данный параметр заполняется для возвратных чеков, которые присылает только касса.
						"FooterText" => false,
						"SlipText" => false,
						"OfflineCheque" => ["require"],
                        "AuthByPhoneNumber" => ["require"],
						"OfflineType" => false,
                        "ListOfOrderItem" => ["require", "additional" => ["OrderItem" =>
                            ["hideOnMap", "repeat" =>
                                [
                                    "PosNumber" => ["require"],
                                    "Item" => ["require"],
                                    "Quant" => ["require"],
                                    "PriceDisc" => ["require"],
                                    "BasePrice" => ["require"],
                                    "PriceDiscSum" => ["require"],
                                    "Barcode" => false,
                                    "CardBarcode" => false,
                                ]
                            ]
                        ]
                        ],
                        "ListOfCard" => ["additional" => [ "Card" =>
                            ["hideOnMap", "repeat" =>
                                [
                                    "BarcodeNumber" => ["require"],
                                    "Barcode" => ["require"],
                                    "Type" => ["require"]
                                ]
                            ]
                        ]],
                        "ListOfDisc" => ["additional" => [ "Disc" =>
                            ["hideOnMap", "repeat" =>
                                [
                                    "DiscNumber" => ["require"],
                                    "ItemNumber" => ["require"],
                                    "Quantity" => ["require"],
                                    "DiscId" => ["require"],
                                    "DiscSum" => false,
                                    "DiscPercent" => false,
                                    "DiscName" => false,
                                    "DiscBarcode" => false
                                ]
                            ]
                        ]],
						"ListOfDiscountGroups" => ["additional" => [ "DiscountGroups " =>
							["hideOnMap", "repeat" =>
								[
									"DiscId" => ["require"],
									"DiscName" => ["require"],
									"DiscSum" => ["require"],
									"DiscPercent" => ["require"],
								]
							]
						]],
                        "ListOfGiftDisc" => ["additional" => [ "GiftDisc" =>
                            ["hideOnMap", "repeat" =>
                                [
                                    "GiftDiscNumber" => ["require"],
                                    "GiftDiscId" => ["require"],
                                    "GiftDiscName" => ["require"],
                                    "PointSum" => false,
                                    "GiftQuantity" => false
                                ]
                            ]
                        ]],
                        "ListOfGift" => ["additional" => [ "Gift" =>
                            ["hideOnMap", "repeat" =>
                                [
                                    "GiftNumber" => ["require"],
                                    "GiftDiscNumber" => ["require"],
                                    "GiftItem" => ["require"],
                                    "GiftPrice" => ["require"]
                                ]
                            ]
                        ]],
                        "ListOfOrderPayment" => ["additional" => [ "OrderPayment" =>
                            ["hideOnMap", "repeat" =>
                                [
                                    "PayNumber" => ["require"],
                                    "PayType" => ["require"],
                                    "PaySum" => ["require"],
                                    "PayBarcode" => false
                                ]
                            ]
                        ]],
                        "ListOfDeliveryInfo" => ["additional" => [ "DeliveryInfo" =>
                            ["hideOnMap", "repeat" =>
                                [
                                    "DeliveryNumber" => ["require"],
                                    "DeliveryAddress" => false,
                                    "PhoneNumber" => false,
                                    "DeliveryDate" => false,
                                    "DeliveryMethod" => false,
                                    "PaymentMethod" => false,
                                    "RecipientName" => false
                                ]
                            ]
                        ]],
					]
				]
			]
		]
	];
}