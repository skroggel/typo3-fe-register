<?php
namespace Madj2k\FeRegister\Tests\Integration\Registration;

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
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Object\ObjectManager;
use TYPO3\CMS\Extbase\Persistence\Generic\PersistenceManager;

/**
 * GuestUserRegisterTest
 *
 * @author Steffen Krogel <developer@steffenkroggel.de>
 * @author Maximilian Fäßler <maximilian@faesslerweb.de>
 * @copyright Steffen Kroggel
 * @package Madj2k_FeRegister
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License, version 3 or later
 */
class GuestUserRegistrationTest extends FunctionalTestCase
{
    /**
     * @const
     */
    const FIXTURE_PATH = __DIR__ . '/GuestUserRegistrationTest/Fixtures';


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
     * @var \TYPO3\CMS\Extbase\Object\ObjectManager|null
     */
    private ?ObjectManager $objectManager = null;


    /**
     * @var \Madj2k\FeRegister\Registration\GuestUserRegistration|null
     */
    private ?GuestUserRegistration $fixture = null;


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

        /** \Madj2k\FeRegister\Register\FrontendUser\GuestUserRegistration $fixture */
        $this->fixture = $this->objectManager->get(GuestUserRegistration::class);

        /** @var \Madj2k\FeRegister\Domain\Repository\GuestUserRepository guestUserRepository */
        $this->guestUserRepository = $this->objectManager->get(GuestUserRepository::class);

        /** @var \Madj2k\FeRegister\Domain\Repository\FrontendUserGroupRepository frontendUserGroupRepository */
        $this->frontendUserGroupRepository = $this->objectManager->get(FrontendUserGroupRepository::class);

        /** @var \Madj2k\FeRegister\Domain\Repository\ConsentRepository consentRepository */
        $this->consentRepository = $this->objectManager->get(ConsentRepository::class);

        /** @var \Madj2k\FeRegister\Domain\Repository\OptInRepository optInRepository */
        $this->optInRepository = $this->objectManager->get(OptInRepository::class);

