<?php
/**
 * Created by PhpStorm.
 * User: d.ivanov
 * Date: 02.07.2018
 * Time: 15:42
 */

namespace Taber\Siebel\Application;

use Taber\Siebel\Methods\CalculateOrder;
use Taber\Siebel\Methods\CreateOrder;
use Taber\Siebel\SiebelException\BasketUpdateErrorException;

use Girlfriend\Models\Eshop\OrderComponentUtils;

/**
 * Обработка корзины заказов для методов Siebel CalculateOrder и CreateOrder
 * Class Basket
 * @package Taber\Siebel\Utils
 */
class Basket
{
	const shopIndex = 323; //323 - интернет-магазин 115 - Один из магазинов Москвы
	/**
	 * @var array - входной массив товаров из битрикс
	 */
	private $arBasketItems = [];

	private $soapApi = null;

    /**
     * @var string дисконтная карта
     */
    private $discountCard = "";

    /**
     * @var string ID пользователя в Зибеле
     */
    private $siebelId = "";
	/**
	 * @var array - подготовленный массив товаров для отправки в Siebel
	 */
	private $arSiebelItems = [];
	/**
	 * @var array - полученный массив товаров из Siebel
	 */
	private $arResultItems = [];

	/**
	 * @var array - массив товаров из Siebel совмещенный с битрикс для вывода в шаблон
	 */
	private $arPrintItems = [];

	/**
	 * @var array - Масив дисконтных карт в формате зибеля
	 */
	private $arSiebelCards = [];

	/**
	 * @var array - массив доставок в формате зибеля
	 */
	private $arSiebelDelivery = [];

	/**
	 * @var array - Массив выбранных подарков, которые хранятся в корзине битрикса
	 */
	private $arSelectedGift = [];

	/**
	 * @var array - Массив выбранных подарков в формате зибеля
	 */
	private $selectedGiftForSiebel = [];

	/**
	 * Для заполнения массива _SUMMARY_DATA_ в бывших компонентах ADV
	 * @var array
	 */
	private $arSummaryData = [];

	/**
	 * Массив подарочных групп Siebel, полученных из акциий
	 * @var array
	 */
	private $arResultGiftDisc = [];

    /**
     * @var int - ID текущей доставки
     */
    private $deliveryId;

	/**
	 * Название для позиции "Доставка" для передачи в зибель. По этому полю находим доставку, получая данные обратно из Зибеля
	 */
	const DELIVERY_ITEM_NAME = "DELIVERY";

    /**
     * @return array
     */
    public function getArBasketItems(): array
    {
        return $this->arBasketItems;
    }

    /**
     * @param array $arBasketItems
     */
    public function setArBasketItems(array $arBasketItems): void
    {
        $this->arBasketItems = $arBasketItems;
    }

	public function __construct(array $basketItems, $arDeliveryInfo = [], $cards = [])
	{
		$this->arBasketItems = $basketItems;
		$this->arSiebelItems = $this->prepareItems($basketItems);
		if($GLOBALS['USER']->IsAuthorized()) {
		    $user = \Taber\User\Utils::getUserById($GLOBALS['USER']->getId(), ["UF_DISCOUNT_CARD", "UF_SIEBEL_ID"]);
            $this->siebelId = $user["UF_SIEBEL_ID"];
            $this->discountCard = $user["UF_DISCOUNT_CARD"];
            if(strlen($this->discountCard) > 0) {
                $cards[] = [
                    "BARCODE" => $this->discountCard,
                    "TYPE" => CreateOrder::CARD_TYPE_DISCOUNT_CARD
                ];
            }
		}
		$this->arSiebelCards = $this->prepareCards($cards);

		if(!empty($arDeliveryInfo["DELIVERY"]['_TARIFF_DATA_'])) {
            $deliveryConfig = $arDeliveryInfo["DELIVERY"]['_gf_delivery_handler_instance_']->getConfigValues();
            $this->deliveryId = $deliveryConfig["SUPERMAG"]["SUPERMAG_ARTICLE"];

            $this->arSiebelDelivery = $this->prepareDelivery($arDeliveryInfo);
        }
	}

