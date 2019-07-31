<?php
/**
 * Created by PhpStorm.
 * User: d.ivanov
 * Date: 22.11.2018
 * Time: 10:18
 *
 * Расширяет стандартную библиотеку SoapClient для установки таймаута
 */

namespace Taber\Siebel\Soap;

use Bitrix\Main\Diag\Debug;
use Taber\Siebel\SiebelException\SiebelErrorCurlException;


class SoapClientTimeout extends \SoapClient
{

    private $timeout;

    public function __setTimeout($timeout )
    {
        if (!is_int($timeout) && !is_null($timeout))
        {
            throw new SiebelErrorCurlException("Invalid timeout value");
        }

        $this->timeout = $timeout;
    }

    public function __doRequest($request, $location, $action, $version, $one_way = FALSE)
    {
        if (!$this->timeout)
        {
            // Call via parent because we require no timeout
            $response = parent::__doRequest($request, $location, $action, $version, $one_way);
        }
        else
        {
            // Call via Curl and use the timeout
            $curl = curl_init($location);

//            curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, 0);
//            curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, 0);
            curl_setopt($curl, CURLOPT_HTTPHEADER, array("Content-Type: text/xml"));
            curl_setopt($curl, CURLOPT_VERBOSE, FALSE);
            curl_setopt($curl, CURLOPT_RETURNTRANSFER, TRUE);
            curl_setopt($curl, CURLOPT_POST, TRUE);
            curl_setopt($curl, CURLOPT_POSTFIELDS, $request);
            curl_setopt($curl, CURLOPT_HEADER, FALSE);
            curl_setopt($curl, CURLOPT_TIMEOUT, $this->timeout);

            $response = curl_exec($curl);

            if (curl_errno($curl))
            {
                throw new SiebelErrorCurlException(curl_error($curl));
            }

            curl_close($curl);
        }

        // Return?
        if (!$one_way)
        {
            return ($response);
        }
    }
}