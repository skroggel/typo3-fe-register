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

use Madj2k\FeRegister\Domain\Model\Title;
use Madj2k\FeRegister\Domain\Repository\TitleRepository;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Object\ObjectManager;
use TYPO3\CMS\Extbase\Persistence\Generic\PersistenceManager;

/**
 * Class TitleUtility
 *
 * @author Christian Dilger <c.dilger@addorange.de>
 * @copyright Steffen Kroggel
 * @package Madj2k_FeRegister
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License, version 3 or later
 */
class TitleUtility
{

    /**
     * Returns \Madj2k\FeRegister\Domain\Model\Title instance
     *
     * @param string $title
     * @param array $settings
     * @return Madj2k\FeRegister\Domain\Model\Title|null
     * @throws \TYPO3\CMS\Extbase\Persistence\Exception\IllegalObjectTypeException
     */
    public static function extractTxRegistrationTitle(string $title = '', array $settings = []):? Title
    {
        /** @var ObjectManager $objectManager */
        $objectManager = GeneralUtility::makeInstance(ObjectManager::class);

        /** @var TitleRepository $titleRepository */
        $titleRepository = $objectManager->get(TitleRepository::class);
        $txRegistrationTitle = $titleRepository->findOneByName($title);

        if (!$txRegistrationTitle && $title !== '') {

            $txRegistrationTitle = GeneralUtility::makeInstance(Title::class);
            $txRegistrationTitle->setName($title);

            $persistenceManager = $objectManager->get(PersistenceManager::class);
            $titleRepository->add($txRegistrationTitle);
            $persistenceManager->persistAll();
        }

        return $txRegistrationTitle;
    }

}
