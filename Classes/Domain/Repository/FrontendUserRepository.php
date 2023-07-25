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
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Extbase\Object\ObjectManager;
use TYPO3\CMS\Extbase\Persistence\Generic\Mapper\DataMapper;
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
class FrontendUserRepository extends AbstractRepository implements CleanerInterface
{

    /**
     * Finds users which have the given uid even if they are disabled
     * This is relevant for checking during registration or profile editing
     *
     * @param int $uid
     * @return \Madj2k\FeRegister\Domain\Model\FrontendUser|null
     * implicitly tested
     */
    public function findByIdentifierIncludingDisabled(int $uid): ?FrontendUser
    {
        $query = $this->createQuery();
        $query->getQuerySettings()->setIncludeDeleted(false);
        $query->getQuerySettings()->setIgnoreEnableFields(true);

        $user = $query->matching(
            $query->equals('uid', $uid)
        )->setLimit(1)
            ->execute();

        return $user->getFirst();
    }


    /**
     * Finds users which have the given uid even if they are deleted
     * This is relevant for checking during registration or profile editing
     *
     * @param int $uid
     * @return \Madj2k\FeRegister\Domain\Model\FrontendUser|null
     * implicitly tested
     */
    public function findByIdentifierIncludingDeleted (int $uid): ?FrontendUser
    {
        $query = $this->createQuery();
        $query->getQuerySettings()->setIncludeDeleted(true);
        $query->getQuerySettings()->setIgnoreEnableFields(true);

        $user = $query->matching(
            $query->equals('uid', $uid)
        )->setLimit(1)
            ->execute();

        return $user->getFirst();
    }


    /**
     * Finds users which have the given username even if they are disabled
     * This is relevant for checking during registration or profile editing
     *
     * @param string $username
     * @return \Madj2k\FeRegister\Domain\Model\FrontendUser|null
     * implicitly tested
     */
    public function findOneByUsernameIncludingDisabled(string $username): ?FrontendUser
    {
        $query = $this->createQuery();
        $query->getQuerySettings()->setIncludeDeleted(false);
        $query->getQuerySettings()->setIgnoreEnableFields(true);

        $user = $query->matching(
            $query->equals('username', strtolower($username))
        )->setLimit(1)
            ->execute();

        return $user->getFirst();
    }


    /**
     * Find all frontend users that have been expired x days ago
     *
     * @param int $daysExpired
     * @return \TYPO3\CMS\Extbase\Persistence\QueryResultInterface
     * @throws \TYPO3\CMS\Extbase\Persistence\Exception\InvalidQueryException
     * @api Used for cleanup via CLI
     * implicitly tested
     */
    public function findReadyToMarkAsDeleted (int $daysExpired = 7): QueryResultInterface
    {

        $query = $this->createQuery();
        $query->getQuerySettings()->setIgnoreEnableFields(true);
        $query->getQuerySettings()->setRespectStoragePage(false);

        return $query->matching(
            $query->logicalOr(
                $query->logicalAnd(
                    $query->greaterThan('endtime', 0),
                    $query->lessThanOrEqual('endtime', (time() - ($daysExpired * 24 * 60 * 60)))
                ),
                $query->logicalAnd(
                    $query->equals('disable', 1),
                    $query->greaterThan('lastlogin', 0),
                    $query->lessThanOrEqual('tstamp', (time() - ($daysExpired * 24 * 60 * 60)))
                )
            )
        )->execute();
    }


    /**
     * Find frontendUsers that are ready for cleanup
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
        $query->getQuerySettings()->setRespectStoragePage(false);

        return $query->matching(
            $query->logicalAnd(
                $query->equals('disable', 1),
                $query->equals('lastlogin', 0),
                $query->lessThanOrEqual('tstamp', (time() - ($daysExpired * 24 * 60 * 60)))
            )
        )->execute();
    }


    /**
     * Find all deleted frontend users that have been deleted x days ago and have not yet been anonymised/encrypted
     *
     * @param int $daysDeleted
     * @return \TYPO3\CMS\Extbase\Persistence\QueryResultInterface
     * @throws \TYPO3\CMS\Extbase\Persistence\Exception\InvalidQueryException
     * @api Used for anonymisation via CLI
     */
    public function findReadyForAnonymisation (int $daysDeleted = 7): QueryResultInterface
    {
        $query = $this->createQuery();
        $query->getQuerySettings()->setRespectStoragePage(false);
        $query->getQuerySettings()->setIncludeDeleted(true);
        $query->getQuerySettings()->setIgnoreEnableFields(true);

        $query->matching(
            $query->logicalAnd(
                $query->equals('deleted', 1),
                $query->lessThan('txFeregisterDataProtectionStatus', 1),
                $query->logicalAnd(
                    $query->greaterThan('tstamp', 0),
                    $query->lessThanOrEqual('tstamp', (time() - ($daysDeleted * 24 * 60 * 60)))
                )
            )
        );

        return $query->execute();
    }

}

