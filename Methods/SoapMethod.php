<?php
/**
 * Class Taber\Siebel\Methods\SoapMethod
 *
 * Created by PhpStorm.
 * User: d.ivanov
 * Date: 06.06.2018
 * Time: 14:46
 */
namespace  Taber\Siebel\Methods;

use Taber\Siebel\SiebelException\SiebelErrorRequestException;
use Taber\Siebel\SiebelException\SiebelRequiredFieldException;
use Taber\Siebel\SiebelException\SiebelErrorResponceException;
use Taber\Siebel\SiebelException\SiebelWrongDataException;
use Taber\Siebel\Soap\Client;
use Taber\Siebel\Log;
use Taber\Siebel\Soap\SiebelSettings;

/**
 * Class Model
 * @package Taber\Siebel\Models
 */
abstract class SoapMethod
{
    protected $methodName;

	protected $arParams = array();

	protected $arErrors;

	protected $arResult;

	protected $arSendParams = array();

	/** @var Client $obSoapClient */
	protected $obSoapClient = null;

	protected $roundDiscountID = "1-7535407"; //скидка на округление

    protected static $arAvailableParams = [];

    const METHOD_TYPE = SiebelSettings::WSDL_ORDER_METHODS;


	public function getMethodName(){
	    return $this->methodName;
    }

    /**
     * SoapMethod constructor.
     * @param array $arInputParams
     * @throws SiebelRequiredFieldException
     * @throws SiebelWrongDataException
     * @throws \ReflectionException
     * @throws \SoapFault
     */
	public function __construct(array $arInputParams)
	{
	    $this->methodName = (new \ReflectionClass($this))->getShortName();
		$this->obSoapClient = Client::getInstance(static::METHOD_TYPE);

		$this->arErrors = new \Taber\Podrygka\AdminImport\UserError();

		$this->arSendParams = $this->normalizeParams($this->getAvailableParams(), $arInputParams);
		if(!$this->arErrors->checkErrors()) {
			throw new SiebelRequiredFieldException($this->arErrors->getErrors());
		}
	}

    /**
     * @return mixed
     */
	public static function getAvailableParams() {
		return static::$arAvailableParams;
	}

	/**
	 * @return array - Возвращает карту требуемых параметров. В коде не используется, специально для Насти
	 */
	public static function getFieldStructure() {
		return self::structureParams(static::$arAvailableParams);
	}

	/**
	 * @return array
	 */
	public function getParams()
	{
		return $this->arParams;
	}

	/**
	 * @return array
	 */
	public function getSendingParams()
	{
		return $this->arSendParams;
	}


	public function getResult()
	{
		return $this->arResult;
	}

	public function getData()
	{
		return $this->arResult->getData();
	}

	public function getBody()
	{
		return $this->arResult->getData()["BODY"];
	}

	// todo: можно ли перенести переделывание обьекта в массив в клиент?
	public function getOutput()
	{
		$output = objectToArray($this->getData()["BODY"]["OutputIO"]);
		return $output;
	}

	public static function createMethod($arParams = []) {
		$result =  new static($arParams);
		$result->execute();
		return $result;
	}

    /**
     * @param array $arAvailableParams - стукрута параметров, которую требует SOAP функция
     * @param array $arInputParams - входящие парамтеры приложения со своей структурой
     * @return array - Сруктурированный массив под SOAP с введёнными данными
     * @throws SiebelWrongDataException
     */
	private function normalizeParams(array $arAvailableParams, array $arInputParams)
	{
		$final = [];
		foreach($arAvailableParams as $key => $value) {
			//Значение по-умолчанию, если параметр не передан
			if($value["default"] && !$arInputParams[$key]) {
				$arInputParams[$key] = $value["default"];
			}
			//Предустановленные значения
            $integrationId = "site_" . uniqid();
			if($key == "IntegrationId") {
				$arInputParams[$key] = $integrationId;
			}
			if($key == "ChequeId" && !isset($arInputParams[$key]) && in_array("auto", $value)) {
                $arInputParams[$key] = $integrationId;
            }
			//проверка на обязательные поля
			if(in_array("require", $value) && !(isset($arInputParams[$key]))) {
				$this->arErrors->addError("Поле " . $key . " не заполнено");
			}

			if($value["subarray"]) {
				$final[$key] = $this->normalizeParams($value["subarray"], $arInputParams);
			} elseif($value["additional"] && !empty($arInputParams[$key])) {
				$final[$key] = $this->normalizeParams($value["additional"], $arInputParams[$key]);
			} elseif($value["repeat"]) {
				foreach ($arInputParams as $item) {
					if(!is_array($item))
						throw new SiebelWrongDataException("В свойстве repeat может быть указан только массив из повторяющихся элементов");
					$final[]= $this->normalizeParams($value["repeat"], $item);
				}
			} else {
				$final[$key] = $arInputParams[$key];
			}
		}
		return $final;
	}

    /**
     * @throws SiebelErrorRequestException
     * @throws SiebelErrorResponceException
     */
	public function execute() {
		$this->arResult = $this->obSoapClient->call($this);
		//сохраняем лог запросов, если логгирование включено в классе Client
		if($this->obSoapClient->getSaveTrace()) {
			$this->saveTrace($this->obSoapClient);
		}
        if($this->obSoapClient->getShowTraceOnPage()) {
            $this->showTrace($this->obSoapClient);
        }
		if(!$this->arResult->isSuccess()) {
			throw new SiebelErrorRequestException($this->arResult->getErrors());
		} elseif(strlen($this->Getbody()["ErrorCode"]) > 0) {
			throw new SiebelErrorResponceException($this->Getbody());
		}
	}

	/**
	 * Записывает запросы и ответы из Siebel в лог файл
	 * @param Client $obSoapClient
	 */
	public function saveTrace(Client $obSoapClient) {
		Log::addTraceToLogFile($obSoapClient);
	}

    /**
     * Показывает запросы в браузере
     * @param Client $obSoapClient
     */
    public function showTrace(Client $obSoapClient) {
        Log::showTraceOnPage($obSoapClient);
    }

    /**
     * @param array $arAvaibleParams
     * @param array $final
     * @return array
     */
	private static function structureParams(array $arAvaibleParams, $final = [])
	{
		foreach($arAvaibleParams as $key => $value) {

			if(in_array("hideOnMap", $value)) {
				if($value["subarray"] || $value["repeat"]) //иначе пропускаем это поле
					$final = self::structureParams($value["subarray"] ? $value["subarray"] : $value["repeat"], $final);
			} elseif($value["subarray"]) {
				$final[$key] = self::structureParams($value["subarray"]);
			} elseif($value["additional"]) {
				$final[$key] = self::structureParams($value["additional"]);
			} elseif($value["repeat"]) {
				$final[$key] = self::structureParams($value["repeat"]);
			} else {
				$final[$key] = $value;
			}
		}
		return $final;
	}

	/**
	 * Когда из СОАП может вернуться массив элементов или просто элемент, эта функция приводит результат к массиву элементов.
	 * @param $itemArray
	 * @return array
	 */
	public static function repairItemArray($itemArray) {
		if(empty($itemArray[0]) && !empty($itemArray)) {
			return [0 => $itemArray];
		} else {
			return $itemArray;
		}
	}
}