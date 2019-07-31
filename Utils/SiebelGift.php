<?php
/**
 * Created by PhpStorm.
 * User: d.ivanov
 * Date: 18.10.2018
 * Time: 16:02
 */

namespace Taber\Siebel\Utils;


class SiebelGift
{
	private $giftId;

	private $giftElementId;

	private $giftDiscId;

	private $catalogProductData;

	/**
	 * @return mixed
	 */
	public function getGiftId()
	{
		return $this->giftId;
	}

	/**
	 * @return mixed
	 */
	public function getGiftElementId()
	{
		return $this->giftElementId;
	}

	/**
	 * @return mixed
	 */
	public function getGiftDiscId()
	{
		return $this->giftDiscId;
	}

	/**
	 * @return array
	 */
	public function getCatalogProductData(): array
	{
		return $this->catalogProductData;
	}

    /**
     * SiebelGift constructor.
     * @param int $giftElementId
     * @param string $giftDiscId
     * @throws \Bitrix\Main\LoaderException
     */
	public function __construct(int $giftElementId, string $giftDiscId)
	{
		$this->giftElementId = $giftElementId;
		$this->giftDiscId = $giftDiscId;

		$obParamsUser = new \Girlfriend\Models\ParamsUser($GLOBALS['USER']->GetId());
		$this->catalogProductData = \Girlfriend\Models\Catalog\Utils::getCatalogProductDataById(
			$this->giftElementId, $obParamsUser
		);
        $this->giftId = $this->catalogProductData["DATA"]["PROPERTIES"]["ARTICLE"];
	}
}