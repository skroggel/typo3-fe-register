<?php
namespace Madj2k\FeRegister\Service;

/*
 * This file is part of the TYPO3 CMS project.
 *
 * It is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License, either version 2
 * of the License, or any later version.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 *
 * The TYPO3 project - inspiring people to share!
 */

use Madj2k\CoreExtended\Utility\GeneralUtility;
use Madj2k\Postmaster\Mail\MailMassage;
use Madj2k\Postmaster\Mail\MailMessage;
use Madj2k\Postmaster\Utility\FrontendLocalizationUtility;
use Madj2k\FeRegister\Domain\Model\FrontendUser;
use Madj2k\FeRegister\Domain\Model\OptIn;
use \TYPO3\CMS\Extbase\Configuration\ConfigurationManagerInterface;
use TYPO3\CMS\Extbase\Persistence\ObjectStorage;

/**
 * MailService
 *
 * @author Maximilian Fäßler <maximilian@faesslerweb.de>
 * @author Steffen Kroggel <developer@steffenkroggel.de>
 * @copyright Steffen Kroggel
 * @package Madj2k_FeRegister
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License, version 3 or later
 */
class MailService implements \TYPO3\CMS\Core\SingletonInterface
{
    /**
     * Handles optIn-event
     *
     * @param \Madj2k\FeRegister\Domain\Model\FrontendUser $frontendUser
     * @param \Madj2k\FeRegister\Domain\Model\OptIn $optIn
     * @return void
     * @throws \Madj2k\Postmaster\Exception
     * @throws \TYPO3\CMS\Extbase\Persistence\Exception\UnknownObjectException
     * @throws \TYPO3\CMS\Extbase\Persistence\Exception\IllegalObjectTypeException
     * @throws \TYPO3Fluid\Fluid\View\Exception\InvalidTemplateResourceException
     * @throws \TYPO3\CMS\Extbase\Configuration\Exception\InvalidConfigurationTypeException
     */
    public function sendOptInEmail(FrontendUser $frontendUser, OptIn $optIn): void
    {
        // get settings
        $settings = $this->getSettings(ConfigurationManagerInterface::CONFIGURATION_TYPE_FRAMEWORK);
        $settingsDefault = $this->getSettings();

        if ($settings['view']['templateRootPaths']) {

            /** @var \Madj2k\Postmaster\Mail\MailMessage $mailService */
            $mailService = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(MailMessage::class);

            // send new user an email with token
            $mailService->setTo($frontendUser, array(
                'marker' => array(
                    'tokenYes'        => $optIn->getTokenYes(),
                    'tokenNo'         => $optIn->getTokenNo(),
                    'tokenUser'       => $optIn->getTokenUser(),
                    'frontendUser'    => $frontendUser,
                    'settings'        => $settingsDefault,
                    'pageUid'         => intval($GLOBALS['TSFE']->id),
                ),
            ));

            $mailService->getQueueMail()->setSubject(
                \Madj2k\Postmaster\Utility\FrontendLocalizationUtility::translate(
                    'mailService.optIn.subject',
                    'fe_register',
                    null,
                    $frontendUser->getTxFeregisterLanguageKey()
                )
            );

            $mailService->getQueueMail()->addTemplatePaths($settings['view']['templateRootPaths']);
            $mailService->getQueueMail()->setHtmlTemplate('Email/OptIn');

            if ($optIn->getFrontendUserUpdate() && !$optIn->getData()) {
                $mailService->getQueueMail()->setSubject(
                    \Madj2k\Postmaster\Utility\FrontendLocalizationUtility::translate(
                        'mailService.optInUpdate.subject',
                        'fe_register',
                        null,
                        $frontendUser->getTxFeregisterLanguageKey()
                    )
                );

                $mailService->getQueueMail()->setPlaintextTemplate('Email/OptInUpdate');
                $mailService->getQueueMail()->setHtmlTemplate('Email/OptInUpdate');
            }


            $mailService->send();
        }
    }


