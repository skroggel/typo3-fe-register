<?php

namespace Madj2k\FeRegister\Controller;

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

use Madj2k\FeRegister\Registration\GuestUserRegistration;
use Madj2k\FeRegister\Utility\FrontendUserUtility;
use Madj2k\Postmaster\UriBuilder\EmailUriBuilder;
use Madj2k\FeRegister\Domain\Model\FrontendUser;
use Madj2k\FeRegister\Domain\Model\FrontendUserGroup;
use Madj2k\FeRegister\Domain\Model\GuestUser;
use Madj2k\FeRegister\Domain\Repository\TitleRepository;
use Madj2k\FeRegister\Registration\FrontendUserRegistration;
use Madj2k\FeRegister\Utility\TitleUtility;
use TYPO3\CMS\Core\Messaging\AbstractMessage;
use TYPO3\CMS\Extbase\Utility\LocalizationUtility;

/**
 * Class FrontendUserController
 *
 * @author Steffen Kroggel <developer@steffenkroggel.de>
 * @author Maximilian Fäßler <maximilian@faesslerweb.de>
 * @copyright Steffen Kroggel
 * @package Madj2k_FeRegister
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License, version 3 or later
 */
class FrontendUserController extends AbstractController
{

    /**
     * @var \Madj2k\FeRegister\Registration\FrontendUserRegistration
     * @TYPO3\CMS\Extbase\Annotation\Inject
     */
    protected ?FrontendUserRegistration $frontendUserRegistration = null;


    /**
     * @var \Madj2k\FeRegister\Registration\GuestUserRegistration
     * @TYPO3\CMS\Extbase\Annotation\Inject
     */
    protected ?GuestUserRegistration $guestUserRegistration = null;


    /**
     * @var \Madj2k\FeRegister\Domain\Repository\TitleRepository
     * @TYPO3\CMS\Extbase\Annotation\Inject
     */
    protected ?TitleRepository $titleRepository = null;


    /**
     * @var \Madj2k\FeRegister\Registration\FrontendUserRegistration
     */
    public function injectFrontendUserRegistration(FrontendUserRegistration $frontendUserRegistration)
    {
        $this->frontendUserRegistration = $frontendUserRegistration;
    }


    /**
     * @var \Madj2k\FeRegister\Registration\GuestUserRegistration
     */
    public function injectGuestUserRegistration(GuestUserRegistration $guestUserRegistration)
    {
        $this->guestUserRegistration = $guestUserRegistration;
    }


    /**
     * @var \Madj2k\FeRegister\Domain\Repository\TitleRepository
     */
    public function injectTitleRepository(TitleRepository $titleRepository)
    {
        $this->titleRepository = $titleRepository;
    }


    /**
     * action register
     *
     * @param \Madj2k\FeRegister\Domain\Model\FrontendUser|null $frontendUser
     * @return void
     * @throws \TYPO3\CMS\Extbase\Mvc\Exception\StopActionException
     * @throws \TYPO3\CMS\Extbase\Mvc\Exception\UnsupportedRequestTypeException
     * @throws \TYPO3\CMS\Core\Context\Exception\AspectNotFoundException
     * @throws \TYPO3\CMS\Extbase\Configuration\Exception\InvalidConfigurationTypeException
     * @TYPO3\CMS\Extbase\Annotation\IgnoreValidation("frontendUser")
     */
    public function newAction(FrontendUser $frontendUser = null): void
    {
        // not for already logged-in users!
        $this->redirectIfUserLoggedIn();

        if (
            (! $this->getFlashMessageCount())
            && (! $_POST)
        ) {
            $this->addFlashMessage(
                LocalizationUtility::translate(
                    'frontendUserController.notice.newIntroduction',
                    'fe_register'
                ),
                '',
                AbstractMessage::NOTICE
            );
        }

        $titles = $this->titleRepository->findAllOfType(true, false, false);
        $this->view->assignMultiple(
            [
                'frontendUser'   => $frontendUser,
                'titles'         => $titles
            ]
        );
    }


