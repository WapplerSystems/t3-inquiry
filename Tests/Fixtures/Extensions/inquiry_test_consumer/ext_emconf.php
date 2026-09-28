<?php

$EM_CONF[$_EXTKEY] = [
    'title' => 'Inquiry Test Consumer',
    'description' => 'Minimal consumer used by the test suite: resolves pages as inquiry items.',
    'category' => 'example',
    'version' => '1.0.0',
    'state' => 'stable',
    'constraints' => [
        'depends' => [
            'typo3' => '13.4.0-13.4.99',
            'inquiry' => '',
        ],
    ],
];
