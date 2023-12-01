<?php
namespace Madj2k\FeRegister\Domain\Repository;

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

use TYPO3\CMS\Extbase\Persistence\QueryResultInterface;

/**
 * CleanerInterface
 *
 * @author Steffen Kroggel <developer@steffenkroggel.de>
 * @copyright Steffen Kroggel
 * @package Madj2k_FeRegister
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License, version 3 or later
 */
interface CleanerInterface
{

    /**
     * Really removes an object from this repository
     *
     * @param \TYPO3\CMS\Extbase\DomainObject\AbstractEntity $object
     * @return int
     * @throws \TYPO3\CMS\Extbase\Persistence\Generic\Exception
     */
    public function removeHard(\TYPO3\CMS\Extbase\DomainObject\AbstractEntity $object): int;


    /**
     * Find consents that are ready for cleanup
     *
     * @param int $daysExpired
     * @return \TYPO3\CMS\Extbase\Persistence\QueryResultInterface
     * @throws \TYPO3\CMS\Extbase\Persistence\Exception\InvalidQueryException
     * @api Used for cleanup via CLI
     */
    public function findReadyToRemove (int $daysExpired = 30): QueryResultInterface;

}
