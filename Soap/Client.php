<?php
/**
 * Class \Taber\Siebel\Soap\Client
 *
 * copy from Adv\GfDelivery\Soap\Client
 */

namespace Taber\Siebel\Soap;

use Taber\Siebel\Methods\SoapMethod;
use Bitrix\Main\Error;
use Bitrix\Main\Result;

/**
 * Class Client
 * @package Taber\Siebel\Soap
 */
class Client
{
	private static $instance = null;
    /** @var \SOAPClient $obSoapClient */
    private $obSoapClient = null;
    /** @var string $sWsdlUri */
    private $sWsdlUrl;
    /** @var array $arOptions */
    private $arOptions = array();
    /** @var array $arLastRequestTrace */
    private $arLastRequestTrace = array();
    /** @var bool $bSaveTrace */
    private $bSaveTrace = true;
    /**
     * @var bool - показывать лог запроса-ответа зибеля в браузере
     */
    private $showTraceOnPage = false;
    /** @var bool $bUtf8Site */
    private $bUtf8Site = true;
	/**
	 * @var integer - время выполнения отправки функции в зибель
	 */
	private $executeTime = false;

    /**
     * Client constructor.
     * @param int $methodType
     * @param array $arOptions
     */
	private function __construct(int $methodType, array $arOptions = array())
    {
        $this->setOptions($arOptions);

        //$obRequest = \Bitrix\Main\Application::getInstance()->getContext()->getRequest();
        //$iBasketItemId = $obRequest->getRequestUri();
        if($_REQUEST["SIEBEL_DEBUG"] == "Y") {
            $this->showTraceOnPage = true;
        }

        $arOptions = $this->getOptions();
        self::setWsdlUrl($methodType);

        // подключение
        $this->obSoapClient = new SoapClientTimeout($this->getWsdlUrl(), $arOptions);
        $this->obSoapClient->__setTimeout(SOAP_CURLOPT_TIMEOUT); //установка таймаута для класса SoapClientTimeout

        if((!defined('BX_UTF') || !BX_UTF) && (strtoupper(SITE_CHARSET) != 'UTF-8')) {
            $this->bUtf8Site = false;
        }
    }

	private function __clone() {}

    /**
     * @param int $methodType
     * @param array $arOptions
     * @return null|Client
     * @throws \SoapFault
     */
	public static function getInstance(int $methodType, array $arOptions = array())
	{
		if (null === self::$instance)
		{
			self::$instance = new self($methodType, $arOptions);
		}
		return self::$instance;
	}

    /**
     * @param array $arOptions (http://us2.php.net/manual/ru/soapclient.soapclient.php)
     */
    protected function setOptions($arOptions)
    {
        $this->arOptions = array();
        foreach($arOptions as $sOptionName => $mOptionVal) {
            $this->arOptions[$sOptionName] = $mOptionVal;
        }

        if(!isset($this->arOptions['soap_version'])) {
            $this->arOptions['soap_version'] = SOAP_1_1;
        }

        if(!isset($this->arOptions['connection_timeout'])) {
            $this->arOptions['connection_timeout'] = 5;
        }

        if(!isset($this->arOptions['encoding'])) {
            $this->arOptions['encoding'] = 'UTF-8';
        }

        if(!isset($this->arOptions['trace'])) {
            $this->arOptions['trace'] = true;
        }

        //тайм-аут в секундах для соединения с SOAP-сервисом. Опция не устанавливает тайм-аут для сервисов с медленными ответами. Для ограничения времени ожидания вызовов используется default_socket_timeout
		if(!isset($this->arOptions['connection_timeout'])) {
			$this->arOptions['connection_timeout'] = 20; //на наши проблемы с соединением не влияет
		}

        if(!isset($this->arOptions['cache_wsdl'])) {
            // TODO: не может быть одна константа кеша для разных сервисов. Надо сделать отдельную для siebel
            $this->arOptions['cache_wsdl'] = defined('GF_DELIVERY_SOAP_CLIENT_WSDL_CACHE') ? GF_DELIVERY_SOAP_CLIENT_WSDL_CACHE : WSDL_CACHE_BOTH;
        }
    }

