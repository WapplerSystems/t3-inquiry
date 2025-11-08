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

};

$boot();
unset($boot);