	/*public function getOrderItems(array $basketItems) {
		$result = new static($basketItems, false, false);
		$result->reloadSiebelOrder();
		return $result;
	}*/


	public function getSiebelItems()
	{
		return $this->arSiebelItems;
	}

	public function getSiebelCards()
	{
		return $this->arSiebelCards;
	}

	public function getPrintItems()
	{
		return $this->arPrintItems;
	}

	public function getSiebelDelivery()
	{
		return $this->arSiebelDelivery;
	}

	public function getSummaryData()
	{
		return $this->arSummaryData;
	}

	/**
	 * @return array
	 */
	public function getSelectedGift(): array
	{
		return $this->arSelectedGift;
	}

	public function getResultGiftDisc()
	{
		$arGiftDisc = $this->arResultGiftDisc;
		if(!empty($this->getResultGift())) {

			$giftsArticle = [];
			$arNewGift = [];
			foreach ($this->getResultGift() as $gift) {
				$giftsArticle[] = $gift["GiftItem"];
			}
			$arElementByXmlId = [];
            $dbItems = \CIBlockElement::GetList(
                [],
                array(
                    'IBLOCK_ID' => 12,
                    'XML_ID' => $giftsArticle,
                    'ACTIVE' => 'Y',
                    "PROPERTY_PRODUCT.ACTIVE" => 'Y',
                    '>CATALOG_QUANTITY' => 0
                ),
                false,
                false,
                array(
                    'ID', 'NAME', 'IBLOCK_ID', 'XML_ID', 'CATALOG_QUANTITY',
                    "PROPERTY_PRODUCT.ACTIVE"
                )
            );
            while ($arItem = $dbItems->Fetch()) {
				$arElementByXmlId[$arItem["XML_ID"]] = $arItem["ID"];
			}

			foreach ($this->getResultGift() as $gift) {
				$gift["ELEMENT_ID"] = $arElementByXmlId[$gift["GiftItem"]];
				$arNewGift[$gift["GiftDiscNumber"]][] = $gift;
			}

			//Достаём из сессии отменённый выбор автоподарков
            $deletedGiftDisc = OrderComponentUtils::getSessionData("DELETED_GIFT_DISC") ?? [];

			foreach($arGiftDisc as $key => &$giftDisc) {
			    $haveSelectedgift = false;
				//Ищем среди подарков тот, что выбран
				foreach($arNewGift[$giftDisc["GiftDiscNumber"]] as &$gift) {
					//помечаем подарок как выбранный, сам подарок получаем из корзины
					if($this->arSelectedGift[$giftDisc["GiftDiscId"]]["SIEBEL_GIFT_ID"] == $gift["GiftItem"]) {
						$gift["selected"] = true;
						$gift["basketElementId"] = $this->arSelectedGift[$giftDisc["GiftDiscId"]]["BASKET_ELEMENT_ID"];
					//Первый попавшийся подарок делаем выбранным. $deletedGiftDisc - не делаем автоподстановку подарка для этих акций
					} elseif(empty($this->arSelectedGift) && !$haveSelectedgift && !in_array($giftDisc["GiftDiscId"], $deletedGiftDisc)) {
                        $gift["selected"] = true;
                        $haveSelectedgift = true;
                        //Добавляем подарок. Надо учитывать, что подарок добавляется уже после запроса к зибелю и обработки подарков
                        //поэтому в CreateOrder он не попадёт на создании заказа. Так то он там и не особо нужен.
                        if(intval($gift["ELEMENT_ID"]) > 0) { //ELEMENT_ID есть только у активных подарков
                            \Taber\Siebel\Application\GiftApp::addGiftToBasket($gift["ELEMENT_ID"], $giftDisc["GiftDiscId"]);
                        }
                    }

					if(intval($gift["ELEMENT_ID"]) > 0){
                        $giftDisc["have_gift"] = true;
                    }
				}
				unset($gift);
				$giftDisc["Gifts"] = $arNewGift[$giftDisc["GiftDiscNumber"]];
				if(empty($giftDisc["Gifts"])){ //Удаляем подарочную акцию если в неё не пришло подарки
					unset($arGiftDisc[$key]);
				}
			}
			unset($giftDisc);
		}
		return $arGiftDisc;
	}

