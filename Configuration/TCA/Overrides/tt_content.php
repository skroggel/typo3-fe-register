<?php
defined('TYPO3_MODE') || die('Access denied.');

call_user_func(

    function (string $extKey) {

        //=================================================================
        // Register Plugins
        //=================================================================
        \TYPO3\CMS\Extbase\Utility\ExtensionUtility::registerPlugin(
            'Madj2k.FeRegister',
            'Auth',
            'FE Register: Login'
        );

        \TYPO3\CMS\Extbase\Utility\ExtensionUtility::registerPlugin(
            'Madj2k.FeRegister',
            'Logout',
            'FE Register: Logout'
        );

        \TYPO3\CMS\Extbase\Utility\ExtensionUtility::registerPlugin(
            'Madj2k.FeRegister',
            'Password',
            'FE Register: Passwort'
        );

        \TYPO3\CMS\Extbase\Utility\ExtensionUtility::registerPlugin(
            'Madj2k.FeRegister',
            'Welcome',
            'FE Register: Willkommen'
        );

        \TYPO3\CMS\Extbase\Utility\ExtensionUtility::registerPlugin(
            'Madj2k.FeRegister',
            'UserEdit',
            'FE Register: FrontendUser (editieren)'
        );

        \TYPO3\CMS\Extbase\Utility\ExtensionUtility::registerPlugin(
            'Madj2k.FeRegister',
            'UserDelete',
            'FE Register: FrontendUser (löschen)'
        );

        \TYPO3\CMS\Extbase\Utility\ExtensionUtility::registerPlugin(
            'Madj2k.FeRegister',
            'Group',
            'FE Register: FrontendUserGroup'
        );

        \TYPO3\CMS\Extbase\Utility\ExtensionUtility::registerPlugin(
            'Madj2k.FeRegister',
            'Info',
            'FE Register: Info'
        );

        //=================================================================
        // Add Flexforms
        //=================================================================
        $pluginName = strtolower('Auth');
        $extensionName = strtolower(\TYPO3\CMS\Core\Utility\GeneralUtility::underscoredToUpperCamelCase($extKey));
        $pluginSignature = $extensionName . '_' . $pluginName;
        $GLOBALS['TCA']['tt_content']['types']['list']['subtypes_excludelist'][$pluginSignature] = 'layout,select_key,pages';
        $GLOBALS['TCA']['tt_content']['types']['list']['subtypes_addlist'][$pluginSignature] = 'pi_flexform';
        \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addPiFlexFormValue(
            $pluginSignature,
            'FILE:EXT:' . $extKey . '/Configuration/FlexForms/Login.xml'
        );

    },
    'fe_register'
);