        FrontendSimulatorUtility::simulateFrontendEnvironment(1, 2);

    }

    #==============================================================================

    /**
     * @test
     * @throws \Exception
     */
    public function getContextAwareFrontendUserRepositoryReturnsGuestUserRepository ()
    {
        /**
         * Scenario:
         *
         * Given a guestUser-object of type \Madj2k\FeRegister\Domain\Model\GuestUser
         * Given this object has a valid value for the email-property set
         * Given setFrontendUser has been called before
         * When the method is called
         * Then an instance of \Madj2k\FeRegister\Domain\Repository\GuestUserRepository is returned
         */

        /** @var \Madj2k\FeRegister\Domain\Model\GuestUser $guestUser */
        $guestUser = GeneralUtility::makeInstance(GuestUser::class);
        $guestUser->setEmail('test@test.de');
        $this->fixture->setFrontendUser($guestUser);

        self::assertInstanceOf(GuestUserRepository::class, $this->fixture->getContextAwareFrontendUserRepository());
    }

    #==============================================================================

    /**
     * @test
     */
    public function constructSetsFrontendUser ()
    {
        /**
         * Scenario:
         *
         * When the class is instantiated
         * Then getFrontendUser returns an instance of \Madj2k\FeRegister\Domain\Model\GuestUser
         */

        self::assertInstanceOf(GuestUser::class, $this->fixture->getFrontendUser());
    }

    #==============================================================================

    /**
     * @test
     * @throws \Exception
     */
    public function setFrontendUserThrowsExceptionOnNormalUser ()
    {
        /**
         * Scenario:
         *
         * Given a frontendUser-object
         * Given the frontendUser is an instance \Madj2k\FeRegister\Domain\Model\FrontendUser
         * Given this object has a random username set
         * When the method is called
         * Then the exception is an instance of \Madj2k\FeRegister\Exception
         * Then the exception has the code 1407312134
         */
        static::expectException(\Madj2k\FeRegister\Exception::class);
        static::expectExceptionCode(1678359847);

        /** @var \Madj2k\FeRegister\Domain\Model\FrontendUser $frontendUser */
        $frontendUser = GeneralUtility::makeInstance(FrontendUser::class);
        $frontendUser->setUsername('RandomValue');

        $this->fixture->setFrontendUser($frontendUser);
    }


    /**
     * @test
     * @throws \Exception
     */
    public function setFrontendUserUsesRandomStringAsUsername()
    {
        /**
         * Scenario:
         *
         * Given a guestUser-object
         * Given this object has a valid value for the email-property set
         * Given this object has a valid value for the username-property set
         * When the method is called
         * Then the username-property of the guestUser-object is set to a random string
         * Then this random string is no email-address
         * Then this random string is unique in 10.000 calls
         * Then the email-property of the guestUser-object is empty
         */

        /** @var \Madj2k\FeRegister\Domain\Model\GuestUser $guestUser */
        $guestUser = GeneralUtility::makeInstance(GuestUser::class);
        $guestUser->setEmail('test@test.de');
        $guestUser->setUsername('tester@test.de');

        $this->fixture->setFrontendUser($guestUser);

        self::assertNotEquals('test@test.de', $guestUser->getUsername());
        self::assertStringNotContainsString('@', $guestUser->getUsername());

        $arrayOfNames = [];
        for ($i = 1; $i <= 10000; $i++) {
            $guestUser = GeneralUtility::makeInstance(GuestUser::class);
            $this->fixture->setFrontendUser($guestUser);
            $username = $guestUser->getUsername();

            self::assertNotContains($username, $arrayOfNames);
            $arrayOfNames[] = $guestUser->getUsername();
        }
        self::assertEmpty($guestUser->getEmail());

    }


    /**
     * @test
     * @throws \Exception
     */
    public function setFrontendUserSetsLanguageKey ()
    {
        /**
         * Scenario:
         *
         * Given is guestUser-object
         * Given this object has no value for the languageKey-property set
         * When the method is called
         * Then the languageKey-property of the guestUser-object is set to the current site-language
         */

        /** @var \Madj2k\FeRegister\Domain\Model\GuestUser $guestUser */
        $guestUser = GeneralUtility::makeInstance(GuestUser::class);

        $this->fixture->setFrontendUser($guestUser);
        self::assertEquals('ru', $guestUser->getTxFeregisterLanguageKey());

    }


    /**
     * @test
     * @throws \Exception
     */
    public function setFrontendUserKeepsLanguageKey ()
    {
        /**
         * Scenario:
         *
         * Given is guestUser-object
         * Given this object has a value for the LanguageKey-property set
         * When the method is called
         * Then the LanguageKey-property of the guestUser-object is not changed
         */

        /** @var \Madj2k\FeRegister\Domain\Model\GuestUser $guestUser */
        $guestUser = GeneralUtility::makeInstance(GuestUser::class);
        $guestUser->setTxFeregisterLanguageKey('it');

        $this->fixture->setFrontendUser($guestUser);
        self::assertEquals('it', $guestUser->getTxFeregisterLanguageKey());
    }


    /**
     * @test
     * @throws \Exception
     */
    public function setFrontendUserSetsCrdateOnNewObject ()
    {
        /**
         * Scenario:
         *
         * Given a non-persisted guestUser-object
         * Given this object has a value for the crdate-property set
         * When the method is called
         * Then the crdate-property of the guestUser-object is set to the current time
         */

        /** @var \Madj2k\FeRegister\Domain\Model\GuestUser $guestUser */
        $guestUser = GeneralUtility::makeInstance(GuestUser::class);
        $guestUser->setCrdate(10);

        $this->fixture->setFrontendUser($guestUser);
        self::assertGreaterThan(time() -5, $guestUser->getCrdate());

    }


    /**
     * @test
     * @throws \Exception
     */
    public function setFrontendUserKeepsCrdateOnExistingObject ()
    {
        /**
         * Scenario:
         *
         * Given is guestUser-object
         * Given this object has a value for the crdate-property set
         * When the method is called
         * Then the crdate-property of the guestUser-object is not changed
         */

        $this->importDataSet(self::FIXTURE_PATH . '/Database/Check10.xml');

        /** @var \Madj2k\FeRegister\Domain\Model\GuestUser $guestUser */
        $guestUser = $this->guestUserRepository->findByIdentifier(10);

        $this->fixture->setFrontendUser($guestUser);
        self::assertEquals(10, $guestUser->getCrdate());

    }


    /**
     * @test
     * @throws \Exception
     */
    public function setFrontendUserSetsDisableOnNewObject ()
    {
        /**
         * Scenario:
         *
         * Given a non-persisted guestUser-object
         * Given this object has a value for the disable-property set
         * When the method is called
         * Then the disable-property is set to value one
         */

        /** @var \Madj2k\FeRegister\Domain\Model\GuestUser $guestUser */
        $guestUser = GeneralUtility::makeInstance(GuestUser::class);
        $guestUser->setDisable(0);

        $this->fixture->setFrontendUser($guestUser);
        self::assertEquals(1, $guestUser->getDisable());

    }


    /**
     * @test
     * @throws \Exception
     */
    public function setFrontendUserKeepsDisabledOnExistingObject ()
    {
        /**
         * Scenario:
         *
         * Given a persisted guestUser-object
         * Given this object has a value for the disable-property set
         * When the method is called
         * Then the disable-property of the guestUser-object is not changed
         */

        $this->importDataSet(self::FIXTURE_PATH . '/Database/Check10.xml');

        /** @var \Madj2k\FeRegister\Domain\Model\GuestUser $guestUser */
        $guestUser = $this->guestUserRepository->findByIdentifier(10);

        $this->fixture->setFrontendUser($guestUser);
        self::assertEquals(0, $guestUser->getDisable());

    }


    /**
     * @test
     * @throws \Exception
     */
    public function setFrontendUserSetsRegisterRemoteIpOnNewObject ()
    {
        /**
         * Scenario:
         *
         * Given a non-persisted guestUser-object
         * Given this object has a valid value for the email-property set
         * Given this object has a value for the registerRemoteIp-property set
         * When the method is called
         * Then the registerRemoteIp-property of the guestUser-object is set to the current IP
         */

        /** @var \Madj2k\FeRegister\Domain\Model\GuestUser $guestUser */
        $guestUser = GeneralUtility::makeInstance(GuestUser::class);
        $guestUser->setEmail('test@test.de');
        $guestUser->setTxFeregisterRegisterRemoteIp('10');

        $this->fixture->setFrontendUser($guestUser);
        self::assertEquals('127.0.0.1', $guestUser->getTxFeregisterRegisterRemoteIp());

    }


    /**
     * @test
     * @throws \Exception
     */
    public function setFrontendUserKeepsRegisterRemoteIpOnExistingObject ()
    {
        /**
         * Scenario:
         *
         * Given a persisted guestUser-object
         * Given this object has a value for the registerRemoteIp-property set
         * When the method is called
         * Then the registerRemoteIp-property of the guestUser-object is not changed
         */

        $this->importDataSet(self::FIXTURE_PATH .'/Database/Check10.xml');

        /** @var \Madj2k\FeRegister\Domain\Model\GuestUser $guestUser */
        $guestUser = $this->guestUserRepository->findByIdentifier(10);

        $this->fixture->setFrontendUser($guestUser);
        self::assertEquals('1.2.3.4', $guestUser->getTxFeregisterRegisterRemoteIp());

    }


    /**
     * @test
     * @throws \Exception
     */
    public function setFrontendUserSetsEndtimeOnNewObject ()
    {
        /**
         * Scenario:
         *
         * Given a non-persisted guestUser-object
         * Given this object has a value for the endtime-property set
         * When the method is called
         * Then the endtime-property of the guestUser-object is set to the value from the typoscript-configuration
         */

        /** @var \Madj2k\FeRegister\Domain\Model\GuestUser $guestUser */
        $guestUser = GeneralUtility::makeInstance(GuestUser::class);
        $guestUser->setEndtime(10);

        $this->fixture->setFrontendUser($guestUser);
        self::assertGreaterThanOrEqual(time() + (intval(7 * 24 * 60 * 60) -5), $guestUser->getEndtime());

    }


    /**
     * @test
     * @throws \Exception
     */
    public function setFrontendUserKeepsEndtimeOnExistingObject ()
    {
        /**
         * Scenario:
         *
         * Given a persisted guestUser-object
         * Given this object has a value for the endtime-property set
         * When the method is called
         * Then the endtime-property of the guestUser-object is not changed
         */

        $this->importDataSet(self::FIXTURE_PATH .'/Database/Check10.xml');

        /** @var \Madj2k\FeRegister\Domain\Model\GuestUser $guestUser */
        $guestUser = $this->guestUserRepository->findByIdentifier(10);
        $time = time();
        $guestUser->setEndtime($time);

        $this->fixture->setFrontendUser($guestUser);
        self::assertEquals($time, $guestUser->getEndtime());

    }


    /**
     * @test
     * @throws \Exception
     */
    public function setFrontendUserResetsFrontendUserPersisted ()
    {
        /**
         * Scenario:
         *
         * Given a persisted guestUser-object
         * Given the method has been called before with this persisted guestUser-object
         * Given a non-persisted guestUser-object
         * When the method is called with that non-persisted guestUser-object as parameter
         * Then getFrontendUserPersisted returns null
         */
        $this->importDataSet(self::FIXTURE_PATH .'/Database/Check10.xml');

        /** @var \Madj2k\FeRegister\Domain\Model\GuestUser $guestUser */
        $guestUser = $this->guestUserRepository->findByIdentifier(10);

        $this->fixture->setFrontendUser($guestUser);
        self::assertInstanceOf(GuestUser::class, $this->fixture->getFrontendUserPersisted());

        $guestUser = GeneralUtility::makeInstance(GuestUser::class);

        $this->fixture->setFrontendUser($guestUser);
        self::assertNull($this->fixture->getFrontendUserPersisted());

    }


    /**
     * @test
     * @throws \Exception
     */
    public function setFrontendUserResetsFrontendUserToken ()
    {
        /**
         * Scenario:
         *
         * Given a persisted guestUser-object
         * Given setFrontendUserToken has been called before with a random token
         * When the method is called
         * Then getFrontendUserToken returns null
         */
        $this->importDataSet(self::FIXTURE_PATH .'/Database/Check10.xml');

        /** @var \Madj2k\FeRegister\Domain\Model\GuestUser $guestUser */
        $guestUser = $this->guestUserRepository->findByIdentifier(10);

        $this->fixture->setFrontendUserToken('test');
        $this->fixture->setFrontendUser($guestUser);

        self::assertEmpty($this->fixture->getFrontendUserToken());

    }


    /**
     * @test
     * @throws \Exception
     */
    public function setFrontendUserResetsOptInPersisted ()
    {
        /**
         * Scenario:
         *
         * Given a persisted guestUser-object
         * Given a non-persisted guestUser-object
         * Given a persisted optIn-object
         * Given the frontendUserUid-property of this object refers to the guestUser-object
         * Given this object has a valid token set
         * Given setFrontendUserToken has been called with that token before
         * Given getOptInPersisted was called before
         * Given getOptInPersisted returned the corresponding optIn-object
         * When the method is called with the non-persisted guestUser-object as parameter
         * Then getOptInPersisted returns null
         */
        $this->importDataSet(self::FIXTURE_PATH .'/Database/Check20.xml');

        $this->fixture->setFrontendUserToken('test');

        /** @var \Madj2k\FeRegister\Domain\Model\OptIn $result */
        $result = $this->fixture->getOptInPersisted();

        /** @var \Madj2k\FeRegister\Domain\Model\OptIn $OptIn */
        $optIn = $this->optInRepository->findByIdentifier(20);
        self::assertEquals($optIn, $result);

        $guestUser = GeneralUtility::makeInstance(GuestUser::class);

        $this->fixture->setFrontendUser($guestUser);
        self::assertNull($this->fixture->getOptInPersisted());

    }

    #==============================================================================

    /**
     * @test
     * @throws \Exception
     */
    public function startRegistrationThrowsNoExceptionIfNoFrontendUserSet ()
    {
        /**
         * Scenario:
         *
         * Given no guestUser-object
         * When the method is called
         * Then boolean is returned
         */;

        self::assertIsBool($this->fixture->startRegistration());

    }


    /**
     * @test
     * @throws \Exception
     */
    public function startRegistrationThrowsExceptionIfUserLoggedIn ()
    {
        /**
         * Scenario:
         *
         * Given a persisted guestUser-object
         * Given this guestUser is logged in
         * When the method is called
         * Then the exception is an instance of \Madj2k\FeRegister\Exception
         * Then the exception has the code 1661332376
         */
        static::expectException(\Madj2k\FeRegister\Exception::class);
        static::expectExceptionCode(1661332376);

        $this->importDataSet(self::FIXTURE_PATH .'/Database/Check30.xml');

        /** @var \Madj2k\FeRegister\Domain\Model\FrontendUser $frontendUser */
        $frontendUser = $this->guestUserRepository->findByIdentifier(30);
        $frontendUserGroup = $this->frontendUserGroupRepository->findByIdentifier(30);

        FrontendSimulatorUtility::simulateFrontendEnvironment(1);
        FrontendUserSessionUtility::simulateLogin($frontendUser, $frontendUserGroup);

        $this->fixture->setFrontendUser($frontendUser);
        $this->fixture->startRegistration();

        FrontendSimulatorUtility::resetFrontendEnvironment();

    }


    /**
     * @test
     * @throws \Exception
     */
    public function startRegistrationReturnsFalseIfUserNotNew ()
    {
        /**
         * Scenario:
         *
         * Given a persisted guestUser-object
         * When the method is called
         * Then false is returned
         */

        $this->importDataSet(self::FIXTURE_PATH .'/Database/Check10.xml');

        /** @var \Madj2k\FeRegister\Domain\Model\FrontendUser $frontendUser */
        $frontendUser = $this->guestUserRepository->findByIdentifier(10);
        $this->fixture->setFrontendUser($frontendUser);

        self::assertFalse($this->fixture->startRegistration());

    }


    /**
     * @test
     * @throws \Exception
     */
    public function startRegistrationReturnsTrueIfUserNew ()
    {
        /**
         * Scenario:
         *
         * Given a non-persisted guestUser-object
         * When the method is called
         * Then true is returned
         * Then the guestUser is persisted
         * Then the disable-property is set to true
         * Then the password property is set
         * Then the tempPlaintextPassword-property is not set
         * Then no consent-object is created
         */

        /** @var \Madj2k\FeRegister\Domain\Model\GuestUser $guestUser */
        $guestUser = GeneralUtility::makeInstance(GuestUser::class);

        $this->fixture->setFrontendUser($guestUser);
        self::assertTrue($this->fixture->startRegistration());

        /** @var \Madj2k\FeRegister\Domain\Model\FrontendUser $frontendUserDatabase */
        $guestUserDatabase = $this->guestUserRepository->findByIdentifier(1);

        self::assertTrue($guestUserDatabase->getDisable());
        self::assertNotEmpty($guestUserDatabase->getPassword());
        self::assertEmpty($guestUserDatabase->getTempPlaintextPassword());
        self::assertEquals(0, $this->consentRepository->countAll());

    }

    #==============================================================================

    /**
     * @test
     * @throws \Exception
     */
    public function endRegistrationThrowsExceptionIfNoFrontendUserPersisted ()
    {


        /**
         * Scenario:
         *
         * Given a non-persisted guestUser-object
         * Given setFrontendUser has been called with this frontendUser-object as parameter
         * When the method is called
         * Then the exception is an instance of \Madj2k\FeRegister\Exception
         * Then the exception has the code 1661163918
         */
        static::expectException(\Madj2k\FeRegister\Exception::class);
        static::expectExceptionCode(1661163918);

        $frontendUser = GeneralUtility::makeInstance(GuestUser::class);

        $this->fixture->setFrontendUser($frontendUser);
        $this->fixture->endRegistration();

    }


    /**
     * @test
     * @throws \Exception
     */
    public function endRegistrationReturnsTrueAndPerformsLogoutForLoggedInUser ()
    {

        /**
         * Scenario:
         *
         * Given a persisted guestUser-object
         * Given this guestUser-object is not disabled
         * Given a persisted userGroup
         * Given simulateLogin has been called with both as parameters before
         * Given simulateLogin has returned true
         * When the method is called
         * Then true is returned
         * Then the guestUser is logged out
         */

        $this->importDataSet(self::FIXTURE_PATH . '/Database/Check30.xml');

        /** @var \Madj2k\FeRegister\Domain\Model\GuestUser $guestUser */
        $guestUser = $this->guestUserRepository->findByUid(30);

        /** @var \Madj2k\FeRegister\Domain\Model\FrontendUserGroup $frontendUserGroup */
        $frontendUserGroup = $this->frontendUserGroupRepository->findByUid(30);

        FrontendSimulatorUtility::simulateFrontendEnvironment(1);
        self::assertTrue(FrontendUserSessionUtility::simulateLogin($guestUser, $frontendUserGroup));
        $this->fixture->setFrontendUser($guestUser);

        self::assertTrue($this->fixture->endRegistration());
        self::assertFalse(FrontendUserSessionUtility::isUserLoggedIn($guestUser));

        FrontendSimulatorUtility::resetFrontendEnvironment();

    }


    /**
     * @test
     * @throws \Exception
     */
    public function endRegistrationReturnsTrueMarksFrontendUserAsDeleted ()
    {

        /**
         * Scenario:
         *
         * Given a persisted guestUser-object
         * Given this guestUser-object is not disabled
         * Given a persisted userGroup
         * When the method is called
         * Then true is returned
         * Then the frontendUser is marked as deleted
         */

        $this->importDataSet(self::FIXTURE_PATH . '/Database/Check10.xml');

        /** @var \Madj2k\FeRegister\Domain\Model\GuestUser $frontendUser */
        $guestUser = $this->guestUserRepository->findByUid(10);

        $this->fixture->setFrontendUser($guestUser);
        self::assertTrue($this->fixture->endRegistration());

        $persistenceManager = $this->objectManager->get(PersistenceManager::class);
        $persistenceManager->clearState();

        /** @var \Madj2k\FeRegister\Domain\Model\GuestUser $guestUser */
        $guestUser = $this->guestUserRepository->findByIdentifierIncludingDeleted(10);
        self::assertTrue($guestUser->getDeleted());

    }


    /**
     * @test
     * @throws \Exception
     */
    public function endRegistrationReturnsFalseOnDisabledUser ()
    {

        /**
         * Scenario:
         *
         * Given a persisted guestUser-object
         * Given this guestUser-object is disabled
         * Given a persisted userGroup
         * When the method is called
         * Then false is returned
         * Then the frontendUser is not marked as deleted
         */

        $this->importDataSet(self::FIXTURE_PATH . '/Database/Check40.xml');

        /** @var \Madj2k\FeRegister\Domain\Model\GuestUser $guestUser */
        $guestUser = $this->guestUserRepository->findByIdentifierIncludingDisabled(40);

        $this->fixture->setFrontendUser($guestUser);
        self::assertFalse($this->fixture->endRegistration());

        $persistenceManager = $this->objectManager->get(PersistenceManager::class);
        $persistenceManager->clearState();

        /** @var \Madj2k\FeRegister\Domain\Model\GuestUser $guestUser */
        $guestUser = $this->guestUserRepository->findByIdentifierIncludingDeleted(40);
        self::assertFalse($guestUser->getDeleted());

    }


    /**
     * @test
     * @throws \Exception
     */
    public function endRegistrationReturnsTrueAndUpdatesRawFrontendUser ()
    {
        /**
         * Scenario:
         *
         * Given a persisted guestUser-object
         * Given that guestUser-object is not disabled
         * Given the email-address of the object has been changed during object-lifetime without persisting
         * Given this object has a valid value for the email-property set
         * Given setFrontendUser has been called with this guestUser-object as parameter
         * When the method is called
         * Then true is returned
         * Then the guestUser-object is marked as deleted
         * Then getFrontendUser returns the same object as getFrontendUserPersisted
         * Then the changed email-address is dismissed
         */
        $this->importDataSet(self::FIXTURE_PATH . '/Database/Check10.xml');

        /** @var \Madj2k\FeRegister\Domain\Model\GuestUser $guestUser */
        $guestUser = $this->guestUserRepository->findByUid(10);
        $guestUser->setEmail('merz@cdu.de');

        $this->fixture->setFrontendUser($guestUser);
        self::assertTrue($this->fixture->endRegistration());

        $persistenceManager = $this->objectManager->get(PersistenceManager::class);
        $persistenceManager->clearState();

        /** @var \Madj2k\FeRegister\Domain\Model\GuestUser $guestUser */
        $guestUser = $this->guestUserRepository->findByIdentifierIncludingDeleted(10);
        self::assertTrue($guestUser->getDeleted());

        $guestUserPersisted = $this->fixture->getFrontendUserPersisted();
        $frontendUserRaw = $this->fixture->getFrontendUser();

        self::assertSame($guestUserPersisted, $frontendUserRaw);
    }

    #==============================================================================

    /**
     * TearDown
     */
    protected function teardown(): void
    {
        parent::tearDown();
    }

}
