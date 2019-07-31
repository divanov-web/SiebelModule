<?php

namespace Taber\Siebel\SiebelException;

/**
 * Class SiebelErrorRequestException
 * @package Taber\Siebel\SiebelException
 */
class SiebelErrorRequestException extends SiebelException
{

	/**
	 * SiebelErrorRequestException constructor.
	 * @param $arErrors
	 */
    public function __construct($arErrors)
    {
		$message = "Siebel request errors:\n";
    	foreach($arErrors as $obError) {
			$message .= ($obError->GetMessage());
		}
		parent::__construct(
			$message,
			parent::SIEBEL_ERROR_REQUEST
		);
    }
}