	public function getResultGift()
	{
		return $this->arResultGift;
	}

	public function prepareItems(array $basketItems)
	{
		$arSiebelItems = [];//массив товаров для выгрузки в Siebel
		$itemCount = 0; //счётчик товаров Siebel
		foreach ($basketItems as $arItem) {
			$xml_id = explode("#", $arItem["PRODUCT_XML_ID"])[1];
			if ($arItem["PROPS_ALL"]["GIFT"]["VALUE"] != "Y") {
			    debugmessage($arItem["CAN_BUY"]);
			    if($arItem["CAN_BUY"] == "Y") { // отправляем в зибель только те товары, которые можно купить. Зибель вернёт обновлённый список и он пересохранится в корзину, удалив эти товары
                    for ($i = 1; $i <= $arItem["QUANTITY"]; $i++) {
                        $arSiebelItems[] = [
                            "PosNumber" => ++$itemCount,
                            "Item" => $xml_id,
                            "Quant" => 1,
                            "PriceDisc" => $arItem["BASE_PRICE"], //$arItem["PRICE"]
                            "PriceDiscSum" => $arItem["BASE_PRICE"], //$arItem["PRICE"]
                            "BasePrice" => $arItem["BASE_PRICE"]
                        ];
                    }
                }
			} else {
				$this->arSelectedGift[$arItem["PROPS_ALL"]["SIEBEL_GIFT_DISCOUNT_ID"]["VALUE"]] = [
					"SIEBEL_GIFT_ID" => $arItem["PROPS_ALL"]["SIEBEL_GIFT_ID"]["VALUE"],
					"SIEBEL_GIFT_DISCOUNT_ID" => $arItem["PROPS_ALL"]["SIEBEL_GIFT_DISCOUNT_ID"]["VALUE"],
					"BASKET_ELEMENT_ID" => $arItem["ID"],
                    "PRODUCT_ID" => $arItem["PRODUCT_ID"]
				];
			}
		}
        debugmessage(2222);
        die();
		return $arSiebelItems;
	}

	public function prepareCards(array $cards)
	{
		//Дисконтная карта для Siebel
		$arSiebelCards = [];
		$cardCount = 0;
		foreach($cards as $card) {
            $arSiebelCards[] = [
                "BarcodeNumber" => ++$cardCount,
                "Barcode" => $card["BARCODE"],
                "Type" => $card["TYPE"]
            ];
        }


		return $arSiebelCards;
	}

