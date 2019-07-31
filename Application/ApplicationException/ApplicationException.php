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

namespace Taber\Siebel\Application\ApplicationException;

use Taber\Podrygka\TaberLogs\TaberExceptionLog;

/**
 * Class ApplicationException
 * @package Taber\Siebel\Application\ApplicationException
 */
class ApplicationException extends \Exception
{
    // файл для записи лога ошибок
    const LOG_FILE = '_log/siebel_application.txt';

    const APPLICATION_PIN_CODE_NOT_SENT = 11141;

    /**
     * ApplicationException constructor.
     * @param string $message
     * @param int $code
     * @param Throwable|null $previous
     */
    public function __construct(string $message = "", int $code = 0, Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
        self::writeTxtLog();  // при возникновении ошибки сразу пишет ее в лог файл
    }

    /**
     * Запись лога ошибок в файл LOG_FILE
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