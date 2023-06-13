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

use Madj2k\FeRegister\Domain\Model\FrontendUser;
use Madj2k\FeRegister\Domain\Model\FrontendUserGroup;
use Madj2k\FeRegister\Domain\Model\GuestUser;
use Madj2k\FeRegister\Domain\Repository\FrontendUserRepository;
use Madj2k\FeRegister\Utility\ClientUtility;
use Madj2k\FeRegister\Utility\FrontendUserSessionUtility;
use Madj2k\FeRegister\Utility\FrontendUserUtility;
use Madj2k\FeRegister\Validation\FrontendUserValidator;
use TYPO3\CMS\Core\Log\Logger;
use TYPO3\CMS\Core\Log\LogManager;
use TYPO3\CMS\Core\Messaging\AbstractMessage;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Configuration\ConfigurationManagerInterface;
use TYPO3\CMS\Extbase\Mvc\View\ViewInterface;
use TYPO3\CMS\Extbase\Mvc\Web\Routing\UriBuilder;
use TYPO3\CMS\Extbase\Persistence\Generic\PersistenceManager;
use TYPO3\CMS\Extbase\Utility\LocalizationUtility;

/**
 * Class AbstractController
 *
 * @author Steffen Kroggel <developer@steffenkroggel.de>
 * @author Maximilian Fäßler <maximilian@faesslerweb.de>
 * @copyright Steffen Kroggel
 * @package Madj2k_FeRegister
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License, version 3 or later
 */
abstract class AbstractController extends \Madj2k\AjaxApi\Controller\AjaxAbstractController
{

    /**
     * @const string
     */
    const SESSION_KEY_REFERRER = 'tx_feregister_referrer';

    /**
     * @var int
     */
    protected int $referrerPid = 0;


    /**
     * @var \Madj2k\FeRegister\Domain\Model\FrontendUser|null
     */
    protected ?FrontendUser $frontendUser = null;


    /**
     * @var \Madj2k\FeRegister\Domain\Repository\FrontendUserRepository
     * @TYPO3\CMS\Extbase\Annotation\Inject
     */
    protected FrontendUserRepository $frontendUserRepository;


    /**
     * @var \TYPO3\CMS\Extbase\Persistence\Generic\PersistenceManager
     * @TYPO3\CMS\Extbase\Annotation\Inject
     */
    protected PersistenceManager $persistenceManager;


    /**
     * @var \TYPO3\CMS\Core\Log\Logger|null
     */
    protected ?Logger $logger = null;


    /**
     * initialize
     *
     * @param \TYPO3\CMS\Extbase\Mvc\View\ViewInterface $view
     */
    public function initializeView(ViewInterface $view): void
    {
        parent::initializeView($view);

        // set referrer in session when calling Auth:Auth->index or LoginButton:Auth->loginButton
        // this is the main login page
        if (
            (
                ($this->getRequest()->getPluginName() == 'Auth')
                || ($this->getRequest()->getPluginName() == 'LoginButton')
            )
            && ($this->getRequest()->getControllerName() == 'Auth')
            && (
                ($this->getRequest()->getControllerActionName() == 'index')
                || ($this->getRequest()->getControllerActionName() == 'loginButton')
            )
        ) {

            // take referrer from settings
            if (
                ($referrerPid = intval($this->settings['referrerPid']))
                && (ClientUtility::isReferrerPidValid($referrerPid))
            ){
                $this->referrerPid = $referrerPid;
            }

            // referrer via variable always takes precedence
            if (ClientUtility::isReferrerPidValid(GeneralUtility::_GP('referrerPid'))) {
                $this->referrerPid = GeneralUtility::_GP('referrerPid');
            }

            // save referrer to current session
            if ($this->referrerPid) {
                $GLOBALS['TSFE']->fe_user->setAndSaveSessionData(self::SESSION_KEY_REFERRER, $this->referrerPid);
            }
        }

        // set this->referrer based on session data and assign it to all actions
        $this->referrerPid = intval($GLOBALS['TSFE']->fe_user->getSessionData(self::SESSION_KEY_REFERRER)) ?: 0;
        if ($this->referrerPid) {
            $this->view->assign('referrer', $this->referrerPid);
        }
    }


    /**
     * action index
     * This is the default action
     *
     * @param string $flashMessageToInject
     * @return void
     */
    public function indexAction(string $flashMessageToInject = '')
    {
        if ($flashMessageToInject) {
            $this->addFlashMessage(
                $flashMessageToInject,
                '',
                AbstractMessage::ERROR
            );
        }

        // nothing else to do here - is only a fallback
    }


    /**
     * Remove ErrorFlashMessage
     *
     * @see \TYPO3\CMS\Extbase\Mvc\Controller\ActionController::getErrorFlashMessage()
     */
    protected function getErrorFlashMessage(): bool
    {
        return false;
    }


