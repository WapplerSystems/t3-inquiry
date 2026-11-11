<?php


$EM_CONF[$_EXTKEY] = [
    'title' => 'Inquiry',
    'description' => 'The extension is a universal TYPO3 extension that allows visitors to create enquiries for quotes.',
    'category' => 'fe',
    'author' => 'Sven Wappler',
    'author_email' => 'typo3YYYY@wappler.systems',
    'state' => 'stable',
    'clearCacheOnLoad' => 1,
    'author_company' => 'WapplerSystems',
    'version' => '13.0.0',
    'constraints' => [
        'depends' => [
            'typo3' => '14.0.0-14.3.99',
        ],
    ],
];
