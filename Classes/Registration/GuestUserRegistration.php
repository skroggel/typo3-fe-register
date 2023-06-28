<?php

namespace Madj2k\FeRegister\Registration;

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
use Madj2k\FeRegister\DataProtection\ConsentHandler;
use Madj2k\FeRegister\Domain\Model\FrontendUser;
use Madj2k\FeRegister\Domain\Model\GuestUser;
use Madj2k\FeRegister\Exception;
use Madj2k\FeRegister\Utility\FrontendUserSessionUtility;
use Madj2k\FeRegister\Utility\FrontendUserUtility;
use \Madj2k\FeRegister\Utility\PasswordUtility;

/**
 * GuestUserRegistration
 *
 * @author Steffen Kroggel <developer@steffenkroggel.de>
 * @copyright Steffen Kroggel
 * @package Madj2k_FeRegister
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License, version 3 or later
 */
class GuestUserRegistration extends AbstractRegistration
{

    /**
     * Get the frontendUser object
     *
     * @return \Madj2k\FeRegister\Domain\Model\FrontendUser|null
     * @throws Exception
     * @throws \TYPO3\CMS\Core\Context\Exception\AspectNotFoundException
     * @throws \TYPO3\CMS\Extbase\Configuration\Exception\InvalidConfigurationTypeException
     * @throws \TYPO3\CMS\Extbase\Persistence\Exception\IllegalObjectTypeException
     * @api
     */
    public function getFrontendUser():? FrontendUser {

        // load guest if no user is set
        if (! $this->frontendUser) {
            /** @var \Madj2k\FeRegister\Domain\Model\GuestUser $guestUser */
            $this->setFrontendUser(GeneralUtility::makeInstance(GuestUser::class));
        }

        return parent::getFrontendUser();
    }


    /**
     * Sets the frontendUser
     *
     * @var \Madj2k\FeRegister\Domain\Model\FrontendUser $frontendUser
     * @return self
     * @throws Exception
     * @throws \TYPO3\CMS\Core\Context\Exception\AspectNotFoundException
     * @throws \TYPO3\CMS\Extbase\Configuration\Exception\InvalidConfigurationTypeException
     * @throws \TYPO3\CMS\Extbase\Persistence\Exception\IllegalObjectTypeException
     */
    public function setFrontendUser(FrontendUser $frontendUser): self
    {
        // check if a user is of the right type - only GuestUsers allowed here
        if (! FrontendUserUtility::isGuestUser($frontendUser)) {

            throw new Exception(
                'The given frontendUser is not an instance of GuestUser.',
                1678359847
            );
        }

        return parent::setFrontendUser($frontendUser);
    }


    /**
     * Registers new guestUser
     *
     * @return bool
     * @throws Exception
     * @throws \TYPO3\CMS\Core\Context\Exception\AspectNotFoundException
     * @throws \TYPO3\CMS\Core\Crypto\PasswordHashing\InvalidPasswordHashException
     * @throws \TYPO3\CMS\Extbase\Configuration\Exception\InvalidConfigurationTypeException
     * @throws \TYPO3\CMS\Extbase\Mvc\Exception\NoSuchArgumentException
     * @throws \TYPO3\CMS\Extbase\Persistence\Exception\IllegalObjectTypeException
     * @throws \TYPO3\CMS\Extbase\Persistence\Exception\UnknownObjectException
     * @throws \TYPO3\CMS\Extbase\Persistence\Generic\Exception
     * @api
     */
    public function startRegistration(): bool
    {
        // check for frontendUser-object
        if (!$this->getFrontendUser()) {
            throw new Exception('No frontendUser-object set.', 1661332326);
        }

        // check if a user is logged in. In this case no registration is needed!
        if (FrontendUserSessionUtility::getLoggedInUserId()) {
            throw new Exception('Registration is not necessary for logged in users.', 1661332376);
        }

        $frontendUser = $this->getFrontendUser();
        if ($frontendUser->_isNew()) {
            $this->frontendUser->setPassword(PasswordUtility::saltPassword(PasswordUtility::generatePassword()));

            $this->getContextAwareFrontendUserRepository()->add($frontendUser);
            $this->persistenceManager->persistAll();

            // add privacy-object for user
            if ($request = $this->getRequest()) {
                ConsentHandler::add(
                    $request,
                    $frontendUser,
                    $frontendUser,
                    sprintf(
                        'Created guestUser "%s" (disabled=%s, id=%s, category=%s).',
                        strtolower($frontendUser->getUsername()),
                        intval($frontendUser->getDisable()),
                        $frontendUser->getUid(),
                        $this->getCategory()
                    )
                );
            }

            return true;
        }

        return false;
    }

}
