<?php

namespace Taber\Siebel\SiebelException;

/**
 * Class SiebelErrorCurlException
 * @package Taber\Siebel\SiebelException
 */
class SiebelErrorCurlException extends SiebelException
{

    /**
     * SiebelErrorCurlException constructor.
     * @param $arBody
     */
    public function __construct($text)
    {
		$message = "Siebel curl error: " . $text;
		parent::__construct(
			$message,
			parent::SIEBEL_CURL_EXCEPTION
		);
    }
}