	public function prepareDelivery($arDeliveryInfo)
	{
		$count = 0;
		$arDeliveryLocality = \Girlfriend\Models\Eshop\DeliveryLocality::getBySaleLocationCode($arDeliveryInfo["DELIVERY"]['_TARIFF_DATA_']["TARIFF_LOCATION_CODE"]);
		$arSiebelDelivery = [
			"DeliveryNumber" => ++$count,
			"DeliveryMethod" => $arDeliveryInfo["DELIVERY_METHOD"],
			"PaymentMethod" => $arDeliveryInfo["PAYMENT_METHOD"]
		];
		//Формируем строку адреса
		if($arDeliveryInfo['DELIVERY']['_HANDLER_CODE_'] == 'COURIER') {
			$arSiebelDelivery["DeliveryAddress"] = $arDeliveryLocality["NAME"];

			if (strlen($arDeliveryInfo['USER_VALS']["ORDER_PROP"][2]) > 0)
				$arSiebelDelivery["DeliveryAddress"] .= ', улица: ' . $arDeliveryInfo['USER_VALS']["ORDER_PROP"][2];

			if (strlen($arDeliveryInfo['USER_VALS']["ORDER_PROP"][3]) > 0)
				$arSiebelDelivery["DeliveryAddress"] .= ', ' . $arDeliveryInfo['USER_VALS']["ORDER_PROP"][3];

			//Пока что "корпус, подъезд, этаж" нет в дизайне
			/*if(strlen($arDeliveryInfo['USER_VALS']["ORDER_PROP"][4]) > 0)
				$arSiebelDelivery["DeliveryAddress"] .= ', корпус: ' . $arDeliveryInfo['USER_VALS']["ORDER_PROP"][4];

			if(strlen($arDeliveryInfo['USER_VALS']["ORDER_PROP"][5]) > 0)
				$arSiebelDelivery["DeliveryAddress"] .= ', подъезд: ' . $arDeliveryInfo['USER_VALS']["ORDER_PROP"][5];

			if(strlen($arDeliveryInfo['USER_VALS']["ORDER_PROP"][6]) > 0)
				$arSiebelDelivery["DeliveryAddress"] .= ', этаж: ' . $arDeliveryInfo['USER_VALS']["ORDER_PROP"][6];*/

			if (strlen($arDeliveryInfo['USER_VALS']["ORDER_PROP"][7]) > 0)
				$arSiebelDelivery["DeliveryAddress"] .= ', квартира: ' . $arDeliveryInfo['USER_VALS']["ORDER_PROP"][7];
		} else {
			$arSiebelDelivery["DeliveryAddress"] = $arDeliveryInfo['USER_VALS']["ORDER_PROP"][13];
		}

		if(strlen($arDeliveryInfo['USER_VALS']["ORDER_PROP"][15]) > 0)
			$arSiebelDelivery["PhoneNumber"] = $arDeliveryInfo['USER_VALS']["ORDER_PROP"][15];

		if(strlen($arDeliveryInfo['USER_VALS']["ORDER_PROP"][8]) > 0)
			$arSiebelDelivery["DeliveryDate"] = ConvertDateTime($arDeliveryInfo['USER_VALS']["ORDER_PROP"][8], "DD.MM.YYYY HH:MI", "ru");

		if(strlen($arDeliveryInfo['USER_VALS']["ORDER_PROP"][14]) > 0)
			$arSiebelDelivery["RecipientName"] = trim($arDeliveryInfo['USER_VALS']["ORDER_PROP"][14] . " " . $arDeliveryInfo['USER_VALS']["ORDER_PROP"][29]);

		return [$arSiebelDelivery];
	}

	public function reloadSiebelOrder($needResave = true)
	{
		$arSiebelParams = [
            "SiebelId" => $this->siebelId,
			"CardNumber" => $this->discountCard,
			"ListOfOrderItem" => $this->arSiebelItems
		];

		if(!empty($this->arSiebelDelivery)) {
			$arSiebelParams["ListOfDeliveryInfo"] = $this->arSiebelDelivery;
		}

		if (!empty($this->arSiebelCards)) {
			$arSiebelParams["ListOfCard"] = $this->arSiebelCards;
		}

		$arSiebelParams["ShopIndex"] = self::shopIndex;
		$soapApi = CalculateOrder::createMethod($arSiebelParams);

		$this->prepareData($soapApi);
        if($needResave) {
            $this->reSaveBasket();
        }
	}

