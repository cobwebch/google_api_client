<?php
defined('TYPO3_MODE') || die('Access denied.');


call_user_func(

    function () {

        // Add new columns to all types
        \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addToAllTCAtypes('fe_users', 'connected_google_email', '', 'after:comments');

        $configuration = unserialize($GLOBALS['TYPO3_CONF_VARS']['EXT']['extConf']['google_api_client']);

        // Only load the plugin if required
        if (false === isset($configuration['google_api_client']) || true === (bool)$configuration['google_api_client']) {

            \TYPO3\CMS\Extbase\Utility\ExtensionUtility::registerPlugin(
                'Cobweb.GoogleApiClient',
                'Demo',
                'Google documents management'
            );

            $GLOBALS['TCA']['tt_content']['types']['list']['subtypes_excludelist']['googleapiclient_demo'] = 'layout, select_key, pages, recursive';
            #$GLOBALS['TCA']['tt_content']['types']['list']['subtypes_addlist']['googleapiclient_demo'] = 'pi_flexform';
        }
    }
);