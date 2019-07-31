<?php

namespace Taber\Siebel\SiebelException;

/**
 * Class SiebelClientUrlException
 * @package Taber\Siebel\SiebelException
 */
class SiebelClientUrlException extends SiebelException
{

    /**
     * SiebelClientUrlException constructor.
     */
    public function __construct()
    {
        $message = "Указан неверный URL для файла конфигурации wsdl";
        parent::__construct(
            $message,
            parent::SIEBEL_CLIENT_URL_EXCEPTION
        );
    }
}