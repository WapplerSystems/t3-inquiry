<?php

use TYPO3\CMS\Extbase\Utility\ExtensionUtility;
use WapplerSystems\Inquiry\Controller\InquiryController;

array_push($GLOBALS['TYPO3_CONF_VARS']['FE']['cacheHash']['excludedParameters'], 'tx_inquiry[uid]', 'tx_inquiry[type]', 'tx_inquiry[hash]', 'tx_inquiry[items]', 'tx_inquiry[identifier]');


$boot = static function (): void {


    ExtensionUtility::configurePlugin(
        'Inquiry',
        'Form',
        [
            InquiryController::class => 'form',
        ],
        [
            InquiryController::class => 'form',
        ],
        ExtensionUtility::PLUGIN_TYPE_CONTENT_ELEMENT
    );

    ExtensionUtility::configurePlugin(
        'Inquiry',
        'QuickForm',
        [
            InquiryController::class => 'quickForm',
        ],
        [
            InquiryController::class => 'quickForm',
        ],
        ExtensionUtility::PLUGIN_TYPE_CONTENT_ELEMENT
    );

    ExtensionUtility::configurePlugin(
        'Inquiry',
        'ItemsList',
        [
            InquiryController::class => 'getItems',
        ],
        [
            InquiryController::class => 'getItems',
        ],
    );

    ExtensionUtility::configurePlugin(
        'Inquiry',
        'RemoveItem',
        [
            InquiryController::class => 'removeItem',
        ],
        [
            InquiryController::class => 'removeItem',
        ],
    );

    ExtensionUtility::configurePlugin(
        'Inquiry',
        'ToggleItem',
        [
            InquiryController::class => 'toggleItemStatus',
        ],
        [
            InquiryController::class => 'toggleItemStatus',
        ],
    );

    ExtensionUtility::configurePlugin(
        'Inquiry',
        'PreloadItems',
        [
            InquiryController::class => 'preloadItems',
        ],
        [
            InquiryController::class => 'preloadItems',
        ],
    );

    ExtensionUtility::configurePlugin(
        'Inquiry',
        'GeneratePdf',
        [
            InquiryController::class => 'generatePdf',
        ],
        [
            InquiryController::class => 'generatePdf',
        ],
    );

    ExtensionUtility::configurePlugin(
        'Inquiry',
        'SaveListSnapshot',
        [
            InquiryController::class => 'saveListSnapshot',
        ],
        [
            InquiryController::class => 'saveListSnapshot',
        ],
    );

    ExtensionUtility::configurePlugin(
        'Inquiry',
        'GetPrefill',
        [
            InquiryController::class => 'getPrefill',
        ],
        [
            InquiryController::class => 'getPrefill',
        ],
    );

};

$boot();
unset($boot);