    /**
     * @return array
     */
    public function getOptions()
    {
        return $this->arOptions;
    }

    /**
     * @param $methodType
     * @return mixed
     * @throws \Taber\Siebel\SiebelException\SiebelClientSettingsException
     * @throws \Taber\Siebel\SiebelException\SiebelClientUrlException
     */
    public function setWsdlUrl($methodType)
    {
        $siebelSettings = new SiebelSettings($methodType);
        $this->sWsdlUrl = $siebelSettings->getWsdlUrl();
    }

    public function getWsdlUrl()
    {
        return $this->sWsdlUrl;
    }

    /**
     * @param string $sOptionName
     * @return mixed
     */
    public function getOptionValue($sOptionName)
    {
        return $this->arOptions[$sOptionName] ?? '';
    }

    /**
     * @return string
     */
    public function getLogin()
    {
        return $this->getOptionValue('login');
    }

    /**
     * @return string
     */
    public function getPassword()
    {
        return $this->getOptionValue('password');
    }

	public function getExcecuteTime()
	{
		return $this->executeTime;
	}

    /**
     * @param $mSoapHeaders
     */
    public function setSoapHeaders($mSoapHeaders)
    {
        try {
            if($mSoapHeaders) {
                $this->obSoapClient->__setSoapHeaders($mSoapHeaders);
            }
        } catch(\SOAPFault $obSoapFault) {
            //
        }
    }

    /**
     * @return bool
     */
    public function wasInited()
    {
        return !empty($this->obSoapClient);
    }

    /**
     * @return \SOAPClient
     */
    public function getSoapClient()
    {
        return $this->obSoapClient;
    }

	/**
	 * @return bool
	 */
	public function getSaveTrace()
	{
		return $this->bSaveTrace && SIEBEL_ADD_TRACE_TO_LOG_FILE;
	}

    /**
     * @return bool
     */
    public function getShowTraceOnPage()
    {
        return $this->showTraceOnPage;
    }

    /**
     * @return array
     */
    public function getFunctions()
    {
        return $this->wasInited() ? $this->obSoapClient->__getFunctions() : array();
    }

    /**
     * @return array
     */
    public function getLastRequestTrace()
    {
        return $this->arLastRequestTrace;
    }

    /**
     * @param SoapMethod $soapMethod
     * @param bool $bReturnHeader
     * @param null $arSoapHeaders
     * @return Result
     */
	public function call(SoapMethod $soapMethod, $bReturnHeader = false, $arSoapHeaders = null)
    {
        $obResult = new Result();

        $arData = array();

        if($this->wasInited()) {
            $this->arLastRequestTrace = array();
            try {
                $arResponseHeader = [];
				$time_start = microtime(true);
                $arData['BODY'] = $this->obSoapClient->__soapCall(
                    $soapMethod->getMethodName(),
                    $soapMethod->getSendingParams(),
                    null,
                    $arSoapHeaders,
                    $arResponseHeader
                );
				$time_end = microtime(true);
				$this->executeTime = round($time_end - $time_start , 4); //Время выполнения метода на стороне Siebel для логов
                // конвертируем кодировку ответа в кодировку сайта
                if(is_object($arData['BODY'])) {
                    foreach($arData['BODY'] as $sProp => $mVal) {
                        $arData['BODY']->$sProp = $this->convertToSiteCharset($mVal);
                    }
                } else {
                    $arData['BODY'] = $this->convertToSiteCharset($arData['BODY']);
                }

                if($bReturnHeader) {
                    $arData['HEADER'] = $arResponseHeader;
                }
            } catch(\SOAPFault $obSoapFault) {
                $obResult->addError(
                    new Error(
                        $this->ParseSoapFault($obSoapFault)['ERROR_MSG'],
                        'E1'
                    )
                );
            }
            if($this->bSaveTrace) {
                $this->arLastRequestTrace = array(
                    'REQUEST' => $this->obSoapClient->__getLastRequest(),
                    'RESPONSE' => $this->obSoapClient->__getLastResponse(),
                    'RESPONSE_HEADER' => $arResponseHeader,
                    'RESPONSE_HEADER_' => $this->obSoapClient->__getLastResponseHeaders(),
					"SOAP_METHOD" => $soapMethod->getMethodName()
                );
            }
        } else {
            $obResult->addError(
                new Error(
                    'SOAP client not inited',
                    'E0'
                )
            );
        }

        $obResult->setData($arData);

        return $obResult;
    }

