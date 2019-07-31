<?php

namespace Taber\Siebel\SiebelException;


class BasketUpdateErrorException extends SiebelException
{
    /**
     * BasketUpdateErrorException constructor.
     * @param $text
     */
    public function __construct($lastError)
    {
        parent::__construct(
            'Basket update error: ' . $lastError,
            parent::BASKET_UPDATE_EXCEPTION
        );
    }
}