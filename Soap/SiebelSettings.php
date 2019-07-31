<?php
/**
 * Created by PhpStorm.
 * User: a.vartan
 * Date: 22.06.2018
 */

namespace Taber\Siebel\Soap;
use Taber\Siebel\SiebelException\SiebelClientSettingsException;
use Taber\Siebel\SiebelException\SiebelClientUrlException;

/**
 * Class SiebelSettings
 * @package Taber\Siebel\Soap
 */
class SiebelSettings
{
    /**
     * @var string
     */
    private $wsdlUrl;
    /**
     * подключение к методам Авторизация/Регистрация
     */
    public const WSDL_AUTH_METHODS = 1;
    /**
     * подключение к методам Корзина/Заказ
     */
    public const WSDL_ORDER_METHODS = 2;

    /**
     * SiebelSettings constructor.
     * @param int $methodType
     * @throws SiebelClientSettingsException
     * @throws SiebelClientUrlException
     */
    public function __construct($methodType = self::WSDL_ORDER_METHODS)
    {
        $settings = require(\Bitrix\Main\Application::getDocumentRoot() . '/bitrix/settings_extra.php');
        if (!$settings["siebel"]["WSDL_AUTH_CONFIG_URL"] || !$settings["siebel"]["WSDL_ORDER_CONFIG_URL"]) {
            throw new SiebelClientSettingsException();  // нет файла настроек подключения к siebel
        } else {
            switch ($methodType) {
                case self::WSDL_AUTH_METHODS:
                    $this->wsdlUrl = $settings["siebel"]["WSDL_AUTH_CONFIG_URL"];
                    break;
                case self::WSDL_ORDER_METHODS:
                    $this->wsdlUrl = $settings["siebel"]["WSDL_ORDER_CONFIG_URL"];
                    break;
                default:
                    throw new SiebelClientUrlException();
            }
        }
    }

    /**
     * @return string
     */
    public function getWsdlUrl(): string
    {
        return $this->wsdlUrl;
    }
}