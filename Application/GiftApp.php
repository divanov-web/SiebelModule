<?php
/**
 * Created by PhpStorm.
 * User: d.ivanov
 * Date: 18.10.2018
 * Time: 15:02
 *
 * Класс для управления подарками
 */

namespace Taber\Siebel\Application;

class GiftApp
{
	/**
	 * @var \Bitrix\Sale\Basket|null
	 */
	private $obBasket = null;

	private $obSiebelGift = null;


	private function __construct(int $giftElementId, string $giftDiscId)
	{
		$this->obSiebelGift = new \Taber\Siebel\Utils\SiebelGift($giftElementId, $giftDiscId);
		$this->obBasket = \Girlfriend\Models\Eshop\Basket::getBasket();
	}

	private function addToBasket()
	{
		$catalogProductData = $this->obSiebelGift->getCatalogProductData();
		if ($this->checkInBasket()) {
			$arReturn['errorMessage'] = "Подарок уже есть в корзине";
		} elseif ($catalogProductData['DATA']['CAN_ADD_TO_BASKET'] != 'Y') {
			$arReturn['errorMessage'] = "Подарок недоступен";
		} else {
			$arProductParams = [
				[
					'NAME' => 'GIFT',
					'CODE' => 'GIFT',
					'VALUE' => 'Y',
					'SORT' => 100
				],
				[
					'NAME' => 'SIEBEL_GIFT_DISCOUNT_ID',
					'CODE' => 'SIEBEL_GIFT_DISCOUNT_ID',
					'VALUE' => $this->obSiebelGift->getGiftDiscId(),
					'SORT' => 200
				],
				[
					'NAME' => 'SIEBEL_GIFT_ID',
					'CODE' => 'SIEBEL_GIFT_ID',
					'VALUE' => $this->obSiebelGift->getGiftId(),
					'SORT' => 300
				],
			];
			$iBasketItemId = \Girlfriend\Models\Eshop\Basket::addToBasket($catalogProductData['DATA']["ID"], 1, 0, ["PRICE" => 0, "CUSTOM_PRICE" => "Y"], $arProductParams);
		}

		return $iBasketItemId ? $iBasketItemId : false;
	}

	/**
	 * @return bool
	 */
	private function checkInBasket()
	{
		$inBasket = false;
		$obCollection = $this->obBasket->getBasketItems();
		foreach ($obCollection as $obBasketItem) {
			$arProperties = $obBasketItem->getPropertyCollection()->getPropertyValues();
			if ($obBasketItem->getField("PRODUCT_ID") == $this->obSiebelGift->getGiftElementId() && $arProperties["SIEBEL_GIFT_DISCOUNT_ID"]["VALUE"] == $this->obSiebelGift->getGiftDiscId()) {
				$inBasket = true;
			} elseif ($arProperties["SIEBEL_GIFT_DISCOUNT_ID"]["VALUE"] == $this->obSiebelGift->getGiftDiscId()) { //Удаляем другие выбранные подарки под этой акцией
				$obBasketItem->delete();
				$obBasketItem->save();
				$wasDeleted = true;
			}
			//Если один из подарков был удалён, нужно сохранить саму корзину
			if ($wasDeleted) {
				$this->obBasket->save();
			}
		}
		return $inBasket;
	}

	public static function addGiftToBasket(int $giftElementId, string $giftDiscId)
	{
		$result = new static($giftElementId, $giftDiscId);
		return $result->addToBasket();
	}

}