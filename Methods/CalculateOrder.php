<?php
/**
 * Created by PhpStorm.
 * User: d.ivanov
 * Date: 06.06.2018
 * Time: 14:48
 */

namespace Taber\Siebel\Methods;

/**
 * Class CalculateOrder
 * @package Taber\Siebel\Methods
 */
class CalculateOrder extends OrderSoapMethod
{
    private $listGiftDisc = [];

    private $listGift = [];

	private $originalItems = [];

	private $originalDiscs = [];

	private $originalDiscountsGroups = [];

	private $originalGiftDiscs = [];

	private $originalGifts = [];

    private $originalCards = [];


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
                        "ChequeId" => ["require", "auto"], //03.12.2018 сделать копией SiebelId, это обязательное поле для зибеля
						"ChequeNumber" => false,
						"ShopIndex" => false,
						"ShiftNumber" => false,
						"CashNumber" => false,
						"ChequeOpenDate" => false,
						"CashierNumber" => false,
						"ChequeSum" => false,
						"ListOfOrderItem" => ["require", "additional" => ["OrderItem" =>
							["hideOnMap", "repeat" =>
								[
									"PosNumber" => ["require"],
									"Item" => ["require"],
									"Quant" => ["require"],
									"PriceDisc" => false,
									"BasePrice" => ["require"],
									"PriceDiscSum" => false,
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
									"Type" => false
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

    public function getListOfGiftDisc()
    {
        return $this->listGiftDisc;
    }

    public function getListOfGift()
    {
        return $this->listGift;
    }

	/**
	 * @return array - массив оригинальных товаров из зибеля
	 */
	public function getOriginalItems(): array
	{
		return $this->originalItems;
	}

	/**
	 * @return array - массив оригинальных скидок из зибеля
	 */
	public function getOriginalDiscs(): array
	{
		return $this->originalDiscs;
	}

	/**
	 * @return array - массив оригинальных групп скидок из зибеля
	 */
	public function getOriginalDiscountsGroups(): array
	{
		return $this->originalDiscountsGroups;
	}

	/**
	 * @return array
	 */
	public function getOriginalGiftDiscs(): array
	{
		return $this->originalGiftDiscs;
	}

	/**
	 * @return array
	 */
	public function getOriginalGifts(): array
	{
		return $this->originalGifts;
	}

    /**
     * @return array
     */
    public function getOriginalCards(): array
    {
        return $this->originalCards;
    }


    public function execute() {
        parent::execute();
        $arAllGifts = [];
        $arGiftsDisc = [];
		foreach($this->repairItemArray($this->getOutput()["OrderHeader"]["ListOfGiftDisc"]["GiftDisc"]) as $item) {
			$arGiftsDisc[$item["GiftDiscNumber"]] = $item;
		}
        foreach($this->repairItemArray($this->getOutput()["OrderHeader"]["ListOfGift"]["Gift"]) as $item) {
			$item["GiftDiscId"] = $arGiftsDisc[$item['GiftDiscNumber']]["GiftDiscId"];
        	$arAllGifts[] = $item;
        }
        //Собираем массив из чистых товров зибеля
		$this->originalItems = $this->repairItemArray($this->getOutput()["OrderHeader"]["ListOfOrderItem"]["OrderItem"]) ?? [];
		//оригинальные скидки зибеля
		$this->originalDiscs = $this->repairItemArray($this->getOutput()["OrderHeader"]["ListOfDisc"]["Disc"]) ?? [];
		//оригинальные группы скидок зибеля
		$this->originalDiscountsGroups = $this->repairItemArray($this->getOutput()["OrderHeader"]["ListOfDiscountGroups"]["DiscountGroups"]) ?? [];
		//оригинальные подарочные акции
		$this->originalGiftDiscs = $this->repairItemArray($this->getOutput()["OrderHeader"]["ListOfGiftDisc"]["GiftDisc"]) ?? [];
		//оригинальный список подарков
		$this->originalGifts = $this->repairItemArray($this->getOutput()["OrderHeader"]["ListOfGift"]["Gift"]) ?? [];
        //оригинальный список применённых карты
        $this->originalCards = $this->repairItemArray($this->getOutput()["OrderHeader"]["ListOfCard"]["Card"]) ?? [];

        $this->listGiftDisc = !empty($arGiftsDisc) ? array_values($arGiftsDisc) : false;
        $this->listGift = !empty($arAllGifts) ? array_values($arAllGifts) : false;
    }

	/**
	 * Возвращает подготовленный список товаров корзины
	 * @return array
	 */
	public function getListOfOrderItem()
	{
		$arDics = array();
		$arItems = array();

		foreach($this->repairItemArray($this->getOutput()["OrderHeader"]["ListOfDisc"]["Disc"]) as $disc) {
			if($disc["DiscId"] != $this->roundDiscountID) { //Кроме скидок на округление
				$arDics[$disc["ItemNumber"]][] = $disc;
			}
		}

		foreach($this->repairItemArray($this->getOutput()["OrderHeader"]["ListOfOrderItem"]["OrderItem"]) as $item) {
			foreach($arDics[$item["PosNumber"]] as $itemDisc) {
				$item["Discount"][] = $itemDisc;
				$item["Discount_str"] .= $itemDisc["DiscId"];
			}

			$basket_key = $item["Item"]  . "_" . $item["PriceDisc"]; //группируем товары по этому ключу.
			if(empty($arItems[$basket_key])) {
				$arItems[$basket_key] = $item;
			} else {
				$arItems[$basket_key]["Quant"]++;
				$arItems[$basket_key]["PriceDiscSum"] += $item["PriceDiscSum"];
			}
		}
		return array_values($arItems);
	}
}