    /**
     * Handles register user event (after user has done his OptIn)
     *
     * @param \Madj2k\FeRegister\Domain\Model\FrontendUser $frontendUser
     * @param \Madj2k\FeRegister\Domain\Model\OptIn $optIn
     * @return void
     * @throws \Madj2k\Postmaster\Exception
     * @throws \TYPO3\CMS\Extbase\Persistence\Exception\UnknownObjectException
     * @throws \TYPO3\CMS\Extbase\Persistence\Exception\IllegalObjectTypeException
     * @throws \TYPO3Fluid\Fluid\View\Exception\InvalidTemplateResourceException
     * @throws \TYPO3\CMS\Extbase\Configuration\Exception\InvalidConfigurationTypeException
     */
    public function sendConfirmationEmail(FrontendUser $frontendUser, OptIn $optIn): void
    {
        // get settings
        $settings = $this->getSettings(ConfigurationManagerInterface::CONFIGURATION_TYPE_FRAMEWORK);
        $settingsDefault = $this->getSettings();
        if ($settings['view']['templateRootPaths']) {

            /** @var \Madj2k\Postmaster\Mail\MailMessage $mailService */
            $mailService = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(MailMessage::class);

            // send new user an email with token
            $mailService->setTo(
                $frontendUser,
                [
                    'marker' => [
                        'plaintextPasswordForMail'  => $frontendUser->getTempPlaintextPassword(),
                        'frontendUser'              => $frontendUser,
                        'pageUid'                   => intval($GLOBALS['TSFE']->id),
                        'settings'                  => $settingsDefault,
                    ]
                ]
            );

            $mailService->getQueueMail()->setSubject(
                \Madj2k\Postmaster\Utility\FrontendLocalizationUtility::translate(
                    'mailService.confirmation.subject',
                    'fe_register',
                    null,
                    $frontendUser->getTxFeregisterLanguageKey()
                )
            );

            $mailService->getQueueMail()->addTemplatePaths($settings['view']['templateRootPaths']);
            $mailService->getQueueMail()->setPlaintextTemplate('Email/Confirmation');
            $mailService->getQueueMail()->setHtmlTemplate('Email/Confirmation');
            $mailService->send();
        }
    }


    /**
     * Handles optIn-event for groups
     *
     * @param \Madj2k\FeRegister\Domain\Model\FrontendUser $frontendUser
     * @param \Madj2k\FeRegister\Domain\Model\OptIn $optIn
     * @return void
     * @throws \Madj2k\Postmaster\Exception
     * @throws \TYPO3\CMS\Extbase\Configuration\Exception\InvalidConfigurationTypeException
     * @throws \TYPO3\CMS\Extbase\Persistence\Exception\IllegalObjectTypeException
     * @throws \TYPO3\CMS\Extbase\Persistence\Exception\UnknownObjectException
     */
    public function sendGroupOptInEmail(FrontendUser $frontendUser, OptIn $optIn): void
    {
        // get settings
        $settings = $this->getSettings(ConfigurationManagerInterface::CONFIGURATION_TYPE_FRAMEWORK);
        $settingsDefault = $this->getSettings();

        if ($settings['view']['templateRootPaths']) {

            /** @var \Madj2k\Postmaster\Mail\MailMessage $mailService */
            $mailService = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(MailMessage::class);

            // send new user an email with token
            $mailService->setTo($frontendUser, array(
                'marker' => array(
                    'tokenYes'          => $optIn->getTokenYes(),
                    'tokenNo'           => $optIn->getTokenNo(),
                    'tokenUser'         => $optIn->getTokenUser(),
                    'frontendUser'      => $frontendUser,
                    'frontendUserGroup' => $optIn->getData(),
                    'settings'          => $settingsDefault,
                    'pageUid'           => intval($GLOBALS['TSFE']->id),
                ),
            ));

            $mailService->getQueueMail()->setSubject(
                \Madj2k\Postmaster\Utility\FrontendLocalizationUtility::translate(
                    'mailService.group.optIn.subject',
                    'fe_register',
                    null,
                    $frontendUser->getTxFeregisterLanguageKey()
                )
            );

            $mailService->getQueueMail()->addTemplatePaths($settings['view']['templateRootPaths']);
            $mailService->getQueueMail()->setPlaintextTemplate('Email/Group/OptIn');
            $mailService->getQueueMail()->setHtmlTemplate('Email/Group/OptIn');
            $mailService->send();
        }
    }


