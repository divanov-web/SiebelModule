<?php
/**
 * Created by PhpStorm.
 * User: a.vartan
 * Date: 20.07.2018
 * Time: 10:35
 */

namespace Taber\Siebel\Utils;


use Taber\Siebel\Application\AuthorizationPhoneChecker;

class AuthorisationClientSmsSession
{
    private $smsCount;
    private $smsInsertCount;
    private $webClientId;
    /**
     * @var int таймстемп сегодня в 00:00:00
     */
    private $dateToday;

    const SMS_COUNT_CLIENT = 'SMS_COUNT_CLIENT';
    const SMS_INSERT_COUNT_CLIENT = 'SMS_INSERT_COUNT_CLIENT';

    public function __construct(WebClient $webClient)
    {
        $this->webClientId = $webClient->getWebClientId();
        $this->smsCount = self::checkSmsCountSessionExist() ?? 0;
        $this->smsInsertCount = self::checkSmsInsertSessionExist() ?? 0;
        $this->dateToday = strtotime(date('Y-m-d 00:00:00'));
        self::saveSessionStation();
    }

    /**
     * если по юзеру еще нет смс сессии то мы ее сохраняем
     */
    public function saveSessionStation()
    {
        $_SESSION[self::SMS_COUNT_CLIENT][$this->webClientId][$this->dateToday] = $this->smsCount;
        $_SESSION[self::SMS_INSERT_COUNT_CLIENT][$this->webClientId][$this->dateToday] = $this->smsInsertCount;
    }

    /**
     * проверяем сохраняли ли сегодня по юзеру сессию запросов смс кодов
     * @return bool
     */
    private function checkSmsCountSessionExist()
    {
        return $_SESSION[self::SMS_COUNT_CLIENT][$this->webClientId][$this->dateToday] ?? false;
    }

    /**
     * проверяем сохраняли ли сегодня по юзеру сессию ввода кода
     * @return bool
     */
    private function checkSmsInsertSessionExist()
    {
        return $_SESSION[self::SMS_INSERT_COUNT_CLIENT][$this->webClientId][$this->dateToday] ?? false;
    }

    /**
     * лимит запросов новых кодов
     * @return bool
     */
    public function checkSmsCount()
    {
        $daySmsLimit = AuthorizationPhoneChecker::DAY_SMS_COUNT;
        return $this->smsCount <= $daySmsLimit ? true : false;
    }

    /**
     * лимит попыток ввода кода
     * @return bool
     */
    public function checkSmsInsertCount()
    {
        $smsValidationTryLimit = AuthorizationPhoneChecker::SMS_VALIDATION_TRY;
        return $this->smsInsertCount <= $smsValidationTryLimit ? true : false;
    }

    /**
     * обнуляем счетчик для нового кода в смс
     */
    public function resetSmsInsertCount()
    {
        $this->smsInsertCount = 0;
        self::saveSessionStation();
    }

    /**
     * поднимаем счетчик водов кода на 1
     */
    public function updateSmsInsertCount()
    {
        $this->smsInsertCount += 1;
        self::saveSessionStation();
    }

    /**
     * поднимает счетчик запросов смс на 1
     */
    public function updateSmsCount()
    {
        $this->smsCount += 1;
        self::saveSessionStation();
    }
}