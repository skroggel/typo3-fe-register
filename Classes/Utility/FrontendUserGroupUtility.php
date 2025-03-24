<?php
namespace Madj2k\FeRegister\Utility;

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
use Madj2k\FeRegister\Domain\Model\FrontendUser;
use Madj2k\FeRegister\Domain\Model\FrontendUserGroup;
use Madj2k\FeRegister\Domain\Repository\FrontendUserGroupRepository;
use TYPO3\CMS\Extbase\Domain\Model\FrontendUserGroup as FrontendUserGroupCore;
use TYPO3\CMS\Extbase\Object\ObjectManager;
use TYPO3\CMS\Extbase\Persistence\Generic\Session;

/**
 * Class FrontendUserGroupUtility
 *
 * @author Steffen Kroggel <developer@steffenkroggel.de>
 * @copyright Steffen Kroggel
 * @package Madj2k_FeRegister
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License, version 3 or later
 */
class FrontendUserGroupUtility
{

    /**
     * Returns all mandatory properties of user
     *
     * @param \TYPO3\CMS\Extbase\Domain\Model\FrontendUserGroup $frontendUserGroup
     * @return array
     * @throws \TYPO3\CMS\Extbase\Object\Exception
     */
    public static function getMandatoryFields(FrontendUserGroupCore $frontendUserGroup): array
    {
        $mandatoryFields = [];
        $frontendUser = GeneralUtility::makeInstance(FrontendUser::class);

        // upgrade given object
        if (!$frontendUserGroup instanceof FrontendUserGroup) {
            /** @var \TYPO3\CMS\Extbase\Object\ObjectManager $objectManager */
            $objectManager = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(ObjectManager::class);

            /** @var \Madj2k\FeRegister\Domain\Repository\FrontendUserGroupRepository $frontendUserGroupRepository */
            $frontendUserGroupRepository = $objectManager->get(FrontendUserGroupRepository::class);


            if (\TYPO3\CMS\Core\Utility\VersionNumberUtility::convertVersionNumberToInteger(TYPO3_version) < 10000000) {
                    // nothing to do
            } else {
                // we need to destroy the session data, since inherited objects with the same extbase_type are handled
                // as identical objects and hence cached
                /** @var \TYPO3\CMS\Extbase\Persistence\Generic\Session $persistenceSession */

                // !!! Important: Clearing persistenceSession-Cache produce issue on saving objects: !!!
                // --> The object of type "Madj2k\FeRegister\Domain\Model\FrontendUser" given to update must be persisted already, but is new.
                // SK does not remember why he has written this fix. If the error may occur again, maybe we could work here with...
                // ..."unregisterObject" instead of "destroy" (everything)
            //    $persistenceSession = $objectManager->get(Session::class);
            //    $persistenceSession->destroy();
            }

            $frontendUserGroup = $frontendUserGroupRepository->findByIdentifier($frontendUserGroup->getUid());
        }

        // get mandatory fields
        if (
            ($frontendUserGroup instanceof FrontendUserGroup)
            && ($mandatoryFieldsTemp = $frontendUserGroup->getTxFeregisterMembershipMandatoryFields())
        ){
            $mandatoryFieldsTemp = GeneralUtility::trimExplode(',', $mandatoryFieldsTemp);
            foreach($mandatoryFieldsTemp as $field) {
                $field = GeneralUtility::camelize($field);
                if (property_exists($frontendUser, $field)) {
                    $mandatoryFields[] = $field;
                }
            }
        }

        return $mandatoryFields;
    }

}
