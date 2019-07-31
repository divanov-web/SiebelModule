<?php

namespace Taber\Siebel\Utils;

class Discount
{
    /*
     array(5) {
    ["CurrentMonthDiscount"]=>
    string(1) "3"
    ["CurrentMonthSum"]=>
    string(1) "0"
    ["LastMonthSum"]=>
    string(1) "0"
    ["NextMonthDiscount"]=>
    string(1) "3"
     ["ListOfCard"]=>
    array(1) {
      ["Card"]=>
    array(11) {
      ["ActivationDate"]=>
    string(16) "05.10.2018 10:28"
      ["ActivationPlace"]=>
    string(14) "Кошелёк"
      ["Barcode"]=>
    string(13) "2989000000005"
      ["BlockingDate"]=>
    string(0) ""
      ["BlockingReason"]=>
    string(0) ""
      ["CardType"]=>
    string(7) "Virtual"
      ["IssueDate"]=>
    string(16) "05.10.2018 10:28"
      ["IssuePlace"]=>
    string(14) "Кошелёк"
      ["Status"]=>
    string(6) "Active"
      ["StatusBeforeBlocking"]=>
    string(0) ""
      ["ChequeId"]=>
    string(0) ""
    }
    }
    }
     */

    private $currentMonthDiscount = 0;
    private $currentMonthSum = 0;
    private $lastMonthSum = 0;
    private $nextMonthDiscount = 0;

    /**
     * Discount constructor.
     */
    public function __construct()
    {
    }

    /**
     * @param $currentMonthSum
     */
    public function setCurrentMonthSum($currentMonthSum = 0)
    {
        $this->currentMonthSum = $currentMonthSum;
    }

    /**
     * @return int
     */
    public function getCurrentMonthSum()
    {
        return $this->currentMonthSum;
    }

    /**
     * @param int $currentMonthDiscount
     */
    public function setCurrentMonthDiscount($currentMonthDiscount = 0)
    {
        $this->currentMonthDiscount = $currentMonthDiscount;
    }

    /**
     * @return int
     */
    public function getCurrentMonthDiscount()
    {
        return $this->currentMonthDiscount;
    }

    /**
     * @param $lastMonthSum
     */
    public function setLastMonthSum($lastMonthSum = 0)
    {
        $this->lastMonthSum = $lastMonthSum;
    }

    /**
     * @return int
     */
    public function getLastMonthSum()
    {
        return $this->lastMonthSum;
    }

    /**
     * @param $nextMonthDiscount
     */
    public function setNextMonthDiscount($nextMonthDiscount = 0)
    {
        $this->nextMonthDiscount = $nextMonthDiscount;
    }

    /**
     * @return int
     */
    public function getNextMonthDiscount()
    {
        return $this->nextMonthDiscount;
    }
}