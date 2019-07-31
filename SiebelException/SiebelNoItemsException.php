<?php

namespace Taber\Siebel\SiebelException;

/**
 * Class SiebelNoItemsException
 * @package Taber\Siebel\SiebelException
 */
class SiebelNoItemsException extends SiebelException
{

    /**
     * SiebelNotFoundException constructor.
     * @param $request
     */
    public function __construct($request)
    {
		parent::__construct(
			'No items found in object name ' . get_class($request),
			parent::SIEBEL_NO_ITEMS
		);
    }
}