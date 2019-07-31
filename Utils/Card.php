<?php

namespace Taber\Siebel\Utils;

class Card
{
    private $ActivationDate;
    private $ActivationPlace;
    private $Barcode;
    private $BlockingDate;
    private $BlockingReason;
    private $CardType;
    private $IssueDate;
    private $IssuePlace;
    private $Status;
    private $StatusBeforeBlocking;
    private $ChequeId;

    const CARD_STATUS_ACTIVE = 'Active';
    const CARD_STATUS_BLOCKED = 'Blocked';
    const CARD_TYPE_PLASTIC = 'Plastic';
    const CARD_TYPE_VIRTUAL = 'Virtual';

    public function __construct()
    {
    }

    /**
     * @return mixed
     */
    public function getActivationDate()
    {
        return $this->ActivationDate;
    }

    /**
     * @return mixed
     */
    public function getActivationPlace()
    {
        return $this->ActivationPlace;
    }

    /**
     * @return mixed
     */
    public function getBarcode()
    {
        return $this->Barcode;
    }

    /**
     * @return mixed
     */
    public function getBlockingDate()
    {
        return $this->BlockingDate;
    }

    /**
     * @return mixed
     */
    public function getBlockingReason()
    {
        return $this->BlockingReason;
    }

    /**
     * @return mixed
     */
    public function getCardType()
    {
        return $this->CardType;
    }

    /**
     * @return mixed
     */
    public function getIssueDate()
    {
        return $this->IssueDate;
    }

    /**
     * @return mixed
     */
    public function getIssuePlace()
    {
        return $this->IssuePlace;
    }

    /**
     * @return mixed
     */
    public function getStatus()
    {
        return $this->Status;
    }

    /**
     * @return mixed
     */
    public function getStatusBeforeBlocking()
    {
        return $this->StatusBeforeBlocking;
    }

    /**
     * @return mixed
     */
    public function getChequeId()
    {
        return $this->ChequeId;
    }

    /**
     * @param mixed $ActivationDate
     */
    public function setActivationDate($ActivationDate): void
    {
        $this->ActivationDate = $ActivationDate;
    }

    /**
     * @param mixed $Barcode
     */
    public function setBarcode($Barcode): void
    {
        $this->Barcode = $Barcode;
    }

    /**
     * @param mixed $BlockingDate
     */
    public function setBlockingDate($BlockingDate): void
    {
        $this->BlockingDate = $BlockingDate;
    }

    /**
     * @param mixed $BlockingReason
     */
    public function setBlockingReason($BlockingReason): void
    {
        $this->BlockingReason = $BlockingReason;
    }

    /**
     * @param mixed $CardType
     */
    public function setCardType($CardType): void
    {
        $this->CardType = $CardType;
    }

    /**
     * @param mixed $IssueDate
     */
    public function setIssueDate($IssueDate): void
    {
        $this->IssueDate = $IssueDate;
    }

    /**
     * @param mixed $IssuePlace
     */
    public function setIssuePlace($IssuePlace): void
    {
        $this->IssuePlace = $IssuePlace;
    }

    /**
     * @param mixed $Status
     */
    public function setStatus($Status): void
    {
        $this->Status = $Status;
    }

    /**
     * @param mixed $StatusBeforeBlocking
     */
    public function setStatusBeforeBlocking($StatusBeforeBlocking): void
    {
        $this->StatusBeforeBlocking = $StatusBeforeBlocking;
    }

    /**
     * @param mixed $ChequeId
     */
    public function setChequeId($ChequeId): void
    {
        $this->ChequeId = $ChequeId;
    }



    /**
     * @param mixed $ActivationPlace
     */
    public function setActivationPlace($ActivationPlace): void
    {
        $this->ActivationPlace = $ActivationPlace;
    }


}