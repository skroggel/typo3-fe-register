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
 * FrontendUserGroupRepository
 *
 * @author Maximilian Fäßler <maximilian@faesslerweb.de>
 * @author Steffen Kroggel <developer@steffenkroggel.de>
 * @copyright Steffen Kroggel
 * @package Madj2k_FeRegister
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License, version 3 or later
 */
class FrontendUserGroupRepository extends AbstractRepository
{

    /**
     * Finds all frontendUserGroups one can acquire a membership for
     *
     * @return \TYPO3\CMS\Extbase\Persistence\QueryResultInterface<\Madj2k\FeRegister\Domain\Model\FrontendUserGroup|null>
     * @throws \TYPO3\CMS\Extbase\Persistence\Exception\InvalidQueryException
     */
    public function findMembershipable(): QueryResultInterface
    {
        // return all services which do not pass the closingDate or openingDate
        $query = $this->createQuery();

        //  search globally
        $query->getQuerySettings()->setRespectStoragePage(false);

        $query->matching(
            $query->logicalAnd(
                $query->logicalOr(
                    $query->greaterThanOrEqual('txFeregisterMembershipClosingDate', time()),
                    $query->equals('txFeregisterMembershipClosingDate', 0)
                ),
                $query->logicalOr(
                    $query->lessThanOrEqual('txFeregisterMembershipOpeningDate', time()),
                    $query->equals('txFeregisterMembershipOpeningDate', 0)
                ),
                $query->equals('txFeregisterIsMembership', 1)
            )
        );

        return $query->execute();
    }
}
