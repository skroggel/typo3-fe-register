<?php
namespace Madj2k\FeRegister\Tests\Integration\Persistence;

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

use Madj2k\FeRegister\Domain\Model\OptIn;
use Madj2k\FeRegister\Persistence\Cleaner;
use Nimut\TestingFramework\TestCase\FunctionalTestCase;
use Madj2k\CoreExtended\Utility\FrontendSimulatorUtility;
use Madj2k\FeRegister\Domain\Model\FrontendUser;
use Madj2k\FeRegister\Domain\Model\GuestUser;
use Madj2k\FeRegister\Domain\Repository\FrontendUserGroupRepository;
use Madj2k\FeRegister\Domain\Repository\FrontendUserRepository;
use Madj2k\FeRegister\Domain\Repository\GuestUserRepository;
use Madj2k\FeRegister\Domain\Repository\OptInRepository;
use Madj2k\FeRegister\Domain\Repository\ConsentRepository;
use Madj2k\FeRegister\Registration\GuestUserRegistration;
use Madj2k\FeRegister\Utility\FrontendUserSessionUtility;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Mvc\Request;
use TYPO3\CMS\Extbase\Object\ObjectManager;
use TYPO3\CMS\Extbase\Persistence\Generic\PersistenceManager;
use TYPO3\CMS\Extbase\Persistence\QueryResultInterface;

/**
 * CleanerTest
 *
 * @author Steffen Krogel <developer@steffenkroggel.de>
 * @copyright Steffen Kroggel
 * @package Madj2k_FeRegister
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License, version 3 or later
 */
class CleanerTest extends FunctionalTestCase
{
    /**
     * @const
     */
    const FIXTURE_PATH = __DIR__ . '/CleanerTest/Fixtures';


    /**
     * @var string[]
     */
    protected $testExtensionsToLoad = [
        'typo3conf/ext/ajax_api',
        'typo3conf/ext/core_extended',
        'typo3conf/ext/accelerator',
        'typo3conf/ext/postmaster',
        'typo3conf/ext/fe_register'
    ];


    /**
     * @var string[]
     */
    protected $coreExtensionsToLoad = [
        'filemetadata'
    ];


    /**
     * @var \Madj2k\FeRegister\Persistence\Cleaner|null
     */
    private ?Cleaner $fixture = null;


    /**
     * @var \Madj2k\FeRegister\Domain\Repository\GuestUserRepository|null
     */
    private ?GuestUserRepository $guestUserRepository = null;


    /**
     * @var \Madj2k\FeRegister\Domain\Repository\FrontendUserGroupRepository|null
     */
    private ?FrontendUserGroupRepository $frontendUserGroupRepository = null;


    /**
     * @var \Madj2k\FeRegister\Domain\Repository\OptInRepository|null
     */
    private ?OptInRepository $optInRepository = null;


    /**
     * @var \Madj2k\FeRegister\Domain\Repository\ConsentRepository|null
     */
    private ?ConsentRepository $consentRepository = null;


    /**
     * @var \TYPO3\CMS\Extbase\Persistence\Generic\PersistenceManager|null
     */
    private ?PersistenceManager $persistenceManager = null;


    /**
     * @var \TYPO3\CMS\Extbase\Object\ObjectManager|null
     */
    private ?ObjectManager $objectManager = null;