	private function prepareData(CalculateOrder $soapApi) {
		$this->soapApi = $soapApi;
		$this->arResultItems = $soapApi->getListOfOrderItem();
        $this->arResultDisc = $soapApi->getOriginalDiscs();
		$this->arResultGiftDisc = $soapApi->getOriginalGiftDiscs();
		$this->arResultGift = $soapApi->getOriginalGifts();

		$this->clearGifts();//очищаем корзину от старых подарков

        //Скрепляем акции из зибеля с акциями из битрикс
        $arDiscountsId = [];
        $bitrixDiscounts = [];
        if(!empty($this->arResultDisc)) {
            foreach($this->arResultDisc as $discount) {
                if(!in_array($discount['DiscId'], $arDiscountsId)) {
                    $arDiscountsId[] = $discount['DiscId'];
                }
            }
            $bitrixDiscounts = \Taber\Actions\Actions::getActionsBySiebelIds($arDiscountsId);
        }

		//собираем выбранные подарки в формате зибеля
		$giftCount = 1;
		foreach($soapApi->getListOfGift() as $gift) {
			if(!empty($this->arSelectedGift[$gift["GiftDiscId"]]) && $this->arSelectedGift[$gift["GiftDiscId"]]["SIEBEL_GIFT_ID"] == $gift["GiftItem"]) {
				unset($gift["GiftDiscId"]); //этого поля нет в требованиях к отправки в зибель
				$gift["GiftNumber"] = $giftCount++; //порядковый номер подарка
				$this->selectedGiftForSiebel[] = $gift;
			}
		}

		//в $itemsReindexed соритруем товары корзины из битрикса по артикулу
		array_walk(
			$this->arBasketItems,
			function ($item, $key) use (&$itemsReindexed) {
				$xml_id = explode("#", $item["PRODUCT_XML_ID"])[1];
				if (empty($itemsReindexed[$xml_id]) && $item["CAN_BUY"] == "Y") { // $item["CAN_BUY"] == "Y" - товары с одинаковыми артикулами и разными CAN_BUY сливаются в один, поэтому оставляем только доступные к покупке
					$itemsReindexed[$xml_id] = $item;
				} else {
					//@todo throw error
				}
			}
		);

		//Скрещиваем товары из Зибеля с товарами из Битрикса
		$newPrintItems = [];
		$arDiscounts = []; //сгруппированные скидки для вывода в итоговую информацию
		foreach ($this->arResultItems as $key => $soapItem) {
			$bitrixItem = $itemsReindexed[$soapItem["Item"]];
			$bitrixItem["QUANTITY"] = 0; //счётчик кол-ва товаров. Его кол-во получим из зибеля
			$bitrixItem["SIEBEL"] = []; //массив парамтров, подсчитанных из зибеля
			$newItem = $bitrixItem;

			$newItem["PRICE"] = $newItem["_ROUNDED_PRICE_"] = $newItem["~PRICE"] = $soapItem["PriceDisc"];

			//Определяем если этот товар является доставкой
			if($soapItem["Item"] == $this->deliveryId) {
				$newItem["IS_DELIVERY"] = true;
			}

			$newItem["SIEBEL"]["FULL_PRICE"] += $soapItem["PriceDisc"]*$soapItem["Quant"];
			$newItem["BASE_PRICE"] = $soapItem["BasePrice"];
			$newItem["QUANTITY"] = $soapItem["Quant"] + $newItem["QUANTITY"];

			$newItem["SOAP_NUMBER"][] = $soapItem["PosNumber"];
			foreach ($soapItem["Discount"] as $disc) {
			    //Добавляем к акциям информацию из битрикс
                if($bitrixDiscounts[$disc["DiscId"]]["SHORT_NAME"]) {
                    $disc["DiscShownName"] = $bitrixDiscounts[$disc["DiscId"]]["SHORT_NAME"];
                } elseif($bitrixDiscounts[$disc["DiscId"]]["NAME"]) {
                    $disc["DiscShownName"] = $bitrixDiscounts[$disc["DiscId"]]["NAME"];
                } else {
                    $disc["DiscShownName"] = $disc["DiscName"];
                }

				//Формируем массив скидок товара
				if(empty($newItem["DISCOUNTS"][$disc["DiscId"]])) { //Если такой тип скидок ещё не был у товара //&& $soapItem["PriceDisc"] > 0
					$newItem["DISCOUNTS"][$disc["DiscId"]] = $disc;
					if($soapItem["PriceDisc"] > 0) { //Если PriceDisc = 0 то эта скидка отображаетс в рублях, а не процентах
						$newItem["SIEBEL"]["PERCENT"] = $disc["DiscPercent"] + $newItem["SIEBEL"]["PERCENT"]; //плюсуем только скидки без продублированных скидок(дубли из-за кол-ва товара)
					} else {
						$newItem["SIEBEL"]["PRICE_DISCOUNT"] = $soapItem["BasePrice"]; //PRICE_DISCOUNT - это значение будет отображаться в "формуле" товара как вычет
					}
				} else {

				}
				//подсчитываем суммарные данные скидок
				if(empty($arDiscounts[$disc["DiscId"]])) {
					$arDiscounts[$disc["DiscId"]] = [
						"DiscSum" => $disc["DiscSum"]*$soapItem["Quant"],
						"DiscId" => $disc["DiscId"],
						"DiscName" => $disc["DiscName"],
                        "DiscShownName" => $disc["DiscShownName"],
						"DiscPercent" => $disc["DiscPercent"]
					];
				} else {
					$arDiscounts[$disc["DiscId"]]["DiscSum"] += $disc["DiscSum"];
				}
				$this->arSummaryData["TOTAL_DISCOUNT"] += $disc["DiscSum"]*$soapItem["Quant"];
			}
			//Выбраем позицию, которая отображает доставку
			if($soapItem["Item"] == $this->deliveryId) {
				$this->arSummaryData["DELIVERY"] = $newItem;
			}
			$this->arSummaryData["TOTAL_BASE_PRICE"] += $soapItem["BasePrice"]*$soapItem["Quant"];
			$this->arSummaryData["TOTAL_PRICE"] += $soapItem["PriceDisc"]*$soapItem["Quant"];
			$this->arSummaryData["TOTAL_QUANTITY"] += $soapItem["Quant"];
			$newPrintItems[] = $newItem;
		}

		$this->arSummaryData["TOTAL_DISCOUNTS"] = $arDiscounts; //скидки для итогового блока

		$this->arPrintItems = $newPrintItems;
	}

