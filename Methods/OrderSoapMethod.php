<?php
/**
 * Created by PhpStorm.
 * User: a.vartan
 * Date: 20.09.2018
 * Time: 10:10
 */

namespace Taber\Siebel\Methods;

use Taber\Siebel\Soap\SiebelSettings;

/**
 * Class OrderSoapMethod
 * @package Taber\Siebel\Methods
 */
abstract class OrderSoapMethod extends SoapMethod
{
    const METHOD_TYPE = SiebelSettings::WSDL_ORDER_METHODS;
}