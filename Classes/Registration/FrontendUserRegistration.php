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

use Madj2k\FeRegister\DataProtection\ConsentHandler;
use Madj2k\FeRegister\Domain\Model\FrontendUser;
use Madj2k\FeRegister\Domain\Model\OptIn;
use Madj2k\FeRegister\Exception;
use Madj2k\FeRegister\Utility\FrontendUserSessionUtility;
use Madj2k\FeRegister\Utility\FrontendUserUtility;
use Madj2k\FeRegister\Utility\PasswordUtility;
use TYPO3\CMS\Core\Log\LogLevel;


/**
 * FrontendUserRegistration
 *
 * @author Steffen Kroggel <developer@steffenkroggel.de>
 * @copyright Steffen Kroggel
 * @package Madj2k_FeRegister
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License, version 3 or later
 */
class FrontendUserRegistration extends AbstractRegistration
{

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
        // check if a user is of the right type - no GuestUsers allowed here
        /*
        if (FrontendUserUtility::isGuestUser($frontendUser)) {

            throw new Exception(
                'The given frontendUser is an instance of GuestUser. This is not allowed here.',
                1678359846
            );
        }*/

        return parent::setFrontendUser($frontendUser);
    }


    /**
     * Registers new FE-User - or sends another opt-in to existing user
     *
     * @return bool
     * @throws Exception
     * @throws \TYPO3\CMS\Core\Context\Exception\AspectNotFoundException
     * @throws \TYPO3\CMS\Core\Crypto\PasswordHashing\InvalidPasswordHashException
     * @throws \TYPO3\CMS\Extbase\Persistence\Exception\IllegalObjectTypeException
     * @throws \TYPO3\CMS\Extbase\Persistence\Exception\InvalidQueryException
     * @throws \TYPO3\CMS\Extbase\Persistence\Generic\Exception\NotImplementedException
     * @throws \TYPO3\CMS\Extbase\Persistence\Generic\Exception\TooDirtyException
     * @throws \TYPO3\CMS\Extbase\Object\Exception
     * @api
     */
    public function startRegistration(): bool
    {

        // check for frontendUser-object
        if (! $frontendUser = $this->getFrontendUser()) {
            throw new Exception('No frontendUser-object set.', 1434997734);
        }

        // Case 1: check if user already exists - no matter if enabled or disabled
        // then we generate an opt-in for additional data given
        /** @var \Madj2k\FeRegister\Domain\Model\FrontendUser $frontendUserPersisted */
        if ($frontendUserPersisted = $this->getFrontendUserPersisted()) {

            // add opt in - but only if additional data is set!
            // otherwise do update of data!
            if (! $this->getData()) {
                $this->setFrontendUserOptInUpdate($frontendUser);
            }

            $this->createOptIn();

            $this->getLogger()->log(
                LogLevel::INFO,
                sprintf(
                    'Opt-in for existing user "%s" successfully generated (id=%s, category=%s).',
                    strtolower($frontendUserPersisted->getUsername()),
                    $frontendUserPersisted->getUid(),
                    $this->getCategory()
                )
            );

            return true;

        }

        // check if a user is logged in. In this case no registration is possible!
        // except for those faggot GuestUsers
        if (
            (FrontendUserSessionUtility::getLoggedInUserId())
            && (! FrontendUserUtility::isGuestUser(FrontendUserSessionUtility::getLoggedInUser()))
        ){
            throw new Exception(
                'It is not possible to register a new user when already logged in.',
                1659691717
            );
        }

        // Case 2: if user does not exist yet, we create it and set a temporary password
        $this->frontendUser->setTempPlaintextPassword(PasswordUtility::generatePassword());
        $this->frontendUser->setPassword(PasswordUtility::saltPassword($this->frontendUser->getTempPlaintextPassword()));

        $this->getContextAwareFrontendUserRepository()->add($frontendUser);
        $this->persistenceManager->persistAll();

        $this->createOptIn();

        $this->getLogger()->log(
            LogLevel::INFO,
            sprintf(
                'Successfully registered user "%s" (id=%s, category=%s). Awaiting opt-in.',
                strtolower($frontendUser->getUsername()),
                $frontendUser->getUid(),
                $this->getCategory()
            )
        );

        return true;
    }


    /**
     * Checks given tokens from e-mail
     *
     * @param string $token Token for consent or denial to check for
     * @return int returns several codes
     *          200 = confirmed
     *          201 = confirmed, approval by admin pending
     *          202 = confirmed, approval by user pending
     *          210 = confirmed, updated frontendUser
     *          299 = confirmed, already deleted
     *          300 = denied
     *          301 = denied by admin
     *          302 = denied by user
     *          399 = denied, already deleted
     *          999 = Not found / Error
     * @throws Exception
     * @throws \TYPO3\CMS\Core\Crypto\PasswordHashing\InvalidPasswordHashException
     * @throws \TYPO3\CMS\Extbase\Configuration\Exception\InvalidConfigurationTypeException
     * @throws \TYPO3\CMS\Extbase\Persistence\Exception\IllegalObjectTypeException
     * @throws \TYPO3\CMS\Extbase\Persistence\Exception\InvalidQueryException
     * @throws \TYPO3\CMS\Extbase\Persistence\Exception\UnknownObjectException
     * @throws \TYPO3\CMS\Extbase\Persistence\Generic\Exception
     * @throws \TYPO3\CMS\Extbase\Persistence\Generic\Exception\NotImplementedException
     * @throws \TYPO3\CMS\Extbase\SignalSlot\Exception\InvalidSlotException
     * @throws \TYPO3\CMS\Extbase\SignalSlot\Exception\InvalidSlotReturnException
     * @throws \TYPO3\CMS\Extbase\Object\Exception
     * @api
     */
    public function validateOptIn(string $token): int
    {
        // check for frontendUserToken
        if (! $this->getFrontendUserToken()) {
            throw new Exception('No frontendUserToken set.', 1434997735);
        }

        if (
            ($optInPersisted = $this->getOptInPersisted())
            && ($frontendUserPersisted = $this->getFrontendUserPersisted())
        ) {

            $result = $this->checkIfOptInAlreadyDone($optInPersisted, $frontendUserPersisted, $token);
            if ($result > 0) {
                return $result;
            }

            $result = $this->checkIfOptInYes($optInPersisted, $frontendUserPersisted, $token);
            if ($result > 0) {
                return $result;
            }

//            2025-03-19: Commented out as there occurred some automatic inexplicable cancellations
//            $result = $this->checkIfOptInNo($optInPersisted, $frontendUserPersisted, $token);
//            if ($result > 0) {
//                return $result;
//            }
        }

        $this->getLogger()->log(
            LogLevel::WARNING,
            sprintf(
                'Opt-in or frontendUser for token "%s" can not be not found.',
                $this->getFrontendUserToken()
            )
        );

        return 999;
    }


    /**
     * Checks if optIn has been executed already
     *
     * @param \Madj2k\FeRegister\Domain\Model\OptIn $optInPersisted
     * @param \Madj2k\FeRegister\Domain\Model\FrontendUser $frontendUserPersisted
     * @param string $token
     * @return int
     */
    protected function checkIfOptInAlreadyDone(OptIn $optInPersisted, FrontendUser $frontendUserPersisted, string $token): int
    {

        // check if optIn has already been denied (and deleted - because denied is always deleted)
        if ($optInPersisted->getApproved() == -1) {
            $this->getLogger()->log(
                LogLevel::WARNING, sprintf(
                    'OptIn with uid=%s has been withdrawn by user.',
                    $optInPersisted->getUid(),
                )
            );
            return 302;
        }

        if ($optInPersisted->getAdminApproved() == -1) {
            $this->getLogger()->log(
                LogLevel::WARNING, sprintf(
                    'OptIn with uid=%s has been denied by admins.',
                    $optInPersisted->getUid(),
                )
            );
            return 301;
        }


        // check if already processed finally after positive decision
        // Then return the status from the former decision process
        if ($optInPersisted->getDeleted()) {

            $this->getLogger()->log(
                LogLevel::WARNING, sprintf(
                    'OptIn with uid=%s is not valid any more.',
                    $optInPersisted->getUid(),
                )
            );

            // handling mismatch between current status and clicked linked
            // e.g. if it has been approved BUT someone clicks a decline-link afterwards
            if (
                (
                    ($optInPersisted->getApproved() == 1)
                    && ($token == $optInPersisted->getTokenNo())
                )||
                (
                    ($optInPersisted->getAdminApproved() == 1)
                    && ($token == $optInPersisted->getAdminTokenNo())
                )
            ) {
                return 999;
            }

            // return old status
            if (
                ($optInPersisted->getApproved() == 1)
                && ($optInPersisted->getAdminApproved() == 1)
            ) {
                return 299;
            }

            return 399;
        }

        return 0;
    }


    /**
     * Checks if optIn has been accepted
     *
     * @param \Madj2k\FeRegister\Domain\Model\OptIn $optInPersisted
     * @param \Madj2k\FeRegister\Domain\Model\FrontendUser $frontendUserPersisted
     * @param string $token
     * @return int
     * @throws Exception
     * @throws \TYPO3\CMS\Core\Crypto\PasswordHashing\InvalidPasswordHashException
     * @throws \TYPO3\CMS\Extbase\Configuration\Exception\InvalidConfigurationTypeException
     * @throws \TYPO3\CMS\Extbase\Persistence\Exception\IllegalObjectTypeException
     * @throws \TYPO3\CMS\Extbase\Persistence\Exception\InvalidQueryException
     * @throws \TYPO3\CMS\Extbase\Persistence\Exception\UnknownObjectException
     * @throws \TYPO3\CMS\Extbase\Persistence\Generic\Exception
     * @throws \TYPO3\CMS\Extbase\Persistence\Generic\Exception\NotImplementedException
     * @throws \TYPO3\CMS\Extbase\SignalSlot\Exception\InvalidSlotException
     * @throws \TYPO3\CMS\Extbase\SignalSlot\Exception\InvalidSlotReturnException
     */
    protected function checkIfOptInYes(OptIn $optInPersisted, FrontendUser $frontendUserPersisted, string $token): int
    {

        // check if we are in god-mode
        $adminMode = ($token == $optInPersisted->getAdminTokenYes()) || ($token == $optInPersisted->getAdminTokenNo());

        if (
            ($token == $optInPersisted->getTokenYes())
            || ($token == $optInPersisted->getAdminTokenYes())
        ){

            $getter = 'getApproved';
            $setter = 'setApproved';
            $signalSlot = self::SIGNAL_AFTER_APPROVAL_OPTIN;
            if ($adminMode) {
                $getter = 'getAdminApproved';
                $setter = 'setAdminApproved';
                $signalSlot = self::SIGNAL_AFTER_APPROVAL_OPTIN_ADMIN;
            }

            // check if already approved by user or admin
            if (! $optInPersisted->$getter()) {

                // else: approve now!
                $optInPersisted->$setter(1);

                // we do NOT set a category-parameter here. We use the append-method instead.
                // This way we either send a mail from this extension or from another - never both!
                $this->dispatchSignalSlot($signalSlot . ucfirst($this->getCategory()));

                // add privacy entry for non-admins
                if (! $adminMode) {

                    // add privacy for frontendUser
                    if ($request = $this->getRequest()) {
                        ConsentHandler::add(
                            $request,
                            $frontendUserPersisted,
                            $optInPersisted,
                            ($optInPersisted->getCategory() ? 'accepted opt-in for ' . $optInPersisted->getCategory() : 'accepted opt-in'),
                        );
                    }
                }

                // do the update
                $this->optInRepository->update($optInPersisted);
                $this->persistenceManager->persistAll();
            }

            // still waiting for approval on the counterpart?
            if (! $optInPersisted->getAdminApproved()) {
                $this->getLogger()->log(
                    LogLevel::INFO, sprintf(
                        'OptIn with uid=%s is waiting for approval of admins.',
                        $optInPersisted->getUid(),
                    )
                );
                return 201;
            }

            if (! $optInPersisted->getApproved()) {
                $this->getLogger()->log(
                    LogLevel::INFO, sprintf(
                        'OptIn with uid=%s is waiting for approval of user.',
                        $optInPersisted->getUid(),
                    )
                );
                return 202;
            }

            // else: update frontendUser according to stored data
            // now that we have a valid optIn it is safe to persist the form-data in the frontendUser-object
            $updatedUser = false;
            if ($optInPersisted->getFrontendUserUpdate()) {
                $updatedUser = true;

                foreach ($optInPersisted->getFrontendUserUpdate() as $property => $value) {

                    $setter = 'set' . ucfirst($property);
                    if (method_exists($frontendUserPersisted, $setter)) {
                        $frontendUserPersisted->$setter($value);

                        $this->getLogger()->log(
                            LogLevel::INFO, sprintf(
                                'Updating field %s in frontendUser.',
                                $property
                            )
                        );
                    }
                }
            }

            // special treatment for group-optIns
            if (
                ($optInPersisted->getForeignTable() == 'fe_groups')
                && ($groupId = $optInPersisted->getForeignUid())
            ){
                /** @var \Madj2k\FeRegister\Domain\Model\FrontendUserGroup $frontendUserGroup */
                $frontendUserGroup = $this->frontendUserGroupRepository->findByUid($groupId);
                $frontendUserPersisted->addUsergroup($frontendUserGroup);

                $this->getLogger()->log(
                    LogLevel::INFO, sprintf(
                        'Joining group with %s.',
                        $groupId
                    )
                );
            }

            // synchronize frontendUser-objects!
            $this->frontendUser = $frontendUserPersisted;
            $this->frontendUserRepository->update($frontendUserPersisted);

            // complete registration-process
            $this->completeRegistration();

            // mark opt-in as deleted
            $this->optInRepository->remove($optInPersisted);
            $this->persistenceManager->persistAll();

            $this->getLogger()->log(
                LogLevel::INFO,
                sprintf(
                    'Opt-in with uid=%s was successfully accepted (frontendUser uid=%s, category=%s).',
                    $optInPersisted->getUid(),
                    $frontendUserPersisted->getUid(),
                    $optInPersisted->getCategory()
                )
            );

            return ($updatedUser? 210 : 200);
        }

        return 0;
    }


    /**
     * Checks if optIn has been declined
     *
     * @param \Madj2k\FeRegister\Domain\Model\OptIn $optInPersisted
     * @param \Madj2k\FeRegister\Domain\Model\FrontendUser $frontendUserPersisted
     * @param string $token
     * @return int
     * @throws Exception
     * @throws \TYPO3\CMS\Core\Crypto\PasswordHashing\InvalidPasswordHashException
     * @throws \TYPO3\CMS\Extbase\Configuration\Exception\InvalidConfigurationTypeException
     * @throws \TYPO3\CMS\Extbase\Persistence\Exception\IllegalObjectTypeException
     * @throws \TYPO3\CMS\Extbase\Persistence\Exception\InvalidQueryException
     * @throws \TYPO3\CMS\Extbase\Persistence\Exception\UnknownObjectException
     * @throws \TYPO3\CMS\Extbase\Persistence\Generic\Exception
     * @throws \TYPO3\CMS\Extbase\Persistence\Generic\Exception\NotImplementedException
     * @throws \TYPO3\CMS\Extbase\SignalSlot\Exception\InvalidSlotException
     * @throws \TYPO3\CMS\Extbase\SignalSlot\Exception\InvalidSlotReturnException
     */
    protected function checkIfOptInNo(OptIn $optInPersisted, FrontendUser $frontendUserPersisted, string $token): int
    {

        // check if we are in god-mode
        $adminMode = ($token == $optInPersisted->getAdminTokenYes()) || ($token == $optInPersisted->getAdminTokenNo());

        if (
            ($token == $optInPersisted->getTokenNo())
            || ($token == $optInPersisted->getAdminTokenNo())
        ){

            $setter = 'setApproved';
            $signalSlot = self::SIGNAL_AFTER_DENIAL_OPTIN;
            if ($adminMode) {
                $setter = 'setAdminApproved';
                $signalSlot = self::SIGNAL_AFTER_DENIAL_OPTIN_ADMIN;
            }

            // else: disapprove now!
            $optInPersisted->$setter(-1);
            $this->optInRepository->update($optInPersisted);
            $this->persistenceManager->persistAll();

            // we do NOT set a category-parameter here. We use the append-method instead.
            // This way we either send a mail from this extension or from another - never both!
            $this->dispatchSignalSlot($signalSlot . ucfirst($this->getCategory()));

            // cancel registration
            $this->cancelRegistration();

            // mark opt-in as deleted
            $this->optInRepository->remove($optInPersisted);
            $this->persistenceManager->persistAll();

            $this->getLogger()->log(
                LogLevel::INFO,
                sprintf(
                    'Opt-in with uid=%s was successfully canceled (frontendUser uid=%s, category=%s).',
                    $optInPersisted->getUid(),
                    $frontendUserPersisted->getUid(),
                    $optInPersisted->getCategory()
                )
            );

            return 300;
        }

        return 0;
    }

}
