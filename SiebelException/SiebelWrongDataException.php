<?php

namespace Taber\Siebel\SiebelException;

/**
 * Class SiebelWrongDataException
 * @package Taber\Siebel\SiebelException
 */
class SiebelWrongDataException extends SiebelException
{

    /**
     * SiebelWrongDataException constructor.
     * @param $text
     */
    public function __construct($text)
    {
		parent::__construct(
			'Data error: ' . $text,
			parent::SIEBEL_WRONG_DATA
		);
    }
}