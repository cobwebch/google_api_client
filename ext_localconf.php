<?php
defined('TYPO3_MODE') || die('Access denied.');

call_user_func(
    function () {

        \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addTypoScript(
            'google_api_client',
            'constants',
            '<INCLUDE_TYPOSCRIPT: source="FILE:EXT:google_api_client/Configuration/TypoScript/constants.typoscript">'
        );
        \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addTypoScript(
            'google_api_client',
            'setup',
            '<INCLUDE_TYPOSCRIPT: source="FILE:EXT:google_api_client/Configuration/TypoScript/setup.typoscript">'
        );

        $configuration = unserialize($GLOBALS['TYPO3_CONF_VARS']['EXT']['extConf']['google_api_client']);

        if (false === isset($configuration['google_api_client']) || true === (bool)$configuration['google_api_client']) {
            \TYPO3\CMS\Extbase\Utility\ExtensionUtility::configurePlugin(
                'Cobweb.GoogleApiClient',
                'Demo',
                [
                    'GoogleDocument' => 'index, create, show, remove',
                    'GooglePermission' => 'change, changeAllForFile, changeAllForUser, remove, removeAllForFile, removeAllForUser',
                ],
                // non-cacheable actions
                [
                    'GoogleDocument' => 'index, create, show, remove',
                    'GooglePermission' => 'change, changeAllForFile, changeAllForUser, remove, removeAllForFile, removeAllForUser',
                ]
            );
        }

    }
);