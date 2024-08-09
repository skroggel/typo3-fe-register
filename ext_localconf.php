<?php
defined('TYPO3_MODE') || die('Access denied.');

call_user_func(
    function($extKey)
    {

        \TYPO3\CMS\Extbase\Utility\ExtensionUtility::configurePlugin(
            'Madj2k.' . $extKey,
            'Auth',
            [
                'Auth' => 'index, login, logout, logoutRedirect',
                'AuthGuest' => 'login, create',
                'FrontendUser' => 'new, create, optIn, index',
                'FrontendUserGroup' => 'optIn',
                'Password'=> 'new, create',
            ],
            // non-cacheable actions
            [
                'Auth' => 'index, login, loginRedirect, logout, logoutRedirect',
                'AuthGuest' => 'login, create, loginRedirect',
                'FrontendUser' => 'new, create, optIn, index',
                'FrontendUserGroup' => 'optIn',
                'Password'=> 'new, create',
            ]
        );

        \TYPO3\CMS\Extbase\Utility\ExtensionUtility::configurePlugin(
            'Madj2k.' . $extKey,
            'LoginButton',
            [
                'Auth' => 'loginButton, index'
            ],
            // non-cacheable actions
            [
                'Auth' => 'loginButton, index'
            ]
        );

        \TYPO3\CMS\Extbase\Utility\ExtensionUtility::configurePlugin(
            'Madj2k.' . $extKey,
            'Welcome',
            [
                'FrontendUser' => 'welcome, index',
                'Auth'=> 'index',
            ],
            // non-cacheable actions
            [
                'FrontendUser' => 'welcome, index',
                'Auth'=> 'index',
            ]
        );

        \TYPO3\CMS\Extbase\Utility\ExtensionUtility::configurePlugin(
            'Madj2k.' . $extKey,
            'Logout',
            [
                'Auth'=> 'logout, logoutRedirect, index',
            ],
            // non-cacheable actions
            [
                'Auth'=> 'logout, logoutRedirect, index',
            ]
        );


        \TYPO3\CMS\Extbase\Utility\ExtensionUtility::configurePlugin(
            'Madj2k.' . $extKey,
            'Password',
            [
                'Password' => 'edit, update, redirectDisabledUser',
                'Auth'=> 'index',
                'FrontendUser' => 'index'
            ],
            // non-cacheable actions
            [
                'Password' => 'edit, update, redirectDisabledUser',
                'Auth'=> 'index',
                'FrontendUser' => 'index'
            ]
        );

        \TYPO3\CMS\Extbase\Utility\ExtensionUtility::configurePlugin(
            'Madj2k.' . $extKey,
            'UserEdit',
            [
                'FrontendUser' => 'edit, update, index',
                'Auth'=> 'index'
            ],
            // non-cacheable actions
            [
                'FrontendUser' => 'edit, update, index',
                'Auth'=> 'index'
            ]
        );

        \TYPO3\CMS\Extbase\Utility\ExtensionUtility::configurePlugin(
            'Madj2k.' . $extKey,
            'UserDelete',
            [
                'FrontendUser' => 'show, delete, index',
                'Auth'=> 'index'
            ],
            // non-cacheable actions
            [
                'FrontendUser' => 'show, delete, index',
                'Auth'=> 'index'
            ]
        );

        \TYPO3\CMS\Extbase\Utility\ExtensionUtility::configurePlugin(
            'Madj2k.' . $extKey,
            'UserTopics',
            [
                'FrontendUser' => 'topic, topicUpdate',
            ],
            // non-cacheable actions
            [
                'FrontendUser' => 'topic, topicUpdate',
            ]
        );

        \TYPO3\CMS\Extbase\Utility\ExtensionUtility::configurePlugin(
            'Madj2k.' . $extKey,
            'Group',
            [
                'FrontendUserGroup' => 'list, show, create, delete',
                'Auth'=> 'index',
                'FrontendUser' => 'index'
            ],
            // non-cacheable actions
            [
                'FrontendUserGroup' => 'list, show, create, delete',
                'Auth'=> 'index',
                'FrontendUser' => 'index'            ]
        );

        \TYPO3\CMS\Extbase\Utility\ExtensionUtility::configurePlugin(
            'Madj2k.' . $extKey,
            'GroupOptIn',
            [
                'FrontendUserGroup' => 'optIn',
                'Auth'=> 'index',
            ],
            // non-cacheable actions
            [
                'FrontendUserGroup' => 'optIn',
                'Auth'=> 'index',
            ]
        );

        \TYPO3\CMS\Extbase\Utility\ExtensionUtility::configurePlugin(
            'Madj2k.' . $extKey,
            'Info',
            [
                'Info' => 'loginInfo'
            ],
            // non-cacheable actions
            [
                'Info' => 'loginInfo'
            ]
        );

        //=================================================================
        // Register Hook for Backend-Delete
        //=================================================================
        $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_tcemain.php']['processCmdmapClass'][$extKey] = 'Madj2k\\FeRegister\\Hooks\\DatahandlerHook';


        //=================================================================
        // Register Signal-Slots
        //=================================================================
        /**
         * @var \TYPO3\CMS\Extbase\SignalSlot\Dispatcher $signalSlotDispatcher
         */
        $signalSlotDispatcher = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(TYPO3\CMS\Extbase\SignalSlot\Dispatcher::class);
        $signalSlotDispatcher->connect(
            Madj2k\FeRegister\Registration\AbstractRegistration::class,
            \Madj2k\FeRegister\Registration\AbstractRegistration::SIGNAL_AFTER_CREATING_OPTIN,
            \Madj2k\FeRegister\Service\MailService::class,
            'sendOptInEmail'
        );

        $signalSlotDispatcher->connect(
            Madj2k\FeRegister\Registration\AbstractRegistration::class,
            \Madj2k\FeRegister\Registration\AbstractRegistration::SIGNAL_AFTER_REGISTRATION_COMPLETED,
            \Madj2k\FeRegister\Service\MailService::class,
            'sendConfirmationEmail'
        );

        $signalSlotDispatcher->connect(
            \Madj2k\FeRegister\Controller\PasswordController::class,
            \Madj2k\FeRegister\Controller\PasswordController::SIGNAL_AFTER_USER_PASSWORD_RESET,
            \Madj2k\FeRegister\Service\MailService::class,
            'sendResetPasswordEmail'
        );

        $signalSlotDispatcher->connect(
            Madj2k\FeRegister\Registration\AbstractRegistration::class,
            \Madj2k\FeRegister\Registration\AbstractRegistration::SIGNAL_AFTER_CREATING_OPTIN . 'FeRegisterGroups',
            \Madj2k\FeRegister\Service\MailService::class,
            'sendGroupOptInEmail'
        );

        $signalSlotDispatcher->connect(
            Madj2k\FeRegister\Registration\AbstractRegistration::class,
            \Madj2k\FeRegister\Registration\AbstractRegistration::SIGNAL_AFTER_CREATING_OPTIN_ADMIN . 'FeRegisterGroups',
            \Madj2k\FeRegister\Service\MailService::class,
            'sendGroupOptInEmailAdmin'
        );

        $signalSlotDispatcher->connect(
            Madj2k\FeRegister\Registration\AbstractRegistration::class,
            \Madj2k\FeRegister\Registration\AbstractRegistration::SIGNAL_AFTER_REGISTRATION_COMPLETED . 'FeRegisterGroups',
            \Madj2k\FeRegister\Service\MailService::class,
            'sendGroupConfirmationEmail'
        );

        $signalSlotDispatcher->connect(
            Madj2k\FeRegister\Registration\AbstractRegistration::class,
            \Madj2k\FeRegister\Registration\AbstractRegistration::SIGNAL_AFTER_DENIAL_OPTIN . 'FeRegisterGroups',
            \Madj2k\FeRegister\Service\MailService::class,
            'sendGroupOptInWithdrawEmailAdmin'
        );

        $signalSlotDispatcher->connect(
            Madj2k\FeRegister\Registration\AbstractRegistration::class,
            \Madj2k\FeRegister\Registration\AbstractRegistration::SIGNAL_AFTER_DENIAL_OPTIN_ADMIN . 'FeRegisterGroups',
            \Madj2k\FeRegister\Service\MailService::class,
            'sendGroupOptInDenialEmail'
        );

        //=================================================================
        // AuthService
        //=================================================================
        \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addService(
            $extKey,
            'auth',
            \Madj2k\FeRegister\Service\FrontendUserAuthenticationService::class,
            [
                'title' => 'Authentication Service for fe_users as normal users',
                'description' => 'Authentication Service for fe_users as normal users',
                'subtype' => 'getUserFE, authUserFE, getGroupsFE, processLoginDataFE',
                'available' => true,
                'priority' => 90,
                'quality' => 50,
                'os' => '',
                'exec' => '',
                'className' => \Madj2k\FeRegister\Service\FrontendUserAuthenticationService::class
            ]
        );

        \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addService(
            $extKey,
            'auth',
            \Madj2k\FeRegister\Service\GuestUserAuthenticationService::class,
            [
                'title' => 'Authentication Service for fe_users as guests',
                'description' => 'Authentication Service for fe_users as guests',
                'subtype' => 'getUserFE, authUserFE, getGroupsFE, processLoginDataFE',
                'available' => true,
                'priority' => 80,
                'quality' => 50,
                'os' => '',
                'exec' => '',
                'className' => \Madj2k\FeRegister\Service\GuestUserAuthenticationService::class
            ]
        );

        //=================================================================
        // ATTENTION: deactivated due to faulty mapping in TYPO3 9.5
        // Add XClasses for extending existing classes
        //=================================================================
//        // for TYPO3 12+
//        $GLOBALS['TYPO3_CONF_VARS']['SYS']['Objects'][\Madj2k\CoreExtended\Domain\Model\BackendUser::class] = [
//            'className' => \Madj2k\FeRegister\Domain\Model\BackendUser::class
//        ];
//
//        // for TYPO3 9.5 - 11.5 only, not required for TYPO3 12
//        \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(\TYPO3\CMS\Extbase\Object\Container\Container::class)
//            ->registerImplementation(
//                \Madj2k\CoreExtended\Domain\Model\BackendUser::class,
//                \Madj2k\FeRegister\Domain\Model\BackendUser::class
//            );
//
//        // for TYPO3 12+
//        $GLOBALS['TYPO3_CONF_VARS']['SYS']['Objects'][\Madj2k\CoreExtended\Domain\Model\FrontendUser::class] = [
//            'className' => \Madj2k\FeRegister\Domain\Model\FrontendUser::class
//        ];
//
//        // for TYPO3 9.5 - 11.5 only, not required for TYPO3 12
//        \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(\TYPO3\CMS\Extbase\Object\Container\Container::class)
//            ->registerImplementation(
//                \Madj2k\CoreExtended\Domain\Model\FrontendUser::class,
//                \Madj2k\FeRegister\Domain\Model\FrontendUser::class
//            );
//
//        // for TYPO3 12+
//        $GLOBALS['TYPO3_CONF_VARS']['SYS']['Objects'][\Madj2k\CoreExtended\Domain\Model\FrontendUserGroup::class] = [
//            'className' => \Madj2k\FeRegister\Domain\Model\FrontendUserGroup::class
//        ];
//
//        // for TYPO3 9.5 - 11.5 only, not required for TYPO3 12
//        \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(\TYPO3\CMS\Extbase\Object\Container\Container::class)
//            ->registerImplementation(
//                \Madj2k\CoreExtended\Domain\Model\FrontendUserGroup::class,
//                \Madj2k\FeRegister\Domain\Model\FrontendUserGroup::class
//            );

        //=================================================================
        // Register Logger
        //=================================================================
        $GLOBALS['TYPO3_CONF_VARS']['LOG']['Madj2k']['FeRegister']['writerConfiguration'] = [

            // configuration for WARNING severity, including all
            // levels with higher severity (ERROR, CRITICAL, EMERGENCY)
            \TYPO3\CMS\Core\Log\LogLevel::INFO => [
                // add a FileWriter
                'TYPO3\\CMS\\Core\\Log\\Writer\\FileWriter' => [
                    // configuration for the writer
                    'logFile' => \TYPO3\CMS\Core\Core\Environment::getVarPath()  . '/log/tx_feregister.log'
                ]
            ],
        ];

        $GLOBALS['TYPO3_CONF_VARS']['LOG']['Madj2k']['FeRegister']['processorConfiguration'] = [
            // Configuration for ERROR level log entries
            \TYPO3\CMS\Core\Log\LogLevel::ERROR => [
                // Add a NotifyProcessor
                \Madj2k\FeRegister\Log\Processor\NotifyProcessor::class => [
                    'sendMails' => true,
                    'emailAddress' => "xyz@abc.com",
                ],
            ],
        ];


    },
    'fe_register'
);
