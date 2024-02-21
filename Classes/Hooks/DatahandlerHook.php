<?php

namespace Madj2k\FeRegister\Hooks;

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

use Madj2k\FeRegister\Domain\Repository\FrontendUserRepository;
use Madj2k\FeRegister\Domain\Repository\GuestUserRepository;
use Madj2k\FeRegister\Exception;
use Madj2k\FeRegister\Registration\FrontendUserRegistration;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Object\ObjectManager;


/**
 * Class DatahandlerHook
 *
 * @author Maximilian Fäßler <maximilian@faesslerweb.de>
 * @author Steffen Kroggel <developer@steffenkroggel.de>
 * @copyright Steffen Kroggel
 * @package Madj2k_FeRegister
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License, version 3 or later
 */
class DatahandlerHook
{


    /**
     * Hook: processCmdmap_deleteAction - fired when datasets are deleted
     *
     * @param string $table The table currently processing data for
     * @param string $id The record uid currently processing data for, [integer] or [string] (like 'NEW...')
     * @param array $recordToDelete The record that has been deleted including all data
     * @param boolean $recordWasDeleted Shows if record was already deleted (e.g. by another hook-call)
     * @param object $object \TYPO3\CMS\Core\DataHandling\DataHandler
     * @return void
     * @throws \Exception
     * @see \TYPO3\CMS\Core\DataHandling\DataHandler::deleteAction
     */
    public function processCmdmap_deleteAction($table, $id, $recordToDelete, $recordWasDeleted, $object)
    {
        /**
         * If a frontendUser is deleted in backend, we imitate the behavior of the system when a user
         * deletes his/her data in the frontend. This way all related objects can be deleted using the
         * normal signal-slots / events
         */
        try {
            if ($table == 'fe_users') {

                $repositoryName = FrontendUserRepository::class;
                if ( $recordToDelete['tx_extbase_type'] == '\Madj2k\FeRegister\Domain\Model\GuestUser') {
                    $repositoryName = GuestUserRepository::class;
                }

                /** @var \TYPO3\CMS\Extbase\Object\ObjectManager $objectManager */
                $objectManager = GeneralUtility::makeInstance(ObjectManager::class);
                $repository = $objectManager->get($repositoryName);

                /** @var \Madj2k\FeRegister\Domain\Model\FrontendUser $frontendUser */
                if ($frontendUser = $repository->findByIdentifierIncludingDisabled($id)) {

                    /** @var \Madj2k\FeRegister\Registration\FrontendUserRegistration $registration */
                    $registration = $objectManager->get(FrontendUserRegistration::class);

                    $registration->setFrontendUser($frontendUser)
                        ->endRegistration();
                }
            }

        } catch (Exception $e) {
            // do nothing
       }
    }
}
