<?php
require $_SERVER['DOCUMENT_ROOT'] . '/bitrix/header.php';

/**
 * @var CMain $APPLICATION
 */

$APPLICATION->IncludeComponent(
	'exam31.ticket:examelements',
	'.default',
    array(
        "SEF_FOLDER" => "/exam31/",
        "DEFAULT_PAGE" => "list",
        "COMPONENT_TEMPLATE" => ".default",
        "SEF_MODE" => "Y",
        "SEF_URL_TEMPLATES" => array(
            "list" => "",
            "detail" => "detail/#ELEMENT_ID#/",
            "info" => "info/#ELEMENT_ID#/",
        )
    ),
);

require $_SERVER['DOCUMENT_ROOT'] . '/bitrix/footer.php';