    /**
     * Setup
     * @throws \Exception
     */
    protected function setUp(): void
    {

        parent::setUp();
        $this->importDataSet(self::FIXTURE_PATH . '/Database/Global.xml');

        $this->setUpFrontendRootPage(
            1,
            [
                'EXT:core_extended/Configuration/TypoScript/setup.txt',
                'EXT:core_extended/Configuration/TypoScript/constants.txt',
                'EXT:fe_register/Configuration/TypoScript/setup.txt',
                'EXT:fe_register/Configuration/TypoScript/constants.txt',
                self::FIXTURE_PATH . '/Frontend/Configuration/Rootpage.typoscript',
            ],
            ['example.com' => self::FIXTURE_PATH .  '/Frontend/Configuration/config.yaml']
        );

        /** @var \TYPO3\CMS\Extbase\Object\ObjectManager $objectManager */
        $this->objectManager = GeneralUtility::makeInstance(ObjectManager::class);

        /** @var \Madj2k\FeRegister\Persistence\Cleaner $fixture */
        $this->fixture = $this->objectManager->get(Cleaner::class);

        /** @var \Madj2k\FeRegister\Domain\Repository\GuestUserRepository guestUserRepository */
        $this->guestUserRepository = $this->objectManager->get(GuestUserRepository::class);

        /** @var \Madj2k\FeRegister\Domain\Repository\FrontendUserGroupRepository frontendUserGroupRepository */
        $this->frontendUserGroupRepository = $this->objectManager->get(FrontendUserGroupRepository::class);

        /** @var \Madj2k\FeRegister\Domain\Repository\ConsentRepository consentRepository */
        $this->consentRepository = $this->objectManager->get(ConsentRepository::class);

        /** @var \TYPO3\CMS\Extbase\Persistence\Generic\PersistenceManager persistenceManager */
        $this->persistenceManager = GeneralUtility::makeInstance(PersistenceManager::class);

        /** @var \Madj2k\FeRegister\Domain\Repository\OptInRepository optInRepository */
        $this->optInRepository = $this->objectManager->get(OptInRepository::class);


    }

    #==============================================================================

    /**
     * @test
     * @throws \Exception
     */
    public function removeOptInRemovesExpiredOptIns ()
    {
        /**
         * Scenario:
         *
         * Given four opt-ins
         * Given two opt-ins A and B have an endtime that dates to 31 days ago
         * Given one opt-in C has an endtime that dates to 20 days ago
         * given one opt-ins D has no endtime
         * When the method is called
         * Then an integer is returned
         * Then the integer has the value 2
         * Then the opt-ins C and D are not deleted
         */

        // Preparation
        $tableName = 'tx_feregister_domain_model_optin';
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)
            ->getQueryBuilderForTable($tableName);

        $queryBuilder
            ->insert($tableName)
            ->values([
                'endtime' => strtotime('-31 days', time())
            ])
            ->execute();

        $queryBuilder
            ->insert($tableName)
            ->values([
                'endtime' => strtotime('-31 days', time())
            ])
            ->execute();

        $queryBuilder
            ->insert($tableName)
            ->values([
                'endtime' => strtotime('-20 days', time())
            ])
            ->execute();

        $queryBuilder
            ->insert($tableName)
            ->values([
                'endtime' => 0
            ])
            ->execute();

        // Test
        $result = $this->fixture->removeOptIns(30);
        self::assertIsInt($result);
        self::assertEquals(2, $result);

