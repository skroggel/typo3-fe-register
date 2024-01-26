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

use Madj2k\FeRegister\Domain\Model\FrontendUser;
use Madj2k\FeRegister\Domain\Model\GuestUser;
use Madj2k\FeRegister\Domain\Repository\FrontendUserRepository;
use Madj2k\FeRegister\Domain\Repository\GuestUserRepository;
use TYPO3\CMS\Extbase\Object\ObjectManager;

/**
 * Class FrontendUserSessionUtility
 *
 * @author Steffen Kroggel <developer@steffenkroggel.de>
 * @author Maximilian Fäßler <maximilian@faesslerweb.de>
 * @copyright Steffen Kroggel
 * @package Madj2k_FeRegister
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License, version 3 or later
 */
class FrontendUserSessionUtility extends \Madj2k\CoreExtended\Utility\FrontendUserSessionUtility
{

    /**
     * Id of logged-in User
     *
     * @return \Madj2k\FeRegister\Domain\Model\FrontendUser|null
     * @throws \TYPO3\CMS\Core\Context\Exception\AspectNotFoundException
     */
    public static function getLoggedInUser(): ?FrontendUser
    {

        if ($uid = self::getLoggedInUserId()) {

            /** @var }TYPO3\CMS\Extbase\Object\ObjectManager\ObjectManager $objectManager */
            $objectManager = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(ObjectManager::class);

            /** @var \Madj2k\FeRegister\Domain\Repository\FrontendUserRepository $frontendUserRepository */
            $frontendUserRepository = $objectManager->get(FrontendUserRepository::class);

            /** @var \Madj2k\FeRegister\Domain\Repository\GuestUserRepository $guestUserRepository */
            $guestUserRepository = $objectManager->get(GuestUserRepository::class);

            /** @var \Madj2k\FeRegister\Domain\Model\GuestUser $frontendUser */
            if ($frontendUser = $guestUserRepository->findByIdentifier($uid)) {
                return $frontendUser;
            }

            /** @var \Madj2k\FeRegister\Domain\Model\FrontendUser $frontendUser */
            if ($frontendUser = $frontendUserRepository->findByIdentifier($uid)) {
                return $frontendUser;
            }
        }

        return null;
    }

}
