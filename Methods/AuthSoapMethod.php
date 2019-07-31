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
 * Class AuthSoapMethod
 * @package Taber\Siebel\Methods
 */
abstract class AuthSoapMethod extends SoapMethod
{
    const METHOD_TYPE = SiebelSettings::WSDL_AUTH_METHODS;
}