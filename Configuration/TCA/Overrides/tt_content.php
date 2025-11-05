<?php


use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;
use TYPO3\CMS\Extbase\Utility\ExtensionUtility;


$pluginSignature = ExtensionUtility::registerPlugin(
    extensionName: 'inquiry',
    pluginName: 'Form',
    pluginTitle: 'Inquiry Form',
    group: 'inquiry',
    pluginDescription: '',
);

ExtensionManagementUtility::addToAllTCAtypes(
    'tt_content',
    '--div--;Configuration,pi_flexform;Mail Optionen,',
    $pluginSignature,
    'after:subheader',
);

ExtensionManagementUtility::addPiFlexFormValue(
    '*',
    'FILE:EXT:inquiry/Configuration/FlexForm/MailOptions.xml',
    $pluginSignature
);

ExtensionUtility::registerPlugin(
    extensionName: 'inquiry',
    pluginName: 'QuickForm',
    pluginTitle: 'Quick Inquiry form for single item',
    group: 'inquiry',
    pluginDescription: '',
);

ExtensionUtility::registerPlugin(
    extensionName: 'inquiry',
    pluginName: 'AddItemForm',
    pluginTitle: 'Add Item Form',
    group: 'inquiry',
    pluginDescription: '',
);

ExtensionUtility::registerPlugin(
    extensionName: 'inquiry',
    pluginName: 'AddItem',
    pluginTitle: 'Add Item',
    group: 'inquiry',
    pluginDescription: '',
);

ExtensionUtility::registerPlugin(
    extensionName: 'inquiry',
    pluginName: 'CountItems',
    pluginTitle: 'Add Item',
    group: 'inquiry',
    pluginDescription: '',
);
