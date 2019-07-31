<?php
/**
 * Created by PhpStorm.
 * User: a.vartan
 * Date: 28.09.2018
 * Time: 12:25
 */

namespace Taber\Siebel\Utils;

use Bitrix\Main\Application;
use Bitrix\Main\DB\SqlQueryException;

/**
 * Class PinCodeTable
 * @package Taber\Siebel\Utils
 */
class PinCodeTable
{
    /**
     * таблица хранения смс кодов
     */
    const TABLE_NAME = 'sms_user_code';

    /**
     * @throws \Bitrix\Main\Db\SqlQueryException
     */
    private static function create()
    {
        Application::getConnection()->query(
            "create table if not exists " . self::TABLE_NAME . " (
            id int(11) NOT NULL AUTO_INCREMENT,
            code int(4) not null default 0,  
            web_client int(10) not null default 0,
            primary key (id));");
    }

    /**
     * @param PinCode $code
     * @throws SqlQueryException
     */
    public static function insert(PinCode $code)
    {
        $id = self::getId($code);
        if($id){
            Application::getConnection()->query(
                'UPDATE ' . self::TABLE_NAME .
                ' SET code=' . $code->getCode() . ' WHERE id=' . $id . ';'
            );
        }
        else {
            Application::getConnection()->query(
                'INSERT INTO ' . self::TABLE_NAME .
                ' (code, web_client) VALUES (' . $code->getCode() . ', ' . $code->getWebClientId() . ');'
            );
        }
    }

    /**
     * @param PinCode $code
     * @return mixed
     * @throws SqlQueryException
     */
    public static function getId(PinCode $code)
    {
        $id = Application::getConnection()->query(
            'SELECT id FROM ' . self::TABLE_NAME .
            ' WHERE web_client=' . $code->getWebClientId() . ';'
        )->fetch()['id'];
        return $id;
    }

    /**
     * @param PinCode $code
     * @return array|false
     * @throws SqlQueryException
     */
    public static function get(PinCode $code)
    {
        if($code->getWebClientId()) {
            $pinCode = Application::getConnection()->query(
                'SELECT * FROM ' . self::TABLE_NAME .
                ' WHERE web_client=' . $code->getWebClientId() . ';'
            )->fetch();
        }else{
            $pinCode = 0;
        }
        return $pinCode;
    }

    /**
     * @param PinCode $code
     * @param bool $last_try
     * @throws SqlQueryException
     */
    public static function write(PinCode $code, $last_try = false)
    {
        try {
            self::insert($code);
        } catch (SqlQueryException $e) {
            if (!$last_try) {
                self::create();  // создаем таблицу
                self::write($code, true);
            } else {
                // todo: выбрасывать исключение
            }
        }
    }

    /**
     * @return array
     */
    public static function getFields()
    {
        return [
            'code',  // код из смс
            'web_client',  // юзер id
        ];
    }

    public static function writeFastRegistrationCode($code, $webUser){

        try {
            $id = Application::getConnection()->query(
                'SELECT id FROM ' . self::TABLE_NAME .
                ' WHERE web_client=' . $webUser . ';'
            )->fetch()['id'];
            if($id){
                Application::getConnection()->query(
                    'UPDATE ' . self::TABLE_NAME .
                    ' SET code=' . $code . ' WHERE id=' . $id . ';'
                );
            }
            else {
                Application::getConnection()->query(
                    'INSERT INTO ' . self::TABLE_NAME .
                    ' (code, web_client) VALUES (' . $code . ', ' . $webUser . ');'
                );
            }
        } catch (SqlQueryException $e) {
        }
    }

    public static function getFastRegistrationCode($webUser){
        try {
            $code = Application::getConnection()->query(
                'SELECT id, code FROM ' . self::TABLE_NAME .
                ' WHERE web_client=' . $webUser . ';'
            )->fetch()['code'];
            return $code;
        } catch (SqlQueryException $e) {
        }
    }
}