    /**
     * Handles optIn-event for group-admins
     *
     * @param \Madj2k\FeRegister\Domain\Model\FrontendUser $frontendUser
     * @param \Madj2k\FeRegister\Domain\Model\OptIn $optIn
     * @param \TYPO3\CMS\Extbase\Persistence\ObjectStorage<\Madj2k\FeRegister\Domain\Model\BackendUser> $approvals
     * @return void
     * @throws \Madj2k\Postmaster\Exception
     * @throws \TYPO3\CMS\Extbase\Persistence\Exception\UnknownObjectException
     * @throws \TYPO3\CMS\Extbase\Persistence\Exception\IllegalObjectTypeException
     * @throws \TYPO3Fluid\Fluid\View\Exception\InvalidTemplateResourceException
     * @throws \TYPO3\CMS\Extbase\Configuration\Exception\InvalidConfigurationTypeException
     */
    public function sendGroupOptInEmailAdmin(FrontendUser $frontendUser, OptIn $optIn, ObjectStorage $approvals): void
    {
        // get settings
        $settings = $this->getSettings(ConfigurationManagerInterface::CONFIGURATION_TYPE_FRAMEWORK);
        $settingsDefault = $this->getSettings();

        if (
            ($settings['view']['templateRootPaths'])
            && (count($approvals))
        ){

            /** @var \Madj2k\Postmaster\Mail\MailMessage $mailService */
            $mailService = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(MailMessage::class);

            /** @var \Madj2k\FeRegister\Domain\Model\BackendUser $backendUser */
            foreach ($approvals as $backendUser) {

                // send new user an email with token
                $mailService->setTo($backendUser, array(
                    'marker' => array(
                        'tokenYes' => $optIn->getAdminTokenYes(),
                        'tokenNo' => $optIn->getAdminTokenNo(),
                        'tokenUser' => $optIn->getTokenUser(),
                        'frontendUser' => $frontendUser,
                        'backendUser'  => $backendUser,
                        'frontendUserGroup' => $optIn->getData(),
                        'settings' => $settingsDefault,
                        'pageUid' => intval($GLOBALS['TSFE']->id),
                    ),
                    'subject' => FrontendLocalizationUtility::translate(
                        'mailService.group.optInAdmin.subject',
                        'fe_register',
                        null,
                        $backendUser->getLang()
                    ),
                ));
            }

            $mailService->getQueueMail()->setSubject(
                \Madj2k\Postmaster\Utility\FrontendLocalizationUtility::translate(
                    'mailService.group.optInAdmin.subject',
                    'fe_register',
                    null,
                )
            );

            $mailService->getQueueMail()->addTemplatePaths($settings['view']['templateRootPaths']);
            $mailService->getQueueMail()->setPlaintextTemplate('Email/Group/OptInAdmin');
            $mailService->getQueueMail()->setHtmlTemplate('Email/Group/OptInAdmin');
            $mailService->send();
        }
    }


    /**
     * Handles completion-event for groups
     *
     * @param \Madj2k\FeRegister\Domain\Model\FrontendUser $frontendUser
     * @param \Madj2k\FeRegister\Domain\Model\OptIn $optIn
     * @return void
     * @throws \Madj2k\Postmaster\Exception
     * @throws \TYPO3\CMS\Extbase\Persistence\Exception\UnknownObjectException
     * @throws \TYPO3\CMS\Extbase\Persistence\Exception\IllegalObjectTypeException
     * @throws \TYPO3Fluid\Fluid\View\Exception\InvalidTemplateResourceException
     * @throws \TYPO3\CMS\Extbase\Configuration\Exception\InvalidConfigurationTypeException
     */
    public function sendGroupConfirmationEmail(FrontendUser $frontendUser, OptIn $optIn): void
    {
        // get settings
        $settings = $this->getSettings(ConfigurationManagerInterface::CONFIGURATION_TYPE_FRAMEWORK);
        $settingsDefault = $this->getSettings();

        if ($settings['view']['templateRootPaths']) {

            /** @var \Madj2k\Postmaster\Mail\MailMessage $mailService */
            $mailService = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(MailMessage::class);

            // send new user an email with token
            $mailService->setTo($frontendUser, array(
                'marker' => array(
                    'frontendUser'      => $frontendUser,
                    'frontendUserGroup' => $optIn->getData(),
                    'settings'          => $settingsDefault,
                    'pageUid'           => intval($GLOBALS['TSFE']->id),
                ),
            ));

            $mailService->getQueueMail()->setSubject(
                \Madj2k\Postmaster\Utility\FrontendLocalizationUtility::translate(
                    'mailService.group.confirmation.subject',
                    'fe_register',
                    null,
                    $frontendUser->getTxFeregisterLanguageKey()
                )
            );

            $mailService->getQueueMail()->addTemplatePaths($settings['view']['templateRootPaths']);
            $mailService->getQueueMail()->setPlaintextTemplate('Email/Group/Confirmation');
            $mailService->getQueueMail()->setHtmlTemplate('Email/Group/Confirmation');
            $mailService->send();
        }
    }


