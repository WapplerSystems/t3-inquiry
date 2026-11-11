<?php

$EM_CONF[$_EXTKEY] = [
    'title' => 'Inquiry Test Consumer',
    'description' => 'Minimal consumer used by the test suite: resolves pages as inquiry items.',
    'category' => 'example',
    'version' => '1.0.0',
    'state' => 'stable',
    'constraints' => [
        'depends' => [
            'typo3' => '14.0.0-14.3.99',
            'inquiry' => '',
        ],
    ],
];