        $rows = $this->getAllRowsOfTable($tableName);
        self::assertCount(2, $rows);
        self::assertEquals(3, $rows[0]['uid']);
        self::assertEquals(4, $rows[1]['uid']);
    }


    /**
     * @test
     * @throws \Exception
     */
    public function removeOptInRemovesDeletedOptIns ()
    {
        /**
         * Scenario:
         *
         * Given four deleted opt-ins
         * Given two opt-ins A and B have a tstamp that dates to 31 days ago
         * Given one opt-in C has a tstamp that dates to 20 days ago
         * Given one opt-in D has a tstamp that dates to current time
         * When the method is called
         * Then an integer is returned
         * Then the integer has the value 2
         * Then the opt-ins C and D are not deleted
         */

        // Preparation
        $tableName = 'tx_feregister_domain_model_optin';
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)
            ->getQueryBuilderForTable($tableName);

        $queryBuilder
            ->insert($tableName)
            ->values([
                'deleted' => 1,
                'tstamp' => strtotime('-31 days', time())
            ])
            ->execute();

        $queryBuilder
            ->insert($tableName)
            ->values([
                'deleted' => 1,
                'tstamp' => strtotime('-31 days', time())
            ])
            ->execute();

        $queryBuilder
            ->insert($tableName)
            ->values([
                'deleted' => 1,
                'tstamp' => strtotime('-20 days', time())
            ])
            ->execute();

        $queryBuilder
            ->insert($tableName)
            ->values([
                'deleted' => 1,
                'tstamp' => time()
            ])
            ->execute();

        // Test
        $result = $this->fixture->removeOptIns(30);
        self::assertIsInt($result);
        self::assertEquals(2, $result);

        $rows = $this->getAllRowsOfTable($tableName);
        self::assertCount(2, $rows);
        self::assertEquals(3, $rows[0]['uid']);
        self::assertEquals(4, $rows[1]['uid']);
    }

    #==============================================================================

    /**
     * @test
     * @throws \Exception
     */
    public function removeGuestUsersRemovesExpiredGuestUsers ()
    {
        /**
         * Scenario:
         *
         * Given four guestUsers
         * Given two guestUsers A and B have an endtime that dates to 31 days ago
         * Given one guestUser C has an endtime that dates to 20 days ago
         * Given one guestUser D has no endtime
         * Given one normal frontendUser
         * Given that normal frontendUser E has an endtime that dates to 31 days ago
         * When the method is called
         * Then an integer is returned
         * Then the integer has the value 2
         * Then the guestUsers C and D are not deleted
         * Then the normal frontendUser E is not deleted
         */

        // Preparation
        $tableName = 'fe_users';
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)
            ->getQueryBuilderForTable($tableName);

        $queryBuilder
            ->insert($tableName)
            ->values([
                'tx_extbase_type' => '\Madj2k\FeRegister\Domain\Model\GuestUser',
                'endtime' => strtotime('-31 days', time())
            ])
            ->execute();

        $queryBuilder
            ->insert($tableName)
            ->values([
                'tx_extbase_type' => '\Madj2k\FeRegister\Domain\Model\GuestUser',
                'endtime' => strtotime('-31 days', time())
            ])
            ->execute();

        $queryBuilder
            ->insert($tableName)
            ->values([
                'tx_extbase_type' => '\Madj2k\FeRegister\Domain\Model\GuestUser',
                'endtime' => strtotime('-20 days', time())
            ])
            ->execute();

        $queryBuilder
            ->insert($tableName)
            ->values([
                'tx_extbase_type' => '\Madj2k\FeRegister\Domain\Model\GuestUser',
                'endtime' => 0
            ])
            ->execute();

        $queryBuilder
            ->insert($tableName)
            ->values([
                'tx_extbase_type' => '0',
                'endtime' => strtotime('-31 days', time())
            ])
            ->execute();

        // Test
        $result = $this->fixture->removeGuestUsers(30);
        self::assertIsInt($result);
        self::assertEquals(2, $result);

        $rows = $this->getAllRowsOfTable($tableName);
        self::assertCount(3, $rows);
        self::assertEquals(3, $rows[0]['uid']);
        self::assertEquals('\Madj2k\FeRegister\Domain\Model\GuestUser', $rows[0]['tx_extbase_type']);

        self::assertEquals(4, $rows[1]['uid']);
        self::assertEquals('\Madj2k\FeRegister\Domain\Model\GuestUser', $rows[1]['tx_extbase_type']);

        self::assertEquals(5, $rows[2]['uid']);
        self::assertEquals('0', $rows[2]['tx_extbase_type']);

    }


    /**
     * @test
     * @throws \Exception
     */
    public function removeGuestUsersRemovesDeletedGuestUsers ()
    {
        /**
         * Scenario:
         *
         * Given four deleted guestUsers
         * Given two guestUsers A and B have a tstamp that dates to 31 days ago
         * Given one guestUser C has a tstamp that dates to 20 days ago
         * Given one guestUser D has a tstamp that dates to current time
         * Given one deleted normal frontendUser
         * Given that normal frontendUser E has a tstamp that dates to 31 days ago
         * When the method is called
         * Then an integer is returned
         * Then the integer has the value 2
         * Then the guestUsers C and D are not deleted
         * Then the normal frontendUser E is not deleted
         */

        // Preparation
        $tableName = 'fe_users';
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)
            ->getQueryBuilderForTable($tableName);

        $queryBuilder
            ->insert($tableName)
            ->values([
                'tx_extbase_type' => '\Madj2k\FeRegister\Domain\Model\GuestUser',
                'deleted' => 1,
                'tstamp' => strtotime('-31 days', time())
            ])
            ->execute();

        $queryBuilder
            ->insert($tableName)
            ->values([
                'tx_extbase_type' => '\Madj2k\FeRegister\Domain\Model\GuestUser',
                'deleted' => 1,
                'tstamp' => strtotime('-31 days', time())
            ])
            ->execute();

        $queryBuilder
            ->insert($tableName)
            ->values([
                'tx_extbase_type' => '\Madj2k\FeRegister\Domain\Model\GuestUser',
                'deleted' => 1,
                'tstamp' => strtotime('-20 days', time())
            ])
            ->execute();

        $queryBuilder
            ->insert($tableName)
            ->values([
                'tx_extbase_type' => '\Madj2k\FeRegister\Domain\Model\GuestUser',
                'deleted' => 1,
                'tstamp' => time()
            ])
            ->execute();

        $queryBuilder
            ->insert($tableName)
            ->values([
                'tx_extbase_type' => '0',
                'deleted' => 1,
                'tstamp' => strtotime('-31 days', time())
            ])
            ->execute();


        // Test
        $result = $this->fixture->removeGuestUsers(30);
        self::assertIsInt($result);
        self::assertEquals(2, $result);

        $rows = $this->getAllRowsOfTable($tableName);
        self::assertCount(3, $rows);

        self::assertEquals(3, $rows[0]['uid']);
        self::assertEquals('\Madj2k\FeRegister\Domain\Model\GuestUser', $rows[0]['tx_extbase_type']);

        self::assertEquals(4, $rows[1]['uid']);
        self::assertEquals('\Madj2k\FeRegister\Domain\Model\GuestUser', $rows[1]['tx_extbase_type']);

        self::assertEquals(5, $rows[2]['uid']);
        self::assertEquals('0', $rows[2]['tx_extbase_type']);
    }


    /**
     * @test
     * @throws \Exception
     */
    public function removeGuestUsersRemovesDisabledGuestUsers ()
    {
        /**
         * Scenario:
         *
         * Given four disabled guestUsers
         * Given two guestUsers A and B have a tstamp that dates to 31 days ago
         * Given one guestUser C has a tstamp that dates to 20 days ago
         * Given one guestUser D has a tstamp that dates to current time
         * Given one disabled normal frontendUser
         * Given that normal frontendUser E has a tstamp that dates to 31 days ago
         * When the method is called
         * Then an integer is returned
         * Then the integer has the value 2
         * Then the guestUsers C and D are not deleted
         * Then the normal frontendUser E is not deleted
         */

        // Preparation
        $tableName = 'fe_users';
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)
            ->getQueryBuilderForTable($tableName);

        $queryBuilder
            ->insert($tableName)
            ->values([
                'tx_extbase_type' => '\Madj2k\FeRegister\Domain\Model\GuestUser',
                'disable' => 1,
                'tstamp' => strtotime('-31 days', time())
            ])
            ->execute();

        $queryBuilder
            ->insert($tableName)
            ->values([
                'tx_extbase_type' => '\Madj2k\FeRegister\Domain\Model\GuestUser',
                'disable' => 1,
                'tstamp' => strtotime('-31 days', time())
            ])
            ->execute();

        $queryBuilder
            ->insert($tableName)
            ->values([
                'tx_extbase_type' => '\Madj2k\FeRegister\Domain\Model\GuestUser',
                'disable' => 1,
                'tstamp' => strtotime('-20 days', time())
            ])
            ->execute();

        $queryBuilder
            ->insert($tableName)
            ->values([
                'tx_extbase_type' => '\Madj2k\FeRegister\Domain\Model\GuestUser',
                'disable' => 1,
                'tstamp' => time()
            ])
            ->execute();

        $queryBuilder
            ->insert($tableName)
            ->values([
                'tx_extbase_type' => '0',
                'disable' => 1,
                'tstamp' => strtotime('-31 days', time())
            ])
            ->execute();


        // Test
        $result = $this->fixture->removeGuestUsers(30);
        self::assertIsInt($result);
        self::assertEquals(2, $result);

        $rows = $this->getAllRowsOfTable($tableName);
        self::assertCount(3, $rows);

        self::assertEquals(3, $rows[0]['uid']);
        self::assertEquals('\Madj2k\FeRegister\Domain\Model\GuestUser', $rows[0]['tx_extbase_type']);

        self::assertEquals(4, $rows[1]['uid']);
        self::assertEquals('\Madj2k\FeRegister\Domain\Model\GuestUser', $rows[1]['tx_extbase_type']);

        self::assertEquals(5, $rows[2]['uid']);
        self::assertEquals('0', $rows[2]['tx_extbase_type']);
    }

    #==============================================================================

    /**
     * @test
     * @throws \Exception
     */
    public function removeFrontendUsersRemovesDisabledFrontendUsers ()
    {
        /**
         * Scenario:
         *
         * Given four disabled frontendUsers
         * Given one frontendUser A has a tstamp that dates to 31 days ago and has no lastlogin timestamp
         * Given one frontendUser B has a tstamp that dates to 31 days ago and has a lastlogin timestamp > 0
         * Given one frontendUser C has a tstamp that dates to 20 days ago and has no lastlogin timestamp
         * Given one frontendUser D has a tstamp that dates to current time and has no lastlogin timestamp
         * Given one disabled guestUser
         * Given that guestUser E has a tstamp that that dates to 31 days ago and has no lastlogin timestamp
         * When the method is called
         * Then an integer is returned
         * Then the integer has the value 2
         * Then the frontendUsers B, C and D are not deleted
         * Then the guestUser E is deleted, because it is a sub-model
         */

        // Preparation
        $tableName = 'fe_users';
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)
            ->getQueryBuilderForTable($tableName);

        $queryBuilder
            ->insert($tableName)
            ->values([
                'tx_extbase_type' => '0',
                'disable' => 1,
                'lastlogin' => 0,
                'tstamp' => strtotime('-31 days', time())
            ])
            ->execute();

        $queryBuilder
            ->insert($tableName)
            ->values([
                'tx_extbase_type' => '0',
                'disable' => 1,
                'lastlogin' => 1,
                'tstamp' => strtotime('-31 days', time())
            ])
            ->execute();

        $queryBuilder
            ->insert($tableName)
            ->values([
                'tx_extbase_type' => '0',
                'disable' => 1,
                'lastlogin' => 0,
                'tstamp' => strtotime('-20 days', time())
            ])
            ->execute();

        $queryBuilder
            ->insert($tableName)
            ->values([
                'tx_extbase_type' => '\Madj2k\FeRegister\Domain\Model\GuestUser',
                'disable' => 1,
                'lastlogin' => 0,
                'tstamp' => time()
            ])
            ->execute();

        $queryBuilder
            ->insert($tableName)
            ->values([
                'tx_extbase_type' => '\Madj2k\FeRegister\Domain\Model\GuestUser',
                'disable' => 1,
                'lastlogin' => 0,
                'tstamp' => strtotime('-31 days', time())
            ])
            ->execute();


        // Test
        $result = $this->fixture->removeFrontendUsers(30);
        self::assertIsInt($result);
        self::assertEquals(2, $result);

        $rows = $this->getAllRowsOfTable($tableName);

        self::assertCount(3, $rows);

        self::assertEquals(2, $rows[0]['uid']);
        self::assertEquals('0', $rows[0]['tx_extbase_type']);

        self::assertEquals(3, $rows[1]['uid']);
        self::assertEquals('0', $rows[1]['tx_extbase_type']);

        self::assertEquals(4, $rows[2]['uid']);
        self::assertEquals('\Madj2k\FeRegister\Domain\Model\GuestUser', $rows[2]['tx_extbase_type']);

    }

    #==============================================================================

    /**
     * @test
     * @throws \Exception
     */
    public function deleteFrontendUsersMarksExiredUsersAsDeleted()
    {
        /**
         * Scenario:
         *
         * Given four frontendUsers
         * Given two frontendUsers A and B have an endtime that dates to 31 days ago
         * Given one frontendUser C has an endtime that dates to 20 days ago
         * Given one frontendUser D has no endtime
         * Given one guestUser
         * Given that guestUser E has an endtime that dates to 31 days ago
         * When the method is called
         * Then an integer is returned
         * Then the integer has the value 3
         * Then the frontendUsers A and B are marked as deleted
         * Then the frontendUsers C and D are not marked as deleted
         * Then the guestUser E is marked as deleted because it is a sub-model
         */

        // Preparation
        $tableName = 'fe_users';
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)
            ->getQueryBuilderForTable($tableName);

        $queryBuilder
            ->insert($tableName)
            ->values([
                'tx_extbase_type' => '0',
                'endtime' => strtotime('-31 days', time())
            ])
            ->execute();

        $queryBuilder
            ->insert($tableName)
            ->values([
                'tx_extbase_type' => '0',
                'endtime' => strtotime('-31 days', time())
            ])
            ->execute();

        $queryBuilder
            ->insert($tableName)
            ->values([
                'tx_extbase_type' => '0',
                'endtime' => strtotime('-20 days', time())
            ])
            ->execute();

        $queryBuilder
            ->insert($tableName)
            ->values([
                'tx_extbase_type' => '0',
                'endtime' => 0
            ])
            ->execute();

        $queryBuilder
            ->insert($tableName)
            ->values([
                'tx_extbase_type' => '\Madj2k\FeRegister\Domain\Model\GuestUser',
                'endtime' => strtotime('-31 days', time())
            ])
            ->execute();

        // Test
        $result = $this->fixture->deleteFrontendUsers(30);
        self::assertIsInt($result);
        self::assertEquals(3, $result);

        $rows = $this->getAllRowsOfTable($tableName);
        self::assertCount(5, $rows);

        self::assertEquals(1, $rows[0]['uid']);
        self::assertEquals(1, $rows[0]['deleted']);

        self::assertEquals(2, $rows[1]['uid']);
        self::assertEquals(1, $rows[1]['deleted']);

        self::assertEquals(3, $rows[2]['uid']);
        self::assertEquals(0, $rows[2]['deleted']);

        self::assertEquals(4, $rows[3]['uid']);
        self::assertEquals(0, $rows[3]['deleted']);

        self::assertEquals(5, $rows[4]['uid']);
        self::assertEquals(1, $rows[4]['deleted']);
    }


    /**
     * @test
     * @throws \Exception
     */
    public function deleteFrontendUsersMarksDisabledUsersAsDeleted ()
    {
        /**
         * Scenario:
         *
         * Given four disabled guestUsers
         * Given two guestUsers A and B have a tstamp that dates to 31 days ago
         * Given one guestUser C has a tstamp that dates to 20 days ago
         * Given one guestUser D has a tstamp that dates to current time
         * Given one disabled normal frontendUser
         * Given that normal frontendUser E has a tstamp that dates to 31 days ago
         * When the method is called
         * Then an integer is returned
         * Then the integer has the value 2
         * Then the guestUsers C and D are not deleted
         * Then the normal frontendUser E is not deleted
         */

        // Preparation
        $tableName = 'fe_users';
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)
            ->getQueryBuilderForTable($tableName);

        $queryBuilder
            ->insert($tableName)
            ->values([
                'tx_extbase_type' => '\Madj2k\FeRegister\Domain\Model\GuestUser',
                'disable' => 1,
                'tstamp' => strtotime('-31 days', time())
            ])
            ->execute();

        $queryBuilder
            ->insert($tableName)
            ->values([
                'tx_extbase_type' => '\Madj2k\FeRegister\Domain\Model\GuestUser',
                'disable' => 1,
                'tstamp' => strtotime('-31 days', time())
            ])
            ->execute();

        $queryBuilder
            ->insert($tableName)
            ->values([
                'tx_extbase_type' => '\Madj2k\FeRegister\Domain\Model\GuestUser',
                'disable' => 1,
                'tstamp' => strtotime('-20 days', time())
            ])
            ->execute();

        $queryBuilder
            ->insert($tableName)
            ->values([
                'tx_extbase_type' => '\Madj2k\FeRegister\Domain\Model\GuestUser',
                'disable' => 1,
                'tstamp' => time()
            ])
            ->execute();

        $queryBuilder
            ->insert($tableName)
            ->values([
                'tx_extbase_type' => '0',
                'disable' => 1,
                'tstamp' => strtotime('-31 days', time())
            ])
            ->execute();


        // Test
        $result = $this->fixture->removeGuestUsers(30);
        self::assertIsInt($result);
        self::assertEquals(2, $result);

        $rows = $this->getAllRowsOfTable($tableName);
        self::assertCount(3, $rows);

        self::assertEquals(3, $rows[0]['uid']);
        self::assertEquals('\Madj2k\FeRegister\Domain\Model\GuestUser', $rows[0]['tx_extbase_type']);

        self::assertEquals(4, $rows[1]['uid']);
        self::assertEquals('\Madj2k\FeRegister\Domain\Model\GuestUser', $rows[1]['tx_extbase_type']);

        self::assertEquals(5, $rows[2]['uid']);
        self::assertEquals('0', $rows[2]['tx_extbase_type']);
    }

    #==============================================================================

    /**
     * @test
     * @throws \Exception
     */
    public function removeConsentsRemovesConsentsOfDeletedFrontendUsers ()
    {
        /**
         * Scenario:
         *
         * Given six consent-objects
         * Given one consent-object A has a reference to a frontendUser that exists and has deleted=0 set
         * Given that consent-object A has a tstamp that dates to 31 days ago
         * Given one consent-object B has a reference to a frontendUser that exists but has deleted=1 set
         * Given that consent-object B has a tstamp that dates to 31 days ago
         * Given one consent-object C has a reference to a frontendUser that does not exist
         * Given that consent-object C has a tstamp that dates to 31 days ago
         * Given one consent-object D has a reference to a frontendUser that exists and has deleted=0 set
         * Given that consent-object D has a tstamp that dates to 20 days ago
         * Given one consent-object D has a reference to a frontendUser that exists but has deleted=1 set
         * Given that consent-object D has a tstamp that dates to 20 days ago
         * Given one consent-object D has a reference to a frontendUser that does not exist
         * Given that consent-object D has a tstamp that dates to 20 days ago
         * When the method is called
         * Then an integer is returned
         * Then the integer has the value 1
         * Then the consent-object C is deleted
         */

        // Preparation
        $tableName = 'tx_feregister_domain_model_consent';
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)
            ->getQueryBuilderForTable($tableName);

        $queryBuilder
            ->insert($tableName)
            ->values([
                'frontend_user' => 1,
                'tstamp' => strtotime('-31 days', time())
            ])
            ->execute();

        $queryBuilder
            ->insert($tableName)
            ->values([
                'frontend_user' => 2,
                'tstamp' => strtotime('-31 days', time())
            ])
            ->execute();

        $queryBuilder
            ->insert($tableName)
            ->values([
                'frontend_user' => 3,
                'tstamp' => strtotime('-31 days', time())
            ])
            ->execute();

        $queryBuilder
            ->insert($tableName)
            ->values([
                'frontend_user' => 1,
                'tstamp' => strtotime('-20 days', time())
            ])
            ->execute();

        $queryBuilder
            ->insert($tableName)
            ->values([
                'frontend_user' => 2,
                'tstamp' => strtotime('-20 days', time())
            ])
            ->execute();

        $queryBuilder
            ->insert($tableName)
            ->values([
                'frontend_user' => 3,
                'tstamp' => strtotime('-20 days', time())
            ])
            ->execute();


        $tableName = 'fe_users';
        $queryBuilder
            ->insert($tableName)
            ->values([
                'tx_extbase_type' => '0',
                'deleted' => 0,
            ])
            ->execute();

        $queryBuilder
            ->insert($tableName)
            ->values([
                'tx_extbase_type' => '0',
                'deleted' => 1,
            ])
            ->execute();


        // Test
        $result = $this->fixture->removeConsents(30);
        self::assertIsInt($result);
        self::assertEquals(1, $result);

        $tableName = 'tx_feregister_domain_model_consent';
        $rows = $this->getAllRowsOfTable($tableName);

        self::assertCount(5, $rows);

        self::assertEquals(1, $rows[0]['uid']);
        self::assertEquals(2, $rows[1]['uid']);
        self::assertEquals(4, $rows[2]['uid']);
        self::assertEquals(5, $rows[3]['uid']);
        self::assertEquals(6, $rows[4]['uid']);

    }

    #==============================================================================
    /**
     * TearDown
     */
    protected function teardown(): void
    {
        parent::tearDown();
    }


    #==============================================================================

    /**
     * @param string $tableName
     * @returns array
     */
    protected function getAllRowsOfTable (string $tableName): array
    {

        $connectionPool = \Madj2k\CoreExtended\Utility\GeneralUtility::makeInstance(ConnectionPool::class);
        $queryBuilder = $connectionPool->getQueryBuilderForTable($tableName);
        $queryBuilder
            ->getRestrictions()
            ->removeAll();

        $statement = $queryBuilder
            ->select('*')
            ->from($tableName)
            ->execute();

        $rows = [];
        while ($row = $statement->fetch()) {
            $rows[] = $row;
        }

        return $rows;
    }

}
