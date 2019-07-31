<?php
/**
 * Class Taber\Siebel\SiebelException\SiebelException
 *
 * copy from Taber\Podrygka\AdminImport\AdminImportException
 *
 * Created by PhpStorm.
 * User: d.ivanov
 * Date: 07.06.2018
 *
 */

namespace Taber\Siebel\SiebelException;

use Taber\Podrygka\TaberLogs\TaberExceptionLog;

/**
 * Class SiebelException
 * @package Taber\Siebel\SiebelException
 */
class SiebelException extends \Exception
{
    // файл для записи лога ошибок
    const LOG_FILE = '_log/siebel.txt';

    // коды ошибок SiebelException
    const SIEBEL_NOT_FOUND = 11131;
	const SIEBEL_NO_ITEMS = 11132;
	const SIEBEL_REQUIRED_FIELD = 11133;
	const SIEBEL_ERROR_REQUEST = 11134;
	const SIEBEL_ERROR_RESPONSE = 11135;
	const SIEBEL_WRONG_DATA = 11136;
	const SIEBEL_CLIENT_URL_EXCEPTION = 11137;
	const SIEBEL_CLIENT_SETTINGS_EXCEPTION = 11138;
    const SIEBEL_CURL_EXCEPTION = 11139;
    const BASKET_UPDATE_EXCEPTION = 11140;

    /**
     * SiebelException constructor.
     * @param string $message
     * @param int $code
     * @param Throwable|null $previous
     */
    public function __construct(string $message = "", int $code = 0, Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
        self::writeTxtLog();  // при возникновении ошибки сразу пишет ее в БД
    }

    /**
     * Запись лога ошибок в БД
     */
    public function writeTxtLog()
    {
        new TaberExceptionLog($this);
    }

    /**
     * строковый вывод сообщения об ошибке с указанием кода ошибки
     *
     * @return string
     */
    public function __toString()
    {
        return "EXCEPTION_CODE={$this->code} " . parent::__toString();
    }
}