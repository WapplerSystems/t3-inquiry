<?php

return [
    'ctrl' => [
        'title' => 'LLL:EXT:inquiry/Resources/Private/Language/backend.xlf:tx_inquiry_domain_model_requesttexttemplate',
        'label' => 'title',
        'tstamp' => 'tstamp',
        'crdate' => 'crdate',
        'cruser_id' => 'cruser_id',
        'delete' => 'deleted',
        'languageField' => 'sys_language_uid',
        'default_sortby' => 'sorting',
        'sortby' => 'sorting',
        'enablecolumns' => [
            'disabled' => 'hidden',
        ],
        'searchFields' => 'title,body',
        #'iconfile' => 'EXT:inquiry/Resources/Public/Icons/tx_inquiry_domain_model_requesttexttemplate.svg',
    ],
    'types' => [
        '1' => ['showitem' => 'title, body, hidden'],
    ],
    'columns' => [
        'title' => [
            'exclude' => true,
            'label' => 'LLL:EXT:inquiry/Resources/Private/Language/backend.xlf:tx_inquiry_domain_model_requesttexttemplate.title',
            'config' => [
                'type' => 'input',
                'size' => 50,
                'eval' => 'trim,required',
            ],
        ],
        'body' => [
            'exclude' => true,
            'label' => 'LLL:EXT:inquiry/Resources/Private/Language/backend.xlf:tx_inquiry_domain_model_requesttexttemplate.body',
            'config' => [
                'type' => 'text',
                'rows' => 10,
                'eval' => 'required',
            ],
        ],
    ],
];