    /**
     * Пересохраняет корзину учитывая данные из зибеля.
     * Запускать после метода reloadSiebelOrder
     */
	public function reSaveBasket() {
	    global $APPLICATION;
        $strError = "";
        $origBasketItems = $this->arBasketItems; //товары в корзине битрикса
        $basketItems = $this->arPrintItems; //товары корзины, полученные из зибеля

        $itemsForUpdate = []; //Массив товаров в очереди на апдейт. Пришлось вынести их отдельно, чтобы не было проблем с остатками на складе(когда нужно сначала удалить устаревшие позиции и только потом обновить другие при дублировании товара).
        $oB = new \CSaleBasket;
        foreach($origBasketItems as $originItem) {
            $found = false; //был ли найден товар из корзины битрикса в корзине зибеля
            foreach($basketItems as $key => $item) {
                if($item['PRODUCT_ID'] && $originItem['PRODUCT_ID'] == $item['PRODUCT_ID'] && !$found) {
                    $found = true;
                    unset($basketItems[$key]); //по одному товару убираем из массива, чтобы второй раз на него не натыкаться

                    $originItem["PROPS"] = $item["PROPS"];
                    $originItem["QUANTITY"] = $item["QUANTITY"];
                    $originItem["PRICE"] = $item["PRICE"]; //$originItem["~PRICE"]
                    $originItem["CUSTOM_PRICE"] = "Y";
                    $originItem = self::unsetProblemFields($originItem);

                    $itemsForUpdate[] = $originItem; //в очередь на апдейт
                    $this->arPrintItems[$key]["ID"] = $originItem["ID"]; //обновляем ID позици товара в корзине из настоящий
                    //$updatedId = $oB->Update($originItem['ID'], $originItem); //апдейтить сразу не получилось, так как остатков может нехватить на складе, а продублированный товар ещё не удалён
                } elseif(!$item['PRODUCT_ID']) {
                    unset($basketItems[$key]);
                }
            }
            //Если товар в корзине не найден в зибеле, то удаляем этот товар
            if (!$found) {
                //Не найденные подарки не удаляем, актуальност подарков проверяется в методе clearGifts в операциях с подарками
                if($originItem["PROPS_ALL"]["GIFT"]["VALUE"] != "Y" && $originItem['PRODUCT_ID']) {
                    $oB->Delete($originItem['ID']);
                }
            }
        }

        $itemsForUpdateSecondTry = [];
        //Обновляем товары, накопившиеся в очереди на обновление
        if(!empty($itemsForUpdate)) {
            foreach($itemsForUpdate as $updateItem) {
                try{
                    $updatedId = $oB->Update($updateItem['ID'], $updateItem); //$APPLICATION->LAST_ERROR
                    if(!$updatedId) {
                        throw new BasketUpdateErrorException("Некритичная ошибка: " . $APPLICATION->LAST_ERROR);
                    }
                } catch (BasketUpdateErrorException $e) {
                    $itemsForUpdateSecondTry[] = $updateItem;
                }
            }
        }
        /*Обновляем товары, накопившиеся в очереди на обновление
         * Есть баг, когда нужно увеличить кол-во товаров одной позиции больше максимально возможной, но следующая позиция должна освоболить недостающее кол-во товара со склада
         * Нужно переделать, например, отсортировав товары по возрастанию кол-ва
        */
        if(!empty($itemsForUpdateSecondTry)) {
            $oB3 = new \CSaleBasket;
            foreach($itemsForUpdateSecondTry as $updateItem) {
                try {
                    $updatedId = $oB3->Update($updateItem['ID'], $updateItem); //$APPLICATION->LAST_ERROR
                    if (!$updatedId) {
                        throw new BasketUpdateErrorException("Ошибка: " . $APPLICATION->LAST_ERROR);
                    }
                } catch (BasketUpdateErrorException $e) {
                    //
                }
            }
        }

        //эти товары есть в зибеле, но их ещё нет в корзине. Надо добавить
        foreach($basketItems as $key => $item) {
            if(!$item["IS_DELIVERY"]) {
                $item["PROPS"][] = [ // HACKING HACK - добавляем выдуманное св-во товара в корзине, только чтобы при Add2BasketByProductID битрикс не склеил два одинаковых товара
                    'NAME' => 'NO_MERGE_PRICE',
                    'CODE' => 'NO_MERGE_PRICE',
                    'VALUE' => $item["PRICE"], //Если цена одинаковая, то битрикс склеит одинаковые товары
                    'SORT' => "900",
                ];

                $newId = \Girlfriend\Models\Eshop\Basket::addToBasket($item["PRODUCT_ID"], $item["QUANTITY"], 0, $item);
                if(!$newId) {
                    throw new \Exception('Ошибка добавления товара в корзину');
                } else {
                    $this->arPrintItems[$key]["ID"] = $newId; //обновляем ID позици товара в корзине из настоящей в битриксе
                }
            }
        }
    }