    /**
     * Returns current logged in user object
     *
     * @return \Madj2k\FeRegister\Domain\Model\FrontendUser|null
     * @throws \TYPO3\CMS\Core\Context\Exception\AspectNotFoundException
     */
    protected function getFrontendUser(): ?FrontendUser
    {
        return FrontendUserSessionUtility::getLoggedInUser();
    }


    /**
     * Checks if user is logged in and redirects to login (if defined)
     *
     * @return void
     * @throws \TYPO3\CMS\Extbase\Mvc\Exception\UnsupportedRequestTypeException
     * @throws \TYPO3\CMS\Core\Context\Exception\AspectNotFoundException
     * @throws \TYPO3\CMS\Extbase\Mvc\Exception\StopActionException
     * @throws \TYPO3\CMS\Extbase\Configuration\Exception\InvalidConfigurationTypeException
     */
    protected function redirectIfUserNotLoggedIn(): void
    {
        if (!$this->getFrontendUser()) {
            $this->redirectToLogin();
        }
    }


    /**
     * Checks if user is logged in as guest and redirects to login (if defined)
     *
     * @return void
     * @throws \TYPO3\CMS\Extbase\Mvc\Exception\UnsupportedRequestTypeException
     * @throws \TYPO3\CMS\Core\Context\Exception\AspectNotFoundException
     * @throws \TYPO3\CMS\Extbase\Mvc\Exception\StopActionException
     * @throws \TYPO3\CMS\Extbase\Configuration\Exception\InvalidConfigurationTypeException
     */
    protected function redirectIfUserNotLoggedInOrGuest(): void
    {
        if (!$this->getFrontendUser()) {
            $this->redirectToLogin();

        } else if (FrontendUserUtility::isGuestUser($this->getFrontendUser())) {
            $this->redirectToWelcome();
        }
    }


    /**
     * Checks if user is logged in and redirects to welcome page
     *
     * @return void
     * @throws \TYPO3\CMS\Extbase\Mvc\Exception\UnsupportedRequestTypeException
     * @throws \TYPO3\CMS\Core\Context\Exception\AspectNotFoundException
     * @throws \TYPO3\CMS\Extbase\Mvc\Exception\StopActionException
     */
    protected function redirectIfUserLoggedIn(): void
    {

        if ($this->getFrontendUser()) {
            $this->redirectToWelcome();
        }
    }