    /**
     * Handles optIn-withdraw-event for group-admins
     *
     * @param \Madj2k\FeRegister\Domain\Model\FrontendUser $frontendUser
     * @param \Madj2k\FeRegister\Domain\Model\OptIn $optIn
     * @param \TYPO3\CMS\Extbase\Persistence\ObjectStorage<\Madj2k\FeRegister\Domain\Model\BackendUser> $approvals
     * @return void
     * @throws \Madj2k\Postmaster\Exception
     * @throws \TYPO3\CMS\Extbase\Persistence\Exception\UnknownObjectException
     * @throws \TYPO3\CMS\Extbase\Persistence\Exception\IllegalObjectTypeException
     * @throws \TYPO3Fluid\Fluid\View\Exception\InvalidTemplateResourceException
     * @throws \TYPO3\CMS\Extbase\Configuration\Exception\InvalidConfigurationTypeException
     */
    public function sendGroupOptInWithdrawEmailAdmin (
        FrontendUser $frontendUser,
        OptIn $optIn,
        ObjectStorage $approvals
    ): void {

        // get settings
        $settings = $this->getSettings(ConfigurationManagerInterface::CONFIGURATION_TYPE_FRAMEWORK);
        $settingsDefault = $this->getSettings();

        if (
            ($settings['view']['templateRootPaths'])
            && (count($approvals))
        ){

            /** @var \Madj2k\Postmaster\Mail\MailMessage $mailService */
            $mailService = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(MailMessage::class);

            /** @var \Madj2k\FeRegister\Domain\Model\BackendUser $backendUser */
            foreach ($approvals as $backendUser) {

                // send new user an email with token
                $mailService->setTo($backendUser, array(
                    'marker' => array(
                        'frontendUser' => $frontendUser,
                        'backendUser'  => $backendUser,
                        'frontendUserGroup' => $optIn->getData(),
                        'settings' => $settingsDefault,
                        'pageUid' => intval($GLOBALS['TSFE']->id),
                    ),
                    'subject' => FrontendLocalizationUtility::translate(
                        'mailService.group.optInWithdrawAdmin.subject',
                        'fe_register',
                        null,
                        $backendUser->getLang()
                    ),
                ));
            }

            $mailService->getQueueMail()->setSubject(
                \Madj2k\Postmaster\Utility\FrontendLocalizationUtility::translate(
                    'mailService.group.optInWithdrawAdmin.subject',
                    'fe_register',
                    null,
                )
            );

            $mailService->getQueueMail()->addTemplatePaths($settings['view']['templateRootPaths']);
            $mailService->getQueueMail()->setPlaintextTemplate('Email/Group/OptInWithdrawAdmin');
            $mailService->getQueueMail()->setHtmlTemplate('Email/Group/OptInWithdrawAdmin');
            $mailService->send();
        }
    }


