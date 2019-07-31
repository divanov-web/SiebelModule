<?php

namespace Taber\Siebel\Application;


use Taber\Siebel\Utils\SiebelClient;
use Taber\Siebel\Utils\WebClient;

/**
 * Class PersonalProfile
 * @package Taber\Siebel\Application
 */
class PersonalProfile
{
    private $_webClient;
    private $_siebelClient;

    const BLOCKED_CLIENT = 'BLOCKED_CLIENT';

    /**
     * PersonalProfile constructor.
     * @param WebClient $webClient
     */
    public function __construct(WebClient $webClient)
    {
        $this->_webClient = $webClient;
        $this->_siebelClient = new SiebelClient($this->_webClient->getSiebelId());
        if(strpos($this->_siebelClient->getSiebelClientError(), self::BLOCKED_CLIENT)) {
            global $USER;
            $USER->Logout();
            LocalRedirect('/');
        }
    }

    /**
     * @return SiebelClient
     */
    public function getSiebelProfileInformation(): SiebelClient
    {
        return $this->_siebelClient;
    }

    /**
     * @return WebClient
     */
    public function getWebClientInformation(): WebClient
    {
        return $this->_webClient;
    }
}