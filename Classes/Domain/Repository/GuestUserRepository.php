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
 * FrontendUserRepository
 *
 * @author Maximilian Fäßler <maximilian@faesslerweb.de>
 * @author Steffen Kroggel <developer@steffenkroggel.de>
 * @copyright Steffen Kroggel
 * @package Madj2k_FeRegister
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License, version 3 or later
 */
class GuestUserRepository extends FrontendUserRepository
{

    /**
     * Find guestUsers that are ready for cleanup
     *
     * @param int $daysExpired
     * @return \TYPO3\CMS\Extbase\Persistence\QueryResultInterface
     * @throws \TYPO3\CMS\Extbase\Persistence\Exception\InvalidQueryException
     * @api Used for cleanup via CLI
     * implicitly tested
     */
    public function findReadyToRemove (int $daysExpired = 30): QueryResultInterface {

        $query = $this->createQuery();
        $query->getQuerySettings()->setIgnoreEnableFields(true);
        $query->getQuerySettings()->setIncludeDeleted(true);
        $query->getQuerySettings()->setRespectStoragePage(false);

        return $query->matching(
            $query->logicalOr(
                $query->logicalAnd(
                    $query->greaterThan('endtime', 0),
                    $query->lessThanOrEqual('endtime', (time() - ($daysExpired * 24 * 60 * 60)))
                ),
                $query->logicalAnd(
                    $query->equals('deleted', 1),
                    $query->lessThanOrEqual('tstamp', (time() - ($daysExpired * 24 * 60 * 60)))
                ),
                $query->logicalAnd(
                    $query->equals('disable', 1),
                    $query->lessThanOrEqual('tstamp', (time() - ($daysExpired * 24 * 60 * 60)))
                )
            )
        )->execute();
    }

}
