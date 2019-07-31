<?php

namespace Taber\Siebel\SiebelException;

/**
 * Class SiebelErrorResponceException
 * @package Taber\Siebel\SiebelException
 */
class SiebelErrorResponceException extends SiebelException
{

    /**
     * SiebelErrorResponceException constructor.
     * @param $arBody
     */
    public function __construct($arBody)
    {
		$message = "Siebel responce errors: " . $arBody["ErrorText"] . " (". $arBody["ErrorCode"] . ")";
		parent::__construct(
			$message,
			parent::SIEBEL_ERROR_RESPONSE
		);
    }
}