    /**
     * @param $obSoapFault
     * @return array
     */
    protected function parseSoapFault($obSoapFault)
    {
        $arReturn = array(
            'ERROR_CODE' => '',
            'ERROR_MSG' => '',
            'ERROR_DETAILS' => array()
        );
        if($obSoapFault && is_object($obSoapFault) && is_a($obSoapFault, '\\SoapFault')) {
            // пробуем нормализовать сообщения об ошибках
            if(isset($obSoapFault->detail) && is_object($obSoapFault->detail) && is_a($obSoapFault->detail, 'stdClass')) {
                foreach($obSoapFault->detail as $mValue) {
                    if(is_object($mValue) && is_a($mValue, 'SoapVar')) {
                        if(isset($mValue->enc_value->errorCode)) {
                            $arReturn['ERROR_CODE'] = $mValue->enc_value->errorCode;
                        }
                        if(isset($mValue->enc_value->errorParams) && is_array($mValue->enc_value->errorParams)) {
                            foreach($mValue->enc_value->errorParams as $obErrItem) {
                                $arReturn['ERROR_DETAILS'][] = (array) $obErrItem;
                            }
                        }
                        if(isset($mValue->enc_value->localizedMessage)) {
                            $arReturn['ERROR_MSG'] = $mValue->enc_value->localizedMessage;
                        } elseif(isset($mValue->enc_value->message)) {
                            $arReturn['ERROR_MSG'] = $mValue->enc_value->message;
                        }
                    }
                }
            }

            if($arReturn['ERROR_CODE'] === '') {
                $arReturn['ERROR_CODE'] = $obSoapFault->faultcode;
            }
            if($arReturn['ERROR_MSG'] === '') {
                $arReturn['ERROR_MSG'] = $obSoapFault->getMessage();
            }
            if($arReturn['ERROR_MSG'] === '') {
                $arReturn['ERROR_MSG'] = $obSoapFault->__toString();
            }
            // конвертируем кодировку ответа в кодировку сайта
            $arReturn = $this->convertToSiteCharset($arReturn);
        }

        return $arReturn;
    }

    /**
     * @param mixed $mConvert
     * @return array
     */
    protected function convertToSiteCharset($mConvert)
    {
        if(!$this->bUtf8Site) {
            if(is_array($mConvert)) {
                foreach($mConvert as $sKey => $mValue) {
                    if(is_string($mValue)) {
                        if(strlen($mValue)) {
                            $mConvert[$sKey] = $GLOBALS['APPLICATION']->convertCharset($mValue, 'UTF-8', SITE_CHARSET);
                        }
                    } elseif(is_array($mValue)) {
                        $mConvert[$sKey] = $this->convertToSiteCharset($mValue);
                    }
                }
            } elseif(is_string($mConvert)) {
                $mConvert = $GLOBALS['APPLICATION']->convertCharset($mConvert, 'UTF-8', SITE_CHARSET);
            }
        }

        return $mConvert;
    }

    /**
     * @param mixed $mConvert
     * @return array
     */
    protected function convertToUtf8($mConvert)
    {
        if(!$this->bUtf8Site) {
            if(is_array($mConvert)) {
                foreach($mConvert as $sKey => $mValue) {
                    if(is_string($mValue)) {
                        if(strlen($mValue)) {
                            $mConvert[$sKey] = $GLOBALS['APPLICATION']->convertCharset($mValue, SITE_CHARSET, 'UTF-8');
                        }
                    } elseif(is_array($mValue)) {
                        $mConvert[$sKey] = $this->convertToUtf8($mValue);
                    }
                }
            } elseif(is_string($mConvert)) {
                $mConvert = $GLOBALS['APPLICATION']->convertCharset($mConvert, SITE_CHARSET, 'UTF-8');
            }
        }

        return $mConvert;
    }
}