    /**
     * Redirects to login page (if defined)
     *
     * @return void
     * @throws \TYPO3\CMS\Extbase\Mvc\Exception\StopActionException
     * @throws \TYPO3\CMS\Extbase\Mvc\Exception\UnsupportedRequestTypeException
     * @throws \TYPO3\CMS\Extbase\Configuration\Exception\InvalidConfigurationTypeException
     */
    protected function redirectToLogin(): void
    {
        // offer a link for users
        if (! $this->getFlashMessageCount()) {

            $this->addFlashMessage(
                LocalizationUtility::translate(
                    'abstractController.error.userNotLoggedIn',
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
            $this->settings['loginPid']?: 0
        );
    }


    /**
     * Redirects user to welcome page or the referer from the login
     *
     * @param bool $newGuestLogin
     * @return void
     * @throws \TYPO3\CMS\Extbase\Mvc\Exception\UnsupportedRequestTypeException
     * @throws \TYPO3\CMS\Core\Context\Exception\AspectNotFoundException
     * @throws \TYPO3\CMS\Extbase\Mvc\Exception\StopActionException
     */
    protected function redirectToWelcome(bool $newGuestLogin = false): void
    {

        // try redirecting to referrer
        $this->redirectToReferer($newGuestLogin);

        $pid = (($newGuestLogin && intval($this->settings['welcomeGuestPid']))
            ? intval($this->settings['welcomeGuestPid'])
            : intval($this->settings['welcomePid']));

        // we need a real redirect for the login to be effective
        /** @var  \TYPO3\CMS\Extbase\Mvc\Web\Routing\UriBuilder $uriBuilder */
        $uriBuilder = $this->objectManager->get(UriBuilder::class);
        $url = $uriBuilder->reset()
            ->setTargetPageUid($pid)
            ->setLinkAccessRestrictedPages(true)
            ->setCreateAbsoluteUri(true)
            ->setUseCacheHash(false)
            ->setArguments(
                [
                    'tx_feregister_' . ($pid ? 'welcome' : 'auth') => [
                        'action' => ($pid ? 'welcome' : 'index'),
                        'controller' => ($pid ? 'FrontendUser' : 'Auth'),
                    ],
                ]
            )
            ->build();

        $this->redirectToUri($url);
    }


    /**
     * Redirects to the referer from the login
     *
     * @param bool $newGuestLogin
     * @return void
     * @throws \TYPO3\CMS\Extbase\Mvc\Exception\UnsupportedRequestTypeException
     * @throws \TYPO3\CMS\Core\Context\Exception\AspectNotFoundException
     * @throws \TYPO3\CMS\Extbase\Mvc\Exception\StopActionException
     */
    protected function redirectToReferer(bool $newGuestLogin = false): void
    {

        if (
            (!$newGuestLogin)
            && (ClientUtility::isReferrerPidValid($this->referrerPid))
        ){
            $GLOBALS['TSFE']->fe_user->setAndSaveSessionData(self::SESSION_KEY_REFERRER, '');

            /** @var \TYPO3\CMS\Extbase\Mvc\Web\Routing\UriBuilder $uriBuilder */
            $uriBuilder = $this->objectManager->get(UriBuilder::class);

            $url = $uriBuilder
                ->reset()
                ->setTargetPageUid($this->referrerPid)
                ->setLinkAccessRestrictedPages(1)
                ->build();

            $this->redirectToUri($url);
        }
    }


    /**
     * Checks if user has filled out all mandatory fields and redirects to profile page (if defined)
     *
     * @param \Madj2k\FeRegister\Domain\Model\FrontendUserGroup|null $frontendUserGroup
     * @return void
     * @throws \TYPO3\CMS\Extbase\Mvc\Exception\StopActionException
     * @throws \TYPO3\CMS\Extbase\Mvc\Exception\UnsupportedRequestTypeException
     * @throws \TYPO3\CMS\Core\Context\Exception\AspectNotFoundException
     * @throws \TYPO3\CMS\Extbase\Configuration\Exception\InvalidConfigurationTypeException
     * @TYPO3\CMS\Extbase\Annotation\Validate("Madj2k\FeRegister\Validation\FrontendUserValidator", param="insecureFrontendUser")
     */
    protected function redirectIfUserHasMissingData(FrontendUserGroup $frontendUserGroup = null): void
    {
        // check if user has all relevant fields filled out
        // if not, redirect to edit form
        if ($this->getFrontendUser()) {

            $insecureFrontendUser = clone $this->getFrontendUser();
            if ($frontendUserGroup) {
                $insecureFrontendUser->setTempFrontendUserGroup($frontendUserGroup);
            }

            $frontendUserValidator = $this->objectManager->get(FrontendUserValidator::class);
            $frontendUserValidator->validate($insecureFrontendUser);

            if (! $frontendUserValidator->isValid($insecureFrontendUser)) {

                $this->addFlashMessage(
                    LocalizationUtility::translate(
                        'abstractController.warning.missingData',
                        'fe_register'
                    ),
                    '',
                    AbstractMessage::WARNING
                );

                 if ($this->settings['editUserPid']) {
                    $this->redirect(
                        'edit',
                        'FrontendUser',
                        null,
                        [
                            'frontendUser' => $this->getFrontendUser(),
                            'frontendUserGroup' => $frontendUserGroup
                        ],
                        $this->settings['editUserPid']
                    );
                }

                $this->redirect(
                    'index',
                    'FrontendUser',
                );
            }
        }
    }


    /**
     * Returns the number of flashMessages of all configured plugins
     * @return int
     * @throws \TYPO3\CMS\Extbase\Configuration\Exception\InvalidConfigurationTypeException
     */
    protected function getFlashMessageCount(): int
    {
        $frameworkSettings = \Madj2k\CoreExtended\Utility\GeneralUtility::getTypoScriptConfiguration(
            'Feregister',
            ConfigurationManagerInterface::CONFIGURATION_TYPE_FULL_TYPOSCRIPT
        );

        $cnt = 0;
        $pluginList = preg_grep('/^tx_feregister_[\d]*/', array_keys($frameworkSettings['plugin.']));
        foreach ($pluginList as $key => $value) {
            $identifier = 'extbase.flashmessages.'. trim($value, '.');
            $cnt += count($this->controllerContext->getFlashMessageQueue($identifier)->getAllMessages());
        }

        return $cnt;
    }


    /**
     * Returns storagePid
     *
     * @param
     * @return string
     * @throws \TYPO3\CMS\Extbase\Configuration\Exception\InvalidConfigurationTypeException
     */
    protected function getStoragePids(): string
    {
        $storagePid = 0;
        $settings = \Madj2k\CoreExtended\Utility\GeneralUtility::getTypoScriptConfiguration(
            'Feregister',
            ConfigurationManagerInterface::CONFIGURATION_TYPE_FRAMEWORK
        );

        if ($settings['persistence']['storagePid']) {
            $storagePid = $settings['persistence']['storagePid'];
        }

        return $storagePid;
    }


    /**
     * Returns logger instance
     *
     * @return \TYPO3\CMS\Core\Log\Logger
     */
    protected function getLogger(): Logger
    {
        if (!$this->logger instanceof Logger) {
            $this->logger = GeneralUtility::makeInstance(LogManager::class)
                ->getLogger(__CLASS__);
        }

        return $this->logger;
    }

}