    /**
     * Удаляет из элемента корзины поля, которые не дают сохранить позицию
     * @param $item
     * @return mixed
     */
    static private function unsetProblemFields($item) {
        if (\is_array($item['PROPS']) && !empty($item['PROPS'])) {
            foreach ($item['PROPS'] as &$prop) {
                unset($prop['BASKET_ID']); //Это поле не даёт обновить позицию
            }
        }
        unset($prop);
	    return $item;
    }

	/**
	 * очистка корзины от подарков, которые утратили актуальность участия в акции
	 */
	private function clearGifts() {
		$giftDiscountsId = [];
		//собираем все айдишники акций из зибеля
		if(!empty($this->arSelectedGift)) {
			foreach($this->arResultGiftDisc as $giftDiscount) {
				$giftDiscountsId[] = $giftDiscount["GiftDiscId"];
			}
		}
		foreach($this->arSelectedGift as $key => $gift) {
			//Если id акции, к которой принадлежит подарок, больше нет, то удаляем этот товар
			if(!in_array($gift["SIEBEL_GIFT_DISCOUNT_ID"], $giftDiscountsId)) {
				$obResult = \Girlfriend\Models\Eshop\Basket::setBasketItemQuantity($gift["BASKET_ELEMENT_ID"], 0);
				if (!$obResult->isSuccess()) {
					throw new \Exception(); //подарок почему-то не удалился
				} else {
					unset($this->arSelectedGift[$key]);
				}
			}
		}
	}

