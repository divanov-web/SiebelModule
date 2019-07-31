<?php
/**
 * Created by PhpStorm.
 * User: d.ivanov
 * Date: 07.06.2018
 * Time: 16:48
 */

namespace Taber\Siebel;

use Taber\Siebel\Soap\Client;
use Taber\Siebel\Utils\PinCode;

/**
 * Временный класс для записи логов запросов и ответов.
 * Debug::dumpToFile на сервере dev4 записывает лишнюю информацию из-за xdebug
 *
 * Class Log
 * @package Taber\Siebel
 */
class Log
{
    // файл для записи лога ошибок
    const LOG_FILE_PATH = '_log/siebel/trace_data_';
    const LOG_FILE_EXTENTION = '.txt';

    public function addTraceToLogFile(Client $obSoapClient)
    {
        $arLastRequestTrace = $obSoapClient->getLastRequestTrace();
        $filename = $_SERVER["DOCUMENT_ROOT"] . "/" . self::LOG_FILE_PATH . date('Y.m.d') . self::LOG_FILE_EXTENTION;
        $file = @fopen($filename, "ab");
        $str = "";
        $str .= $arLastRequestTrace["SOAP_METHOD"] . "\n";
        $str .= "REQUEST " . date('d.m.Y H:i:s') . " " . "\n";
        $str .= $arLastRequestTrace["REQUEST"];
        $str .= "\n\n";
        $str .= "RESPONSE " . date('d.m.Y H:i:s') . "\n";
        $str .= $arLastRequestTrace["RESPONSE"];
        $str .= "\n";
        $str .= "Execute time: " . $obSoapClient->getExcecuteTime() . " \n";
        $str .= "------------------------------------------------";
        $str .= "\n\n";
        fwrite($file, $str);
        fclose($file);
    }

    /**
     * пишем в трейс ошибки ввода кода из смс
     * @param PinCode $pinCode
     * @param $code
     */
    public function addCodeValidationToLogFile(PinCode $pinCode, $code)
    {
        $filename = $_SERVER["DOCUMENT_ROOT"] . "/" . self::LOG_FILE_PATH . date('Y.m.d') . self::LOG_FILE_EXTENTION;
        $file = @fopen($filename, "ab");
        $str = "";
        $str .= "SMS " . date('d.m.Y H:i:s') . " " . "\n";
        $str .= "WEB_USER_ID " . $pinCode->getWebClientId() . "\n";
        $str .= "CODE " . $pinCode->getCode() . "\n";
        $str .= "INSERT_CODE " . $code . "\n";
        $str .= "------------------------------------------------";
        $str .= "\n\n";
        fwrite($file, $str);
        fclose($file);
    }

    /**
     * Отображает лог запроса на странице
     * @param Client $obSoapClient
     * @param string $color
     */
    public function showTraceOnPage(Client $obSoapClient, $color = "#008B8B")
    {
        $arLastRequestTrace = $obSoapClient->getLastRequestTrace();
        echo '<table class="debugmessage" border="0" cellpadding="5" cellspacing="0" style="border:1px solid ' . $color . ';margin:2px;background: #ffffff; text-align:left;"><tr><td>';
        self::printText($arLastRequestTrace["SOAP_METHOD"]);
        self::printText($arLastRequestTrace["REQUEST"]);
        self::printText($arLastRequestTrace["RESPONSE"]);
        self::printText("Execute time: " . $obSoapClient->getExcecuteTime());
        echo '</td></tr></table>';
    }

    /**
     * Форматирует и выводит большой текст
     * @param $text
     * @param string $color
     */
    static private function printText($text, $color = "#008B8B")
    {
        $text = htmlspecialchars($text);
        //$text = str_replace("&lt;", "<br />&lt;", $text);
        echo '<pre style="color:' . $color . ';font-size:11px;font-family:Verdana;">';
        print_r($text . "</br>");
        echo '</pre>';
    }

}