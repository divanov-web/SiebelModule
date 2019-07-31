<?php

namespace Taber\Siebel\SiebelException;

/**
 * Class SiebelNotFoundException
 * @package Taber\Siebel\SiebelException
 */
class SiebelNotFoundException extends SiebelException
{

    /**
     * SiebelNotFoundException constructor.
     * @param $item - пока не придумал, что передавать
     */
    public function __construct($item)
    {
		parent::__construct(
			'Siebel server not found',
			parent::SIEBEL_NOT_FOUND
		);
    }
}