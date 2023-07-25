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

use Madj2k\CoreExtended\Domain\Repository\StoragePidAwareAbstractRepository;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Extbase\Object\ObjectManager;
use TYPO3\CMS\Extbase\Persistence\Generic\Mapper\DataMapper;

/**
 * AbstractRepository
 *
 * @author Steffen Kroggel <developer@steffenkroggel.de>
 * @copyright Steffen Kroggel
 * @package Madj2k_FeRegister
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License, version 3 or later
 */
class AbstractRepository extends StoragePidAwareAbstractRepository
{

    /**
     * Really removes an object from this repository
     *
     * @param \TYPO3\CMS\Extbase\DomainObject\AbstractEntity $object
     * @return int
     * @throws \TYPO3\CMS\Extbase\Persistence\Generic\Exception
     */
    public function removeHard(\TYPO3\CMS\Extbase\DomainObject\AbstractEntity $object): int
    {
        /** @var \TYPO3\CMS\Extbase\Object\ObjectManager $objectManager */
        $objectManager = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(ObjectManager::class);

        /** @var \TYPO3\CMS\Extbase\Persistence\Generic\Mapper\DataMapper $dataMapper */
        $dataMapper = $objectManager->get(DataMapper::class);
        $tableName = $dataMapper->getDataMap(get_class($object))->getTableName();

        $connectionPool = \Madj2k\CoreExtended\Utility\GeneralUtility::makeInstance(ConnectionPool::class);
        $queryBuilder = $connectionPool->getQueryBuilderForTable($tableName);
        $affectedRows = $queryBuilder
            ->delete($tableName)
            ->where(
                $queryBuilder->expr()->eq('uid', $object->getUid())
            )
            ->execute();

        return intval($affectedRows);
    }

}
