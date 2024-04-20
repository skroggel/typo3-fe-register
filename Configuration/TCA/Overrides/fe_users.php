<?php
defined('TYPO3_MODE') || die('Access denied.');

call_user_func(
    function (string $extKey) {

        $tempCols = [

            'tx_feregister_mobile' => [
                'label'=>'LLL:EXT:fe_register/Resources/Private/Language/locallang_db.xlf:tx_feregister_domain_model_frontenduser.tx_feregister_mobile',
                'exclude' => 0,
                'config'=>[
                    'type'=>'input',
                    'size' => 20,
                    'max' => '256',
                    'eval' => 'trim'
                ],
            ],

            'tx_feregister_gender' => [
                'label'=>'LLL:EXT:fe_register/Resources/Private/Language/locallang_db.xlf:tx_feregister_domain_model_frontenduser.tx_feregister_gender',
                'exclude' => 0,
                'config'=>[
                    'type' => 'select',
                    'renderType' => 'selectSingle',
                    'minitems' => 0,
                    'maxitems' => 1,
                    'default' => 99,
                    'items' => [
                        ['LLL:EXT:fe_register/Resources/Private/Language/locallang_db.xlf:tx_feregister_domain_model_frontenduser.tx_feregister_gender.I.0', '0'],
                        ['LLL:EXT:fe_register/Resources/Private/Language/locallang_db.xlf:tx_feregister_domain_model_frontenduser.tx_feregister_gender.I.1', '1'],
                        ['LLL:EXT:fe_register/Resources/Private/Language/locallang_db.xlf:tx_feregister_domain_model_frontenduser.tx_feregister_gender.I.2', '2'],
                        ['LLL:EXT:fe_register/Resources/Private/Language/locallang_db.xlf:tx_feregister_domain_model_frontenduser.tx_feregister_gender.I.99', '99'],
                    ],
                ],
            ],

            'title' => [
                'label'=>'LLL:EXT:fe_register/Resources/Private/Language/locallang_db.xlf:tx_feregister_domain_model_frontenduser.title',
                'exclude' => 1,
                'config' => [
                    'type'=>'input',
                    'size' => 20,
                    'max' => '256',
                    'eval' => 'trim',
                ],
            ],


            'tx_feregister_title' => [
                'label'=>'LLL:EXT:fe_register/Resources/Private/Language/locallang_db.xlf:tx_feregister_domain_model_frontenduser.tx_feregister_title',
                'exclude' => 0,
                'config' => [
                    'type' => 'select',
                    'renderType' => 'selectSingle',
                    'foreign_table' => 'tx_feregister_domain_model_title',
                    'foreign_table_where' => 'AND tx_feregister_domain_model_title.hidden = 0 AND tx_feregister_domain_model_title.deleted = 0 ORDER BY name ASC',
                    'minitems' => 0,
                    'maxitems' => 1,
                    'items' => [
                        ['LLL:EXT:fe_register/Resources/Private/Language/locallang_db.xlf:tx_feregister_domain_model_frontenduser.tx_feregister_title.I.neutral', 0],
                    ],
                    'default' => 0
                ],
            ],

            'tx_feregister_twitter_url' => [
                'label'=>'LLL:EXT:fe_register/Resources/Private/Language/locallang_db.xlf:tx_feregister_domain_model_frontenduser.tx_feregister_twitter_url',
                'exclude' => 0,
                'config'=>[
                    'type'=>'input',
                    'renderType' => 'inputLink',
                    'size' => 30,
                    'max' => '256',
                    'eval' => 'trim',
                    'softref' => 'typolink'
                ],
            ],

            'tx_feregister_facebook_url' => [
                'label'=>'LLL:EXT:fe_register/Resources/Private/Language/locallang_db.xlf:tx_feregister_domain_model_frontenduser.tx_feregister_facebook_url',
                'exclude' => 0,
                'config'=>[
                    'type' => 'input',
                    'renderType' => 'inputLink',
                    'size' => 30,
                    'max' => '256',
                    'eval' => 'trim',
                    'softref' => 'typolink'
                ],
            ],

            'tx_feregister_xing_url' => [
                'label'=>'LLL:EXT:fe_register/Resources/Private/Language/locallang_db.xlf:tx_feregister_domain_model_frontenduser.tx_feregister_xing_url',
                'exclude' => 0,
                'config'=>[
                    'type' => 'input',
                    'renderType' => 'inputLink',
                    'size' => 30,
                    'max' => '256',
                    'eval' => 'trim',
                    'softref' => 'typolink'
                ],
            ],

            'tx_feregister_register_remote_ip' => [
                'label'=>'LLL:EXT:fe_register/Resources/Private/Language/locallang_db.xlf:tx_feregister_domain_model_frontenduser.tx_feregister_register_remote_ip',
                'exclude' => 0,
                'config'=>[
                    'type'=>'input',
                    'readOnly' => 1,
                    'size' => 30,
                    'max' => '256',
                    'eval' => 'trim',
                ],
            ],

            'tx_feregister_login_error_count' => [
                'label'=>'LLL:EXT:fe_register/Resources/Private/Language/locallang_db.xlf:tx_feregister_domain_model_frontenduser.tx_feregister_login_error_count',
                'exclude' => 0,
                'config'=>[
                    'type'=>'input',
                    'size' => 20,
                    'max' => '256',
                    'eval' => 'trim,int'
                ],
            ],

            'tx_feregister_language_key' => [
                'label'=>'LLL:EXT:fe_register/Resources/Private/Language/locallang_db.xlf:tx_feregister_domain_model_frontenduser.tx_feregister_language_key',
                'exclude' => 0,
                'config'=>[
                    'type'=>'input',
                    'size' => 20,
                    'max' => '256',
                    'eval' => 'trim',
                ],
            ],

            'tx_feregister_consent_privacy' => [
                'exclude' => 1,
                'label' => 'LLL:EXT:fe_register/Resources/Private/Language/locallang_db.xlf:tx_feregister_domain_model_frontenduser.consent_privacy',
                'config' => [
                    'type' => 'check',
                    'readOnly' => 1
                ],
            ],

            'tx_feregister_consent_terms' => [
                'exclude' => 1,
                'label' => 'LLL:EXT:fe_register/Resources/Private/Language/locallang_db.xlf:tx_feregister_domain_model_frontenduser.consent_terms',
                'config' => [
                    'type' => 'check',
                ],
            ],

            'tx_feregister_consent_marketing' => [
                'exclude' => 1,
                'label' => 'LLL:EXT:fe_register/Resources/Private/Language/locallang_db.xlf:tx_feregister_domain_model_frontenduser.consent_marketing',
                'config' => [
                    'type' => 'check',
                ],
            ],

            'tx_feregister_data_protection_status' => [
                'config'=>[
                    'type' => 'passthrough',
                ],
            ],
            'tx_feregister_consent' => [
                'exclude' => 1,
                'label' => 'LLL:EXT:fe_register/Resources/Private/Language/locallang_db.xlf:tx_feregister_domain_model_frontenduser.tx_feregister_consent',
                'config' => [
                    'type' => 'inline',
                    'foreign_table' => 'tx_feregister_domain_model_consent',
                    'foreign_field' => 'frontend_user',
                    'foreign_match_fields' => [
                        'parent' => 0
                    ],
                    'maxitems'      => 9999,
                    'appearance' => [
                        'collapseAll' => 1,
                        'levelLinksPosition' => 'top',
                        'showSynchronizationLink' => 1,
                        'showPossibleLocalizationRecords' => 1,
                        'showAllLocalizationLink' => 1,
                        'enabledControls' => [
                            'new' => FALSE,
                        ],
                    ],
                ],
            ],

            'tx_feregister_categories_topics' => [
                'exclude' => 1,
                'label' => 'LLL:EXT:fe_register/Resources/Private/Language/locallang_db.xlf:tx_feregister_domain_model_frontenduser.tx_feregister_categories_topics',
                'config' => [
                    'type' => 'select',
                    'renderType' => 'selectTree',
                    'foreign_table' => 'sys_category',
                    'treeConfig' => [
                        'parentField' => 'parent',
                        'appearance' => [
                            'expandAll' => true,
                            'showHeader' => true,
                            'maxLevels' => 0
                        ],
                    ],
                ],
            ],
        ];

        \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addTCAcolumns('fe_users',$tempCols);
        \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addToAllTCAtypes(
            'fe_users',
            '--div--;LLL:EXT:fe_register/Resources/Private/Language/locallang_db.xlf:tx_feregister_domain_model_frontenduser.socialmedia',
            '0',
            ''
        );
        \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addToAllTCAtypes(
            'fe_users',
            'tx_feregister_twitter_url, tx_feregister_facebook_url, tx_feregister_xing_url',
            '0',
            'after:tx_feregister_twitter_id'
        );
        \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addToAllTCAtypes(
            'fe_users',
            'tx_feregister_title',
            '0',
            'after:title'
        );
        \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addToAllTCAtypes(
            'fe_users',
            'tx_feregister_gender',
            '0',
            'before:name'
        );
        \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addToAllTCAtypes(
            'fe_users',
            'tx_feregister_mobile',
            '0',
            'after:telephone'
        );
        \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addToAllTCAtypes(
            'fe_users',
            'tx_feregister_login_error_count',
            '0',
            'after:disable'
        );
        \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addToAllTCAtypes(
            'fe_users',',
             tx_feregister_register_remote_ip, tx_feregister_language_key',
            '0',
            'after:lockToDomain'
        );
        \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addToAllTCAtypes(
            'fe_users',
            ', tx_feregister_consent_privacy, tx_feregister_consent_terms, tx_feregister_consent_marketing, tx_feregister_consent',
            '0',
            'after:image'
        );
        \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addToAllTCAtypes(
            'fe_users',
            'tx_feregister_categories_topics',
            '0',
            'after:tx_extbase_type'
        );

        /**
         * Register GuestUser as Type and set visible fields accordingly
         * @see typo3/sysext/frontend/Configuration/TCA/fe_users.php
         */
        $GLOBALS['TCA']['fe_users']['columns']['tx_extbase_type']['config']['items'][] = ['\Madj2k\FeRegister\Domain\Model\GuestUser', '\Madj2k\FeRegister\Domain\Model\GuestUser'];
        $GLOBALS['TCA']['fe_users']['types']['\Madj2k\FeRegister\Domain\Model\GuestUser'] = [
            'showitem' => '
                --div--;LLL:EXT:core/Resources/Private/Language/Form/locallang_tabs.xlf:general,
                    lastlogin,
                --div--;LLL:EXT:frontend/Resources/Private/Language/locallang_tca.xlf:fe_users.tabs.personelData,
                    tx_feregister_consent_privacy, tx_feregister_consent_terms, tx_feregister_consent, tx_feregister_consent
                --div--;LLL:EXT:frontend/Resources/Private/Language/locallang_tca.xlf:fe_users.tabs.options,
                    lockToDomain, tx_feregister_register_remote_ip, tx_feregister_language_key, TSconfig,
                --div--;LLL:EXT:core/Resources/Private/Language/Form/locallang_tabs.xlf:access,
                    disable,tx_feregister_login_error_count,--palette--;;timeRestriction,
                --div--;LLL:EXT:core/Resources/Private/Language/Form/locallang_tabs.xlf:notes,
                    description,
                --div--;LLL:EXT:core/Resources/Private/Language/Form/locallang_tabs.xlf:extended,
            '
        ];

        //=================================================================
        // Add Category
        //=================================================================
        // Add an extra categories selection field to the pages table
        /*
        \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::makeCategorizable(
            'feuser_topics',
            'fe_users',
            // Do not use the default field name ("categories") for pages, tt_content, sys_file_metadata, which is already used
            'tx_feregister_categories_topics',
            array(
                // Set a custom label
                'label' => 'LLL:EXT:examples/Resources/Private/Language/locallang.xlf:additional_categories',
                // This field should not be an exclude-field
                'exclude' => FALSE,
                // Override generic configuration, e.g. sort by title rather than by sorting
                'fieldConfiguration' => array(
                    //  'foreign_table_where' => ' AND ((\'###PAGE_TSCONFIG_IDLIST###\' <> \'0\' AND FIND_IN_SET(sys_category.pid,\'###PAGE_TSCONFIG_IDLIST###\')) OR (\'###PAGE_TSCONFIG_IDLIST###\' = \'0\')) AND sys_category.sys_language_uid IN (-1, 0) ORDER BY sys_category.title ASC',
                ),
                // string (keyword), see TCA reference for details
                'l10n_mode' => 'exclude',
                // list of keywords, see TCA reference for details
                'l10n_display' => 'hideDiff',
                'startingPoints' => '144'
            )
        );
        */
    },
    'fe_register'
);
