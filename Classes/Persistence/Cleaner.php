<?php
namespace Madj2k\FeRegister\Persistence;

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
use Madj2k\FeRegister\Domain\Repository\CleanerInterface;
use Madj2k\FeRegister\Domain\Repository\FrontendUserRepository;
use Madj2k\FeRegister\Domain\Repository\GuestUserRepository;
use Madj2k\FeRegister\Domain\Repository\OptInRepository;
use Madj2k\FeRegister\Domain\Repository\ConsentRepository;
use TYPO3\CMS\Core\Log\Logger;
use TYPO3\CMS\Core\Log\LogLevel;
use TYPO3\CMS\Core\Log\LogManager;
use TYPO3\CMS\Extbase\Persistence\Generic\PersistenceManager;

/**
 * Class Cleaner
 *
 * @author Steffen Kroggel <developer@steffenkroggel.de>
 * @copyright Steffen Kroggel
 * @package Madj2k_FeRegister
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License, version 3 or later
 */
class Cleaner
{

    /**
     * @var bool
     */
    protected bool $dryRun = false;


    /**
     * @var \Madj2k\FeRegister\Domain\Repository\OptInRepository|null
     * @TYPO3\CMS\Extbase\Annotation\Inject
     */
    protected ?OptInRepository $optInRepository = null;


    /**
     * @var \Madj2k\FeRegister\Domain\Repository\FrontendUserRepository|null
     * @TYPO3\CMS\Extbase\Annotation\Inject
     */
    protected ?FrontendUserRepository $frontendUserRepository = null;


    /**
     * @var \Madj2k\FeRegister\Domain\Repository\GuestUserRepository|null
     * @TYPO3\CMS\Extbase\Annotation\Inject
     */
    protected ?GuestUserRepository $guestUserRepository = null;


    /**
     * @var \Madj2k\FeRegister\Domain\Repository\ConsentRepository|null
     * @TYPO3\CMS\Extbase\Annotation\Inject
     */
    protected ?ConsentRepository $consentRepository = null;


    /**
     * @var \TYPO3\CMS\Extbase\Persistence\Generic\PersistenceManager|null
     * @TYPO3\CMS\Extbase\Annotation\Inject
     */
    protected ?PersistenceManager $persistenceManager = null;


    /**
     * @var \Madj2k\FeRegister\Domain\Repository\OptInRepository
     */
    public function injectOptInRepository(OptInRepository $optInRepository)
    {
        $this->optInRepository = $optInRepository;
    }


    /**
     * @var \Madj2k\FeRegister\Domain\Repository\FrontendUserRepository
     */
    public function injectFrontendUserRepository(FrontendUserRepository $frontendUserRepository)
    {
        $this->frontendUserRepository = $frontendUserRepository;
    }


    /**
     * @var \Madj2k\FeRegister\Domain\Repository\GuestUserRepository
     */
    public function injectGuestUserRepository(GuestUserRepository $guestUserRepository)
    {
        $this->guestUserRepository = $guestUserRepository;
    }


    /**
     * @var \Madj2k\FeRegister\Domain\Repository\ConsentRepository
     */
    public function injectConsentRepository(ConsentRepository $consentRepository)
    {
        $this->consentRepository = $consentRepository;
    }


    /**
     * @var \TYPO3\CMS\Extbase\Persistence\Generic\PersistenceManager
     */
    public function injectPersistenceManager(PersistenceManager $persistenceManager)
    {
        $this->persistenceManager = $persistenceManager;
    }


    /**
     * @var Logger|null
     */
    protected ?Logger $logger = null;


    /**
     * Sets dryRun parameter
     *
     * @param bool $dryRun
     * @return void
     */
    public function setDryRun (bool $dryRun): void
    {
        $this->dryRun = $dryRun;
    }


    /**
     * Removes expired objects really from database
     *
     * @param int $daysSinceExpired
     * @return int
     * @throws \TYPO3\CMS\Extbase\Persistence\Exception\InvalidQueryException
     * @throws \TYPO3\CMS\Extbase\Persistence\Generic\Exception
     */
    public function removeOptIns (int $daysSinceExpired = 30): int
    {
        return $this->remove($this->optInRepository, $daysSinceExpired);
    }


