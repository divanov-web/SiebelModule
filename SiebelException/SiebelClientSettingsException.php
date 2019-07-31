<?php

namespace Taber\Siebel\SiebelException;

/**
 * Class SiebelClientSettingsException
 * @package Taber\Siebel\SiebelException
 */
class SiebelClientSettingsException extends SiebelException
{

    /**
     * SiebelErrorRequestException constructor.
     */
    public function __construct()
    {
        $message = "Нет настроек для подключения siebel. Добавьте:
         array('siebel' => [
         'WSDL_AUTH_CONFIG_URL' => 'ссылка',
         'WSDL_ORDER_CONFIG_URL' => 'ссылка',
         ])
          в файле /bitrix/settings_extra.php";
        parent::__construct(
            $message,
            parent::SIEBEL_CLIENT_SETTINGS_EXCEPTION
        );
    }
}