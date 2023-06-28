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

use Madj2k\FeRegister\Domain\Model\GuestUser;
use Madj2k\FeRegister\Registration\GuestUserRegistration;
use Madj2k\FeRegister\Service\AbstractAuthenticationService;
use Madj2k\FeRegister\Utility\FrontendUserSessionUtility;
use TYPO3\CMS\Core\Messaging\AbstractMessage;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Utility\LocalizationUtility;
use TYPO3\CMS\Frontend\Authentication\FrontendUserAuthentication;

/**
 * Class AuthGuestController
 *
 * @author Steffen Kroggel <developer@steffenkroggel.de>
 * @author Maximilian Fäßler <maximilian@faesslerweb.de>
 * @copyright Steffen Kroggel
 * @package Madj2k_FeRegister
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License, version 3 or later
 */
class AuthGuestController extends AbstractController
{

    /**
     * @var \Madj2k\FeRegister\Registration\GuestUserRegistration
     * @TYPO3\CMS\Extbase\Annotation\Inject
     */
    protected GuestUserRegistration $guestUserRegistration;


    /**
     * action create
     *
     * @param int $dummy
     * @return void
     * @throws \Madj2k\FeRegister\Exception
     * @throws \TYPO3\CMS\Core\Context\Exception\AspectNotFoundException
     * @throws \TYPO3\CMS\Extbase\Configuration\Exception\InvalidConfigurationTypeException
     * @throws \TYPO3\CMS\Extbase\Mvc\Exception\StopActionException
     * @throws \TYPO3\CMS\Extbase\Mvc\Exception\UnsupportedRequestTypeException
     * @throws \TYPO3\CMS\Extbase\Persistence\Exception\IllegalObjectTypeException
     * @throws \TYPO3\CMS\Extbase\Persistence\Exception\UnknownObjectException
     * @throws \TYPO3\CMS\Extbase\Persistence\Generic\Exception\NotImplementedException
     * @throws \TYPO3\CMS\Extbase\SignalSlot\Exception\InvalidSlotException
     * @throws \TYPO3\CMS\Extbase\SignalSlot\Exception\InvalidSlotReturnException
     * @throws \TYPO3\CMS\Extbase\Persistence\Exception\InvalidQueryException
     * @throws \TYPO3\CMS\Core\Crypto\PasswordHashing\InvalidPasswordHashException
     * @throws \TYPO3\CMS\Core\Exception
     * @TYPO3\CMS\Extbase\Annotation\Validate("Madj2k\FeRegister\Validation\Consent\TermsValidator", param="dummy")
     */
    public function createAction(int $dummy): void
    {
        // send back already logged-in user. Nothing to do here
        if (FrontendUserSessionUtility::getLoggedInUserId()) {
            $this->addFlashMessage(
                LocalizationUtility::translate(
                    'authGuestController.error.guestLoginImpossible',
                    $this->extensionName
                ),
                '',
                AbstractMessage::ERROR
            );
            $this->redirect('index');
        }

        if ($this->guestUserRegistration->setRequest($this->request)->startRegistration()) {
            if ($this->guestUserRegistration->completeRegistration()) {

                /** @var \Madj2k\FeRegister\Domain\Model\FrontendUser $frontendUser */
                if ($frontendUser = $this->guestUserRegistration->getFrontendUserPersisted()) {
                    $token = $frontendUser->getUsername();

                    $this->redirect(
                        'login',
                        'AuthGuest',
                        null,
                        ['token' => $token, 'newLogin' => true]
                    );
                }
            }
        }

        // if something went wrong on the way...
        $this->addFlashMessage(
            LocalizationUtility::translate(
                'authGuestController.error.guestLoginImpossible',
                $this->extensionName
            ),
            '',
            AbstractMessage::ERROR
        );

        $this->redirect('index', 'Auth');
    }


    /**
     * action login
     *
     * @param string $token
     * @param bool $newLogin
     * @return void
     * @throws \Madj2k\FeRegister\Exception
     * @throws \TYPO3\CMS\Core\Context\Exception\AspectNotFoundException
     * @throws \TYPO3\CMS\Extbase\Configuration\Exception\InvalidConfigurationTypeException
     * @throws \TYPO3\CMS\Extbase\Mvc\Exception\StopActionException
     * @throws \TYPO3\CMS\Extbase\Mvc\Exception\UnsupportedRequestTypeException
     * @throws \TYPO3\CMS\Extbase\Persistence\Exception\IllegalObjectTypeException
     * @throws \TYPO3\CMS\Extbase\Persistence\Exception\UnknownObjectException
     * @throws \TYPO3\CMS\Extbase\Persistence\Generic\Exception\NotImplementedException
     * @throws \TYPO3\CMS\Extbase\SignalSlot\Exception\InvalidSlotException
     * @throws \TYPO3\CMS\Extbase\SignalSlot\Exception\InvalidSlotReturnException
     * @throws \TYPO3\CMS\Extbase\Persistence\Exception\InvalidQueryException
     * @throws \TYPO3\CMS\Core\Crypto\PasswordHashing\InvalidPasswordHashException
     * @throws \TYPO3\CMS\Core\Exception
     */
    public function loginAction(string $token, bool $newLogin = false): void
    {
        // send back already logged-in user. Nothing to do here
        if (FrontendUserSessionUtility::getLoggedInUserId()) {
            $this->addFlashMessage(
                LocalizationUtility::translate(
                    'authGuestController.error.guestLoginImpossible',
                    $this->extensionName
                ),
                '',
                AbstractMessage::ERROR
            );
            $this->redirect('index');
        }

        // do login
        $_POST['logintype'] = 'login';
        $_POST['user'] = $token;
        $_POST['pass'] = '';

        $authService = GeneralUtility::makeInstance(FrontendUserAuthentication::class);
        $authService->start();

        if (
            !$authService->loginFailure
            && $authService->loginSessionStarted
        ) {
            $this->redirectToWelcome($newLogin);
        }

        $this->addFlashMessage(
            LocalizationUtility::translate(
                'authGuestController.error.invalidGuestToken',
                $this->extensionName
            ),
            '',
            AbstractMessage::ERROR
        );

        $this->redirect('index', 'Auth');
    }

}


