<?php

use TYPO3\CMS\Extbase\Utility\ExtensionUtility;
use WapplerSystems\Inquiry\Controller\InquiryController;

$boot = static function (): void {

    ExtensionUtility::configurePlugin(
        'Inquiry',
        'FormPlaceholder',
        [
            InquiryController::class => 'formPlaceholder',
        ],
        [],
        ExtensionUtility::PLUGIN_TYPE_CONTENT_ELEMENT
    );
    ExtensionUtility::configurePlugin(
        'Inquiry',
        'Form',
        [
            InquiryController::class => 'form',
        ],
        [],
        ExtensionUtility::PLUGIN_TYPE_CONTENT_ELEMENT
    );

};

$boot();
unset($boot);
