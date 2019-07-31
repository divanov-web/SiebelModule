<?php
/**
 * Created by PhpStorm.
 * User: a.vartan
 * Date: 20.07.2018
 * Time: 11:11
 */

namespace Taber\Siebel\Utils;

/**
 * Class Phone
 * @package Taber\Siebel\Utils
 */
class Phone
{
    /**
     * @var string
     */
    private $phone;

    /**
     * Phone constructor.
     * @param $phone
     */
    public function __construct($phone)
    {
        $this->phone = self::validatePhone($phone);
    }

    /**
     * @param $phone
     * @return string
     */
    private function validatePhone($phone): string
    {
        $phone = '+' . preg_replace('/(\D)/', '', $phone);  // к виду +79998880066
        if($phone[1] == 8){
            $phone[1] = 7;
        }
        return $phone;
    }

    /**
     * @return string
     */
    public function getPhone()
    {
        return $this->phone;
    }
}