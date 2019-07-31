<?php

namespace Taber\Siebel\SiebelException;

/**
 * Class SiebelRequiredFieldException
 * @package Taber\Siebel\SiebelException
 */
class SiebelRequiredFieldException extends SiebelException
{

    /**
     * SiebelRequiredFieldException constructor.
     * @param $strErrors
     */
    public function __construct($strErrors)
    {
    	$message = "Some fields required for Siebel request:\n";
		$message .= implode("\n", $strErrors);

		parent::__construct(
			$message,
			parent::SIEBEL_REQUIRED_FIELD
		);
    }
}