    /**
     * @param \Bitrix\Sale\Order $obOrder
     * @param int $chequeStatus
     * @param array $payment
     */
	public function saveSiebelOrder(\Bitrix\Sale\Order $obOrder, $chequeStatus = CreateOrder::CHEQUE_STATUS_WAITING_ACCEPT, $payments = [])
	{
		$soapApi        = $this->soapApi;
		$arSiebelCards 	= $this->getSiebelCards();
		$arSiebelData 	= $this->getSummaryData();

		if(!empty($payments)) {
            $orderPayment = [];
            foreach($payments as $payment) {
                $orderPayment[] = [
                    "PayNumber" => $payment["ID"],
                    "PayType" => $payment["TYPE"],
                    "PaySum" => $payment["SUM"],
                ];
            }
        }

		//
		$arSiebelParams = [
			"CardNumber" => $this->discountCard,
            "SiebelId" => $this->siebelId,
			"ListOfOrderItem" => $soapApi->getOriginalItems(),
			"ListOfDeliveryInfo" => $this->getSiebelDelivery(),
			"ListOfDisc" => $soapApi->getOriginalDiscs(),
			"ListOfDiscountGroups" => $soapApi->getOriginalDiscountsGroups(),
            "ListOfCard" => $soapApi->getOriginalCards(),
		];

		if(!empty($orderPayment)) {
            $arSiebelParams["ListOfOrderPayment"] = $orderPayment;
        }

		if(!empty($soapApi->getOriginalGiftDiscs())) {
			$arSiebelParams["ListOfGiftDisc"] = $soapApi->getOriginalGiftDiscs();
		}
		//выбранные подарки
		if(!empty($this->selectedGiftForSiebel)) {
			$arSiebelParams["ListOfGift"] = $this->selectedGiftForSiebel;
		}

		if (!empty($arSiebelCards)) {
			$arSiebelParams["ListOfCard"] = $arSiebelCards;
		}

		$arSiebelParams["ShopIndex"] = self::shopIndex;
		$arSiebelParams["ChequeId"] = CreateOrder::CHEQUE_ID_PREFIX . $obOrder->getField("ACCOUNT_NUMBER");
		$arSiebelParams["ChequeNumber"] = "17";
		$arSiebelParams["ShiftNumber"] = "10";
		$arSiebelParams["CashNumber"] = "12";
		$arSiebelParams["ChequeOpenDate"] = date("d.m.Y H:i");
		$arSiebelParams["Operation"] = "0";
		$arSiebelParams["ChequeStatus"] = $chequeStatus;
		$arSiebelParams["ChequeSum"] = $arSiebelData["TOTAL_PRICE"];
		$arSiebelParams["OfflineCheque"] = "0";
		$arSiebelParams["AuthByPhoneNumber"] = "0";
		//$arSiebelParams["OfflineType"] = "1"; //из-за него не работает метод CreateOrder

		$soapApi = CreateOrder::createMethod($arSiebelParams);

		$this->prepareData($soapApi); //тут подменяются данные после CalculateOrder, используются одни и те же переменные
	}

	/*public static function saveOrder($basketItems, $arDelivery = [])
	{
		$result = new static($basketItems, $arDelivery);
		$result->reloadSiebelOrder(); //отправляем calculateOrder в зибель для получения суммарных данных
		$result->saveSiebelOrder($result->soapApi); //сохраняем заказ в зибель

		return $result;
	}*/
}