    /**
     * action create
     *
     * @param \Madj2k\FeRegister\Domain\Model\FrontendUser $frontendUser
     * @return void
     * @throws \Madj2k\FeRegister\Exception
     * @throws \TYPO3\CMS\Core\Context\Exception\AspectNotFoundException
     * @throws \TYPO3\CMS\Core\Crypto\PasswordHashing\InvalidPasswordHashException
     * @throws \TYPO3\CMS\Extbase\Configuration\Exception\InvalidConfigurationTypeException
     * @throws \TYPO3\CMS\Extbase\Mvc\Exception\StopActionException
     * @throws \TYPO3\CMS\Extbase\Mvc\Exception\UnsupportedRequestTypeException
     * @throws \TYPO3\CMS\Extbase\Persistence\Exception\IllegalObjectTypeException
     * @throws \TYPO3\CMS\Extbase\Persistence\Exception\InvalidQueryException
     * @throws \TYPO3\CMS\Extbase\Persistence\Generic\Exception\NotImplementedException
     * @TYPO3\CMS\Extbase\Annotation\Validate("Madj2k\FeRegister\Validation\FrontendUserValidator", param="frontendUser")
     * @TYPO3\CMS\Extbase\Annotation\Validate("Madj2k\FeRegister\Validation\Consent\TermsValidator", param="frontendUser")
     * @TYPO3\CMS\Extbase\Annotation\Validate("Madj2k\FeRegister\Validation\Consent\PrivacyValidator", param="frontendUser")
     * @TYPO3\CMS\Extbase\Annotation\Validate("Madj2k\FeRegister\Validation\Consent\MarketingValidator", param="frontendUser")
     * @TYPO3\CMS\Extbase\Annotation\Validate("Madj2k\CoreExtended\Validation\CaptchaValidator", param="frontendUser")
     */
    public function createAction(FrontendUser $frontendUser): void
    {
        // not for already logged-in users!
        $this->redirectIfUserLoggedIn();

        /** @var \Madj2k\FeRegister\Registration\FrontendUserRegistration */
        $this->frontendUserRegistration->setFrontendUser($frontendUser)
            ->setRequest($this->request)
            ->startRegistration();

        $this->addFlashMessage(
            LocalizationUtility::translate(
                'frontendUserController.message.registrationWatchForEmail',
                'fe_register',
                [
                    $this->settings['companyEmail']
                ]
            )
        );

        if ($this->settings['loginPid']) {
            $this->redirect(
                'index',
                'Auth',
                null,
                [],
                $this->settings['loginPid']
            );
        }

        $this->redirect('index');
    }


    /**
     * Takes optIn parameters and checks them
     *
     * @return void
     * @throws \Madj2k\FeRegister\Exception
     * @throws \TYPO3\CMS\Extbase\Mvc\Exception\NoSuchArgumentException
     * @throws \TYPO3\CMS\Extbase\Mvc\Exception\StopActionException
     * @throws \TYPO3\CMS\Extbase\Mvc\Exception\UnsupportedRequestTypeException
     * @throws \TYPO3\CMS\Extbase\Persistence\Exception\IllegalObjectTypeException
     * @throws \TYPO3\CMS\Extbase\Persistence\Exception\UnknownObjectException
     * @throws \TYPO3\CMS\Extbase\Persistence\Generic\Exception
     * @throws \TYPO3\CMS\Extbase\Persistence\Generic\Exception\NotImplementedException
     * @throws \TYPO3\CMS\Extbase\SignalSlot\Exception\InvalidSlotException
     * @throws \TYPO3\CMS\Extbase\SignalSlot\Exception\InvalidSlotReturnException
     * @throws \TYPO3\CMS\Extbase\Configuration\Exception\InvalidConfigurationTypeException
     * @throws \TYPO3\CMS\Core\Context\Exception\AspectNotFoundException
     * @throws \TYPO3\CMS\Extbase\Persistence\Exception\InvalidQueryException
     * @throws \TYPO3\CMS\Core\Crypto\PasswordHashing\InvalidPasswordHashException
     */
    public function optInAction(): void
    {
        // not for already logged-in users!
        $this->redirectIfUserLoggedIn();

        $token = preg_replace('/[^a-zA-Z0-9]/', '', $this->request->getArgument('token'));
        $tokenUser = preg_replace('/[^a-zA-Z0-9]/', '', $this->request->getArgument('user'));

        $check =  $this->frontendUserRegistration->setFrontendUserToken($tokenUser)
            ->setRequest($this->getRequest())
            ->validateOptIn($token);

        if ($check < 300) {

            $this->addFlashMessage(
                LocalizationUtility::translate(
                    'frontendUserController.message.registrationSuccessful',
                    'fe_register',
                    [
                        $this->settings['companyEmail']
                    ]
                )
            );

        } elseif ($check < 400) {

            $this->addFlashMessage(
                LocalizationUtility::translate(
                    'frontendUserController.message.registrationCanceled',
                    'fe_register'
                )
            );

        } else {

            $this->addFlashMessage(
                LocalizationUtility::translate(
                    'frontendUserController.error.registrationError',
                    'fe_register'
                ),
                '',
                AbstractMessage::ERROR
            );
        }

        $this->redirect(
            'index',
            'Auth',
            null,
            [],
            $this->settings['loginPid']
        );
    }