    /**
     * Handles optIn-denial-event for groups
     *
     * @param \Madj2k\FeRegister\Domain\Model\FrontendUser $frontendUser
     * @param \Madj2k\FeRegister\Domain\Model\OptIn $optIn
     * @return void
     * @throws \Madj2k\Postmaster\Exception
     * @throws \TYPO3\CMS\Extbase\Persistence\Exception\UnknownObjectException
     * @throws \TYPO3\CMS\Extbase\Persistence\Exception\IllegalObjectTypeException
     * @throws \TYPO3Fluid\Fluid\View\Exception\InvalidTemplateResourceException
     * @throws \TYPO3\CMS\Extbase\Configuration\Exception\InvalidConfigurationTypeException
     */
    public function sendGroupOptInDenialEmail (FrontendUser $frontendUser, OptIn $optIn): void
    {
        // get settings
        $settings = $this->getSettings(ConfigurationManagerInterface::CONFIGURATION_TYPE_FRAMEWORK);
        $settingsDefault = $this->getSettings();

        if ($settings['view']['templateRootPaths']) {

            /** @var \Madj2k\Postmaster\Mail\MailMessage $mailService */
            $mailService = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(MailMessage::class);

            // send new user an email with token
            $mailService->setTo($frontendUser, array(
                'marker' => array(
                    'frontendUser'      => $frontendUser,
                    'frontendUserGroup' => $optIn->getData(),
                    'settings'          => $settingsDefault,
                    'pageUid'           => intval($GLOBALS['TSFE']->id),
                ),
            ));

            $mailService->getQueueMail()->setSubject(
                \Madj2k\Postmaster\Utility\FrontendLocalizationUtility::translate(
                    'mailService.group.optInDenial.subject',
                    'fe_register',
                    null,
                    $frontendUser->getTxFeregisterLanguageKey()
                )
            );

            $mailService->getQueueMail()->addTemplatePaths($settings['view']['templateRootPaths']);
            $mailService->getQueueMail()->setPlaintextTemplate('Email/Group/OptInDenial');
            $mailService->getQueueMail()->setHtmlTemplate('Email/Group/OptInDenial');
            $mailService->send();
        }
    }


    /**
     * Handles password reset event
     *
     * @param \Madj2k\FeRegister\Domain\Model\FrontendUser $frontendUser
     * @param string $plaintextPassword
     * @param string $referrer
     * @return void
     * @throws \Madj2k\Postmaster\Exception
     * @throws \TYPO3\CMS\Extbase\Persistence\Exception\UnknownObjectException
     * @throws \TYPO3\CMS\Extbase\Persistence\Exception\IllegalObjectTypeException
     * @throws \TYPO3Fluid\Fluid\View\Exception\InvalidTemplateResourceException
     * @throws \TYPO3\CMS\Extbase\Configuration\Exception\InvalidConfigurationTypeException
     */
    public function sendResetPasswordEmail(
        FrontendUser $frontendUser,
        string $plaintextPassword,
        string $referrer = ''
    ): void {

        // get settings
        $settings = $this->getSettings(ConfigurationManagerInterface::CONFIGURATION_TYPE_FRAMEWORK);
        $settingsDefault = $this->getSettings();

        if ($settings['view']['templateRootPaths']) {

            /** @var \Madj2k\Postmaster\Mail\MailMessage $mailService */
            $mailService = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(MailMessage::class);

            // send new user an email with token
            $mailService->setTo(
                $frontendUser,
                [
                    'marker' => [
                        'plaintextPasswordForMail' => $plaintextPassword,
                        'frontendUser'             => $frontendUser,
                        'settings'                 => $settingsDefault,
                        'pageUid'                  => intval($GLOBALS['TSFE']->id),
                        'referrer'                 => $referrer
                    ],
                ]
            );

            $mailService->getQueueMail()->setSubject(
                \Madj2k\Postmaster\Utility\FrontendLocalizationUtility::translate(
                    'mailService.resetPassword.subject',
                    'fe_register',
                    null,
                    $frontendUser->getTxFeregisterLanguageKey()
                )
            );

            $mailService->getQueueMail()->addTemplatePaths($settings['view']['templateRootPaths']);
            $mailService->getQueueMail()->setPlaintextTemplate('Email/PasswordReset');
            $mailService->getQueueMail()->setHtmlTemplate('Email/PasswordReset');
            $mailService->send();
        }
    }


    /**
     * Returns TYPO3 settings
     *
     * @param string $which Which type of settings will be loaded
     * @return array
     * @throws \TYPO3\CMS\Extbase\Configuration\Exception\InvalidConfigurationTypeException
     */
    protected function getSettings(string $which = ConfigurationManagerInterface::CONFIGURATION_TYPE_SETTINGS): array
    {
        return GeneralUtility::getTypoScriptConfiguration('Feregister', $which);
    }
}
