<?php

use TYPO3\CMS\Extbase\Utility\ExtensionUtility;
use WapplerSystems\Inquiry\Controller\InquiryController;

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
        'AddItem',
        [
            InquiryController::class => 'addItem',
        ],
        [
            InquiryController::class => 'addItem',
        ],
    );

    ExtensionUtility::configurePlugin(
        'Inquiry',
        'CountItems',
        [
            InquiryController::class => 'countItems',
        ],
        [
            InquiryController::class => 'countItems',
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
        'AddItemForm',
        [
            InquiryController::class => 'addItemForm',
        ],
        [
            InquiryController::class => 'addItemForm',
        ],
        ExtensionUtility::PLUGIN_TYPE_CONTENT_ELEMENT
    );
};

$boot();
unset($boot);
