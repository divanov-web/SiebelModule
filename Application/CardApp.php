<?php
/**
 * Created by PhpStorm.
 * User: d.ivanov
 * Date: 14.11.2018
 * Time: 16:10
 */

namespace Taber\Siebel\Application;

use Taber\Siebel\Methods\CreateOrder;
use Taber\Siebel\Methods\GetCardStatus;
use Taber\Siebel\SiebelException\SiebelErrorResponceException;

class CardApp
{
    public function __construct()
    {

    }

    static public function checkCupon(string $cardNumber) {
        $arSiebelParams = [
            "ShopIndex" => Basket::shopIndex,
            "RequestType" => 1 //1 – запрос информации по ДК/купону в чеке;
        ];

        /*$arSiebelParams["Card"] = [
            "CardNumber" => $cardNumber
        ];*/
        $arSiebelParams["CardNumber"] = $cardNumber;

        try{
            $soapApi = GetCardStatus::createMethod($arSiebelParams);
        } catch(SiebelErrorResponceException $e) {
            return false; //Карта не найдена
        }

        return $soapApi->isCardValid();
    }
}