    /**
     * Removes expired objects really from database
     *
     * @param int $daysSinceExpired
     * @return int
     * @throws \TYPO3\CMS\Extbase\Persistence\Exception\InvalidQueryException
     * @throws \TYPO3\CMS\Extbase\Persistence\Generic\Exception
     */
    public function removeGuestUsers (int $daysSinceExpired = 30): int
    {
        return $this->remove($this->guestUserRepository, $daysSinceExpired);
    }


    /**
     * Removes expired objects really from database
     *
     * @param int $daysSinceExpired
     * @return int
     * @throws \TYPO3\CMS\Extbase\Persistence\Exception\InvalidQueryException
     * @throws \TYPO3\CMS\Extbase\Persistence\Generic\Exception
     */
    public function removeFrontendUsers (int $daysSinceExpired = 30): int
    {
        return $this->remove($this->frontendUserRepository, $daysSinceExpired);
    }


    /**
     * Marks expired objects as deleted
     *
     * @param int $daysSinceExpired
     * @return int
     * @throws \TYPO3\CMS\Extbase\Persistence\Exception\InvalidQueryException
     * @throws \TYPO3\CMS\Extbase\Persistence\Exception\IllegalObjectTypeException
     */
    public function deleteFrontendUsers (int $daysSinceExpired = 30): int
    {
        $expiredFrontendUsers = $this->frontendUserRepository->findReadyToMarkAsDeleted($daysSinceExpired);
        $cnt = 0;

        /** @var \Madj2k\FeRegister\Domain\Model\FrontendUser $frontendUser */
        foreach ($expiredFrontendUsers as $frontendUser) {
            if (! $this->dryRun) {
                $this->frontendUserRepository->remove($frontendUser);
            }
            $cnt++;

            $this->getLogger()->log(
                LogLevel::INFO,
                sprintf(
                    (($this->dryRun) ? 'Dry-Run: ' : '' ). 'Marked id %s of object "%s" as deleted.',
                    $frontendUser->getUid(),
                    get_class($frontendUser),

                )
            );
        }

        $this->persistenceManager->persistAll();

        return $cnt;
    }


    /**
     * Removes expired objects really from database
     *
     * @param int $daysSinceExpired
     * @return int
     * @throws \TYPO3\CMS\Extbase\Persistence\Exception\InvalidQueryException
     * @throws \TYPO3\CMS\Extbase\Persistence\Generic\Exception
     */
    public function removeConsents (int $daysSinceExpired = 30): int
    {
        return $this->remove($this->consentRepository, $daysSinceExpired);
    }


    /**
     * Removes expired objects really from database
     *
     * @param \Madj2k\FeRegister\Domain\Repository\CleanerInterface $repository
     * @param int $daysSinceExpired
     * @return int
     * @throws \TYPO3\CMS\Extbase\Persistence\Exception\InvalidQueryException
     * @throws \TYPO3\CMS\Extbase\Persistence\Generic\Exception
     */
    protected function remove(CleanerInterface $repository, int $daysSinceExpired): int
    {
        $expiredOptIns = $repository->findReadyToRemove($daysSinceExpired);
        $cnt = 0;

        /** @var \TYPO3\CMS\Extbase\DomainObject\AbstractEntity $object */
        foreach ($expiredOptIns as $object) {

            if (! $this->dryRun) {
                $repository->removeHard($object);
            }
            $cnt++;

            $this->getLogger()->log(
                LogLevel::INFO,
                sprintf(
                    (($this->dryRun) ? 'Dry-Run: ' : '' ). 'Deleted id %s of object "%s".',
                    $object->getUid(),
                    get_class($object),

                )
            );
        }

        return $cnt;
    }


    /**
     * Returns logger instance
     *
     * @return \TYPO3\CMS\Core\Log\Logger
     */
    protected function getLogger(): Logger
    {

        if (!$this->logger instanceof Logger) {
            $this->logger = GeneralUtility::makeInstance(LogManager::class)->getLogger(__CLASS__);
        }

        return $this->logger;
    }

}


