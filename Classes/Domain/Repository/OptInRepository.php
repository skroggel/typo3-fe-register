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

use Madj2k\FeRegister\Domain\Model\FrontendUser;
use Madj2k\FeRegister\Domain\Model\OptIn;
use TYPO3\CMS\Extbase\Persistence\QueryResultInterface;

/**
 * OptInRepository
 *
 * @author Steffen Kroggel <developer@steffenkroggel.de>
 * @copyright Steffen Kroggel
 * @package Madj2k_FeRegister
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License, version 3 or later
 */
class OptInRepository extends AbstractRepository implements CleanerInterface
{

    /**
     * Finds optIns which have the given uid even if they are deleted
     *
     * @param int $uid
     * @return \Madj2k\FeRegister\Domain\Model\OptIn|null
     * @throws \TYPO3\CMS\Extbase\Persistence\Exception\InvalidQueryException
     * implicitly tested
     */
    public function findByIdentifierIncludingDeleted(int $uid): ?OptIn
    {
        $query = $this->createQuery();
        $query->getQuerySettings()->setIncludeDeleted(true);
        $query->getQuerySettings()->setIgnoreEnableFields(true);

        $optIn = $query->matching(
            $query->logicalAnd(
                $query->equals('uid', $uid),
                $query->logicalOr(
                    $query->equals('endtime',0),
                    $query->greaterThan('endtime',time())
                )
            )
        )->setLimit(1)
            ->execute();

        return $optIn->getFirst();
    }


    /**
     * Finds optIns by tokenUser even if they are deleted
     *
     * @param string $tokenUser
     * @return \Madj2k\FeRegister\Domain\Model\OptIn|null
     * @throws \TYPO3\CMS\Extbase\Persistence\Exception\InvalidQueryException
     * implicitly tested
     */
    public function findOneByTokenUserIncludingDeleted(string $tokenUser): ?OptIn
    {
        $query = $this->createQuery();
        $query->getQuerySettings()->setIncludeDeleted(true);
        $query->getQuerySettings()->setIgnoreEnableFields(true);

        $optIn = $query->matching(
            $query->logicalAnd(
                $query->equals('tokenUser', $tokenUser),
                $query->logicalOr(
                    $query->equals('endtime',0),
                    $query->greaterThan('endtime',time())
                )
            )
        )->setLimit(1)
            ->execute();

        return $optIn->getFirst();
    }


    /**
     * Finds optIns by tokenUser even if they are deleted
     *
     * @param \Madj2k\FeRegister\Domain\Model\FrontendUser $frontendUser
     * @return \Madj2k\FeRegister\Domain\Model\OptIn|null
     * @throws \TYPO3\CMS\Extbase\Persistence\Exception\InvalidQueryException
     * implicitly tested
     */
    public function findOneByFrontendUserIncludingDeleted(FrontendUser $frontendUser): ?OptIn
    {
        $query = $this->createQuery();
        $query->getQuerySettings()->setIncludeDeleted(true);
        $query->getQuerySettings()->setIgnoreEnableFields(true);

        $optIn = $query->matching(
            $query->logicalAnd(
                $query->equals('frontendUserUid', $frontendUser->getUid()),
                $query->logicalOr(
                    $query->equals('endtime',0),
                    $query->greaterThan('endtime',time())
                )
            )
        )->setLimit(1)
            ->execute();

        return $optIn->getFirst();
    }


    /**
     * Find all pending group-memberships by frontendUser
     *
     * @var \Madj2k\FeRegister\Domain\Model\FrontendUser $frontendUser
     * @return \TYPO3\CMS\Extbase\Persistence\QueryResultInterface<\Madj2k\FeRegister\Domain\Model\OptIn|null>
     * @throws \TYPO3\CMS\Extbase\Persistence\Exception\InvalidQueryException
     * implicitly tested
     */
    public function findPendingGroupMembershipsByFrontendUser(
        FrontendUser $frontendUser
    ): QueryResultInterface
    {
        $query = $this->createQuery();
        $query->getQuerySettings()->setRespectStoragePage(false);

        return $query->matching(
            $query->logicalAnd(
                $query->equals('frontendUserUid', $frontendUser->getUid()),
                $query->equals('foreignTable', 'fe_groups'),
                $query->logicalOr(
                    $query->equals('endtime',0),
                    $query->greaterThan('endtime',time())
                )
            )
        )->execute();
    }


    /**
     * find opt-ins that are ready for cleanup
     *
     * @param int $daysExpired
     * @return \TYPO3\CMS\Extbase\Persistence\QueryResultInterface
     * @throws \TYPO3\CMS\Extbase\Persistence\Exception\InvalidQueryException
     * @api Used for cleanup via CLI
     * implicitly tested
     */
    public function findReadyToRemove (int $daysExpired = 30): QueryResultInterface
    {
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
                )
            )
        )->execute();

    }

}
