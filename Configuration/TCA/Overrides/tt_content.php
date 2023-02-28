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
    }
);
