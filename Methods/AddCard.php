<?php
/**
 * Created by PhpStorm.
 * User: a.vartan
 * Date: 02.11.2018
 * Time: 15:12
 */

namespace Taber\Siebel\Methods;


class AddCard extends AuthSoapMethod
{
    private $card;
    /**
     * @var array
     * Описание возможных аттрибутов полей:
     * require (value) - поле обязательно для заполнения и отправки в Siebel
     * default (key => value) - значение по умолчанию
     * subarray (key => array) - подмассив, будет сформирован как подуровень в xml
     * additional(value) - значение этого поля будет взято из массива $arAvaibleAdditionalParams с таким же ключом. Используется только для набора элементов, цикл по товарам и тп
     * repeat (key => array) - массив входящий значчений будет выведен в цикле
     * auto - поле генерируется автоматически в коде, не используется в коде, только чтобы правило было понятно
     * hideOnMap - этот параметр не будет выведен в отображении карты требуемых параметров (то есть его не надо передавать в метод отправки)
     * false - просто поле без особенностей
     */
    protected static $arAvailableParams = [
        "IntegrationId" => [
            "require",
            "auto",
            "hideOnMap"
        ],
        "SysType" => [
            "require",
            "default" => "podrygka.ru"
        ],
        "InputIO" => [
            "hideOnMap",
            "subarray" => [
                "AddCard" => [
                    "hideOnMap",
                    "subarray" => [
                        "SiebelId" => ["require"],
                        "CardNumber" => false,
                    ]
                ]
            ]
        ]
    ];

    /**
     * получаем ответ от Siebel и записываем его в поля
     *
     * @throws \Taber\Siebel\SiebelException\SiebelErrorRequestException
     * @throws \Taber\Siebel\SiebelException\SiebelErrorResponceException
     */
    public function execute(): void
    {
        parent::execute();
        $output = $this->getOutput();
        $this->card = $output["AddCard"];
    }

}