    /**
     * action welcome
     *
     * @param bool $redirectToReferrer
     * @return void
     * @throws \TYPO3\CMS\Core\Context\Exception\AspectNotFoundException
     * @throws \TYPO3\CMS\Extbase\Configuration\Exception\InvalidConfigurationTypeException
     * @throws \TYPO3\CMS\Extbase\Mvc\Exception\StopActionException
     * @throws \TYPO3\CMS\Extbase\Mvc\Exception\UnsupportedRequestTypeException
     * @throws \TYPO3\CMS\Extbase\Persistence\Exception\IllegalObjectTypeException
     * @throws \TYPO3\CMS\Extbase\Persistence\Exception\UnknownObjectException
     */
    public function welcomeAction(bool $redirectToReferrer = false): void
    {

        // only for logged in users!
        $this->redirectIfUserNotLoggedIn();

        // try to redirect to referer
        if ($redirectToReferrer) {
            $this->redirectToReferer();
        }

        // add corresponding flash message
        if (FrontendUserUtility::isGuestUser($this->getFrontendUser())) {

            // generate link for copy&paste
            /** @var \Madj2k\Postmaster\UriBuilder\EmailUriBuilder $uriBuilder */
            $uriBuilder = $this->objectManager->get(EmailUriBuilder::class);
            $url = $uriBuilder->reset()
                ->setArguments(
                    ['tx_feregister_auth' =>
                        [
                            'controller' => 'AuthGuest',
                            'action'     => 'login',
                            'token'      => $this->getFrontendUser()->getUsername(),
                        ],
                    ]
                )
                ->setTargetPageUid($this->settings['loginPid'])
                ->setCreateAbsoluteUri(true)
                ->build();

            // show link with token to anonymous user
            $translationKey = (intval($GLOBALS['TSFE']->id) == intval($this->settings['welcomeGuestPid']))
                ? 'guestLink'
                : 'guestLink2';

            $this->addFlashMessage(
                LocalizationUtility::translate(
                    'frontendUserController.message.' . $translationKey,
                    'fe_register',
                    [
                        intval(intval($this->settings['users']['guest']['lifetime']) / 60 / 60 / 24),
                        $url,
                    ]
                )
            );

        // user is logged in as normal user
        } else if ($this->getFrontendUser() instanceof FrontendUser) {

            $this->addFlashMessage(
                LocalizationUtility::translate(
                    'frontendUserController.message.loggedIn',
                    'fe_register',
                    [$this->getfrontendUser()->getUsername()]
                )
            );

            $this->redirectIfUserHasMissingData();
        }

        $currentPageUid = intval($GLOBALS["TSFE"]->id);
        $this->view->assignMultiple(
            [
                'frontendUser'    => $this->getFrontendUser(),
                'showContinue'    => ($this->referrerPid || ($currentPageUid !== intval($this->settings['welcomePid'])))
            ]
        );
    }


    /**
     * action editUser
     *
     * @param \Madj2k\FeRegister\Domain\Model\FrontendUser|null $frontendUser
     * @param \Madj2k\FeRegister\Domain\Model\FrontendUserGroup|null $frontendUserGroup
     * @return void
     * @throws \TYPO3\CMS\Core\Context\Exception\AspectNotFoundException
     * @throws \TYPO3\CMS\Extbase\Mvc\Exception\StopActionException
     * @throws \TYPO3\CMS\Extbase\Mvc\Exception\UnsupportedRequestTypeException
     * @throws \TYPO3\CMS\Extbase\Configuration\Exception\InvalidConfigurationTypeException
     */
    public function editAction(FrontendUser $frontendUser = null, FrontendUserGroup $frontendUserGroup = null): void
    {

        // for logged-in users only!
        $this->redirectIfUserNotLoggedInOrGuest();

        // set temporary usergroup for validation
        $frontendUser = $frontendUser ?: $this->getFrontendUser();
        if ($frontendUserGroup) {
            $frontendUser->setTempFrontendUserGroup($frontendUserGroup);
        }

        if (
            (! $this->getFlashMessageCount())
            && (! $_POST)
            && (! $frontendUserGroup)
        ) {
            $this->addFlashMessage(
                LocalizationUtility::translate(
                    'frontendUserController.notice.editIntroduction',
                    'fe_register'
                ),
                '',
                AbstractMessage::NOTICE
            );
        }

        $titles = $this->titleRepository->findAllOfType(true, false, false);
        $this->view->assignMultiple(
            [
                'frontendUser'  => $frontendUser,
                'titles'        => $titles
            ]
        );
    }


    /**
     * action update
     * @param \Madj2k\FeRegister\Domain\Model\FrontendUser $frontendUser
     * @return void
     * @throws \TYPO3\CMS\Extbase\Mvc\Exception\StopActionException
     * @throws \TYPO3\CMS\Extbase\Mvc\Exception\UnsupportedRequestTypeException
     * @throws \TYPO3\CMS\Extbase\Persistence\Exception\IllegalObjectTypeException
     * @throws \TYPO3\CMS\Extbase\Persistence\Exception\UnknownObjectException
     * @throws \TYPO3\CMS\Core\Context\Exception\AspectNotFoundException
     * @throws \TYPO3\CMS\Extbase\Configuration\Exception\InvalidConfigurationTypeException
     * @TYPO3\CMS\Extbase\Annotation\Validate("Madj2k\FeRegister\Validation\FrontendUserValidator", param="frontendUser")
     */
    public function updateAction(FrontendUser $frontendUser): void
    {

        // for logged-in users only!
        $this->redirectIfUserNotLoggedInOrGuest();

        // migrate title-handling
        if ($frontendUser->getTxFeregisterTitle()) {
            $frontendUser->setTxFeregisterTitle(
                TitleUtility::extractTxRegistrationTitle(
                    $frontendUser->getTxFeregisterTitle()->getName()
                )
            );
        }
        $this->frontendUserRepository->update($frontendUser);
        $this->persistenceManager->persistAll();

        $this->addFlashMessage(
            LocalizationUtility::translate(
                'frontendUserController.message.updateSuccessful',
                'fe_register'
            )
        );

        // redirect back to groups when we were originally redirected from there
        if (
            ($this->settings['groupListPid'])
            && ($frontendUser->getTempFrontendUserGroup())
        ){
            $this->redirect(
                'create',
                'FrontendUserGroup',
                null,
                [
                    'frontendUserGroup' => $frontendUser->getTempFrontendUserGroup()
                ],
                $this->settings['groupListPid']
            );
        }

        $this->redirect('edit');
    }


    /**
     * action show
     *
     * @return void
     * @throws \TYPO3\CMS\Extbase\Mvc\Exception\StopActionException
     * @throws \TYPO3\CMS\Extbase\Mvc\Exception\UnsupportedRequestTypeException
     * @throws \TYPO3\CMS\Core\Context\Exception\AspectNotFoundException
     * @throws \TYPO3\CMS\Extbase\Configuration\Exception\InvalidConfigurationTypeException
     */
    public function showAction(): void
    {
        // for logged-in users only!
        $this->redirectIfUserNotLoggedIn();

        $this->addFlashMessage(
            LocalizationUtility::translate(
                'frontendUserController.warning.showIntroduction',
                'fe_register'
            ),
            '',
            AbstractMessage::WARNING
        );

        $this->view->assignMultiple(
            [
                'frontendUser'  => $this->getFrontendUser(),
            ]
        );
    }


    /**
     * action delete
     *
     * @return void
     * @throws \Madj2k\FeRegister\Exception
     * @throws \TYPO3\CMS\Core\Context\Exception\AspectNotFoundException
     * @throws \TYPO3\CMS\Extbase\Configuration\Exception\InvalidConfigurationTypeException
     * @throws \TYPO3\CMS\Extbase\Mvc\Exception\StopActionException
     * @throws \TYPO3\CMS\Extbase\Mvc\Exception\UnsupportedRequestTypeException
     * @throws \TYPO3\CMS\Extbase\Persistence\Exception\IllegalObjectTypeException
     * @throws \TYPO3\CMS\Extbase\Persistence\Generic\Exception\NotImplementedException
     * @throws \TYPO3\CMS\Extbase\SignalSlot\Exception\InvalidSlotException
     * @throws \TYPO3\CMS\Extbase\SignalSlot\Exception\InvalidSlotReturnException
     * @throws \TYPO3\CMS\Extbase\Persistence\Exception\InvalidQueryException
     */
    public function deleteAction(): void
    {
        // for logged-in users only!
        $this->redirectIfUserNotLoggedIn();

        if (FrontendUserUtility::isGuestUser($this->getFrontendUser())) {
            $this->guestUserRegistration->setFrontendUser($this->getFrontendUser())
                ->setRequest($this->request)
                ->endRegistration();

        } else {
            $this->frontendUserRegistration->setFrontendUser($this->getFrontendUser())
                ->setRequest($this->request)
                ->endRegistration();
        }


        $this->addFlashMessage(
            LocalizationUtility::translate(
                'frontendUserController.message.deletedSuccessful',
                'fe_register'
            )
        );

        $this->redirectToLogin();

    }

}


