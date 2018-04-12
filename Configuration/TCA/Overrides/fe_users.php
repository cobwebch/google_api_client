<?php
if (!defined('TYPO3_MODE')) die ('Access denied.');

$tca = [
    'columns' => [
        'connected_google_email' => [
            'displayCond' => 'FIELD:uid:>:0',
            'label' => 'LLL:EXT:google_api_client/Resources/Private/Language/fe_users.xlf:connected_google_email',
            'config' => [
                'type' => 'input',
                'eval' => 'trim,unique',
            ]
        ],
        'google_permission_id' => [
            'label' => 'LLL:EXT:google_api_client/Resources/Private/Language/fe_users.xlf:google_permission_id',
            'config' => [
                'type' => 'input',
                'readOnly' => true,
                'eval' => 'trim,unique',
            ]
        ],
    ],
];

\TYPO3\CMS\Core\Utility\ArrayUtility::mergeRecursiveWithOverrule($GLOBALS['TCA']['fe_users'], $tca);
