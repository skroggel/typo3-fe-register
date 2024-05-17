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
 *
 */

use Madj2k\FeRegister\Domain\Repository\GuestUserRepository;
use Madj2k\FeRegister\Domain\Repository\ShippingAddressRepository;
use Madj2k\FeRegister\Domain\Repository\TitleRepository;
use Nimut\TestingFramework\TestCase\FunctionalTestCase;
use Madj2k\CoreExtended\Utility\FrontendSimulatorUtility;
use Madj2k\FeRegister\Domain\Model\FrontendUser;
use Madj2k\FeRegister\Domain\Model\GuestUser;
use Madj2k\FeRegister\Domain\Model\OptIn;
use Madj2k\FeRegister\Domain\Repository\BackendUserRepository;
use Madj2k\FeRegister\Domain\Repository\FrontendUserGroupRepository;
use Madj2k\FeRegister\Domain\Repository\FrontendUserRepository;
use Madj2k\FeRegister\Domain\Repository\OptInRepository;
use Madj2k\FeRegister\Domain\Repository\ConsentRepository;
use Madj2k\FeRegister\Registration\FrontendUserRegistration;
use Madj2k\FeRegister\Utility\FrontendUserSessionUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Mvc\Request;
use TYPO3\CMS\Extbase\Object\ObjectManager;
use TYPO3\CMS\Extbase\Persistence\Generic\PersistenceManager;

/**
 * FrontendUserRegisterTest
 *
 * @author Steffen Krogel <developer@steffenkroggel.de>
 * @author Maximilian Fäßler <maximilian@faesslerweb.de>
 * @copyright Steffen Kroggel
 * @package Madj2k_FeRegister
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License, version 3 or later
 */
class FrontendUserRegistrationTest extends FunctionalTestCase
{
    /**
     * @const
     */
    const FIXTURE_PATH = __DIR__ . '/FrontendUserRegistrationTest/Fixtures';


    /**
     * @var string[]
     */
    protected $testExtensionsToLoad = [
        'typo3conf/ext/ajax_api',
        'typo3conf/ext/core_extended',
        'typo3conf/ext/accelerator',
        'typo3conf/ext/postmaster',
        'typo3conf/ext/fe_register',
    ];


    /**
     * @var string[]
     */
    protected $coreExtensionsToLoad = [
        'filemetadata'
    ];


    /**
     * @var \Madj2k\FeRegister\Domain\Repository\FrontendUserRepository|null
     */
    private ?FrontendUserRepository $frontendUserRepository;


    /**
     * @var \Madj2k\FeRegister\Domain\Repository\GuestUserRepository|null
     */
    private ?GuestUserRepository $guestUserRepository;


    /**
     * @var \Madj2k\FeRegister\Domain\Repository\FrontendUserGroupRepository|null
     */
    private ?FrontendUserGroupRepository $frontendUserGroupRepository;


    /**
     * @var \Madj2k\FeRegister\Domain\Repository\BackendUserRepository|null
     */
    private ?BackendUserRepository $backendUserRepository;


    /**
     * @var \Madj2k\FeRegister\Domain\Repository\OptInRepository|null
     */
    private ?OptInRepository $optInRepository;


    /**
     * @var \Madj2k\FeRegister\Domain\Repository\ConsentRepository|null
     */
    private ?ConsentRepository $consentRepository;


    /**
     * @var \Madj2k\FeRegister\Domain\Repository\ShippingAddressRepository|null
     */
    private ?ShippingAddressRepository $shippingAddressRepository = null;


    /**
     * @var \Madj2k\FeRegister\Domain\Repository\TitleRepository|null
     */
    private ?TitleRepository $titleRepository = null;


    /**
     * @var \TYPO3\CMS\Extbase\Object\ObjectManager|null
     */
    private ?ObjectManager $objectManager;


    /**
     * @var \Madj2k\FeRegister\Registration\FrontendUserRegistration|null
     */
    private ?FrontendUserRegistration $fixture;


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
                'EXT:core_extended/Configuration/TypoScript/setup.typoscript',
                'EXT:core_extended/Configuration/TypoScript/constants.typoscript',
                'EXT:fe_register/Configuration/TypoScript/setup.typoscript',
                'EXT:fe_register/Configuration/TypoScript/constants.typoscript',
                self::FIXTURE_PATH . '/Frontend/Configuration/Rootpage.typoscript',
            ],
            ['example.com' => self::FIXTURE_PATH .  '/Frontend/Configuration/config.yaml']
        );

        /** @var \TYPO3\CMS\Extbase\Object\ObjectManager $objectManager */
        $this->objectManager = GeneralUtility::makeInstance(ObjectManager::class);

        /** \Madj2k\FeRegister\Register\FrontendUser\FrontendUserRegistration $fixture */
        $this->fixture = $this->objectManager->get(FrontendUserRegistration::class);

        /** @var \Madj2k\FeRegister\Domain\Repository\FrontendUserRepository frontendUserRepository */
        $this->frontendUserRepository = $this->objectManager->get(FrontendUserRepository::class);

        /** @var \Madj2k\FeRegister\Domain\Repository\GuestUserRepository guestUserRepository */
        $this->guestUserRepository = $this->objectManager->get(GuestUserRepository::class);

        /** @var \Madj2k\FeRegister\Domain\Repository\FrontendUserGroupRepository frontendUserGroupRepository */
        $this->frontendUserGroupRepository = $this->objectManager->get(FrontendUserGroupRepository::class);

        /** @var \Madj2k\FeRegister\Domain\Repository\BackendUserRepository backendUserRepository */
        $this->backendUserRepository = $this->objectManager->get(BackendUserRepository::class);

        /** @var \Madj2k\FeRegister\Domain\Repository\OptInRepository optInRepository */
        $this->optInRepository = $this->objectManager->get(OptInRepository::class);

        /** @var \Madj2k\FeRegister\Domain\Repository\ConsentRepository consentRepository */
        $this->consentRepository = $this->objectManager->get(ConsentRepository::class);

        /** @var \Madj2k\FeRegister\Domain\Repository\ConsentRepository shippingAddressRepository */
        $this->shippingAddressRepository = $this->objectManager->get(ShippingAddressRepository::class);

        /** @var \Madj2k\FeRegister\Domain\Repository\ConsentRepository titleRepository */
        $this->titleRepository = $this->objectManager->get(TitleRepository::class);

        $GLOBALS['TYPO3_CONF_VARS']['MAIL']['defaultMailFromAddress'] = 'mail@example.com';
        $GLOBALS['TYPO3_CONF_VARS']['MAIL']['defaultMailFromName'] = 'Default';

        FrontendSimulatorUtility::simulateFrontendEnvironment(1, 2);

    }

    #==============================================================================
    /**
     * @test
     * @throws \Exception
     */
    public function getContextAwareFrontendUserRepositoryReturnsFrontendUserRepository ()
    {
        /**
         * Scenario:
         *
         * Given a frontendUser-object of type \Madj2k\FeRegister\Domain\Model\FrontendUser
         * Given this object has a valid value for the email-property set
         * Given setFrontendUser has been called before
         * When the method is called
         * Then an instance of \Madj2k\FeRegister\Domain\Repository\FrontendUserRepository is returned
         */

        /** @var \Madj2k\FeRegister\Domain\Model\FrontendUser $frontendUser */
        $frontendUser = GeneralUtility::makeInstance(FrontendUser::class);
        $frontendUser->setEmail('test@test.de');
        $this->fixture->setFrontendUser($frontendUser);

        self::assertInstanceOf(FrontendUserRepository::class, $this->fixture->getContextAwareFrontendUserRepository());
    }

    #==============================================================================

    /**
     * @test
     * @throws \Exception
     */
    public function setFrontendUserThrowsExceptionOnInvalidEmail ()
    {
        /**
         * Scenario:
         *
         * Given a frontendUser-object
         * Given this object has an invalid value for the email-property set
         * When the method is called
         * Then the exception is an instance of \Madj2k\FeRegister\Exception
         * Then the exception has the code 1407312133)
         */
        static::expectException(\Madj2k\FeRegister\Exception::class);
        static::expectExceptionCode(1407312133);

        /** @var \Madj2k\FeRegister\Domain\Model\FrontendUser $frontendUser */
        $frontendUser = GeneralUtility::makeInstance(FrontendUser::class);
        $frontendUser->setEmail('TEST');

        $this->fixture->setFrontendUser($frontendUser);
    }


    /**
     * @test
     * @throws \Exception
     */
    public function setFrontendUserThrowsExceptionOnInvalidUsername ()
    {
        /**
         * Scenario:
         *
         * Given a frontendUser-object
         * Given this object has a valid value for the email-property set
         * Given this object has an invalid value for the username-property set
         * When the method is called
         * Then the exception is an instance of \Madj2k\FeRegister\Exception
         * Then the exception has the code 1407312134
         */
        static::expectException(\Madj2k\FeRegister\Exception::class);
        static::expectExceptionCode(1407312134);

        /** @var \Madj2k\FeRegister\Domain\Model\FrontendUser $frontendUser */
        $frontendUser = GeneralUtility::makeInstance(FrontendUser::class);
        $frontendUser->setEmail('test@test.de');
        $frontendUser->setUsername('TEST');

        $this->fixture->setFrontendUser($frontendUser);
    }


    /**
     * @test
     * @throws \Exception
     */
    public function setFrontendUserThrowsExceptionIfAnotherUserLoggedIn ()
    {
        /**
         * Scenario:
         *
         * Given a frontendUser-object
         * Given this object has a valid value for the email-property set
         * Given this object has no value for the username-property set
         * Given another frontendUser of instance \Madj2k\FeRegister\Domain\Model\FrontendUser is logged in
         * When the method is called
         * Then the exception is an instance of \Madj2k\FeRegister\Exception
         * Then the exception has the code 1666014579
         */
        static::expectException(\Madj2k\FeRegister\Exception::class);
        static::expectExceptionCode(1666014579);

        $this->importDataSet(self::FIXTURE_PATH .'/Database/Check30.xml');

        /** @var \Madj2k\FeRegister\Domain\Model\FrontendUser $frontendUser */
        $frontendUser = $this->frontendUserRepository->findByIdentifier(30);
        $frontendUserGroup = $this->frontendUserGroupRepository->findByIdentifier(30);

        FrontendSimulatorUtility::simulateFrontendEnvironment(1);
        FrontendUserSessionUtility::simulateLogin($frontendUser, $frontendUserGroup);

        /** @var \Madj2k\FeRegister\Domain\Model\FrontendUser $frontendUser */
        $frontendUser = GeneralUtility::makeInstance(FrontendUser::class);
        $frontendUser->setEmail('test@example.de');

        $this->fixture->setFrontendUser($frontendUser);

        FrontendSimulatorUtility::resetFrontendEnvironment();
    }


    /**
     * @test
     * @throws \Exception
     */
    public function setFrontendUserThrowsNoExceptionIfAnotherGuestUserLoggedIn ()
    {
        /**
         * Scenario:
         *
         * Given a frontendUser-object
         * Given this object has a valid value for the email-property set
         * Given this object has no value for the username-property set
         * Given another frontendUser of instance \Madj2k\FeRegister\Domain\Model\GuestUser is logged in
         * When the method is called
         * Then self is returned
         */
        $this->importDataSet(self::FIXTURE_PATH .'/Database/Check200.xml');

        /** @var \Madj2k\FeRegister\Domain\Model\GuestUser $frontendUser */
        $guestUser = $this->guestUserRepository->findByIdentifier(200);
        $frontendUserGroup = $this->frontendUserGroupRepository->findByIdentifier(200);

        FrontendSimulatorUtility::simulateFrontendEnvironment(1);
        FrontendUserSessionUtility::simulateLogin($guestUser, $frontendUserGroup);

        /** @var \Madj2k\FeRegister\Domain\Model\FrontendUser $frontendUser */
        $frontendUser = GeneralUtility::makeInstance(FrontendUser::class);
        $frontendUser->setEmail('test@example.de');

        self::assertInstanceOf(FrontendUserRegistration::class, $this->fixture->setFrontendUser($frontendUser));

        FrontendSimulatorUtility::resetFrontendEnvironment();
    }


    /**
     * @test
     * @throws \Exception
     */
    public function setFrontendUserThrowsExceptionOnGuestUser ()
    {
        /**
         * Scenario:
         *
         * Given a frontendUser-object
         * Given the frontendUser is an instance \Madj2k\FeRegister\Domain\Model\GuestUser
         * Given this object has a valid value for the email-property set
         * When the method is called
         * Then the exception is an instance of \Madj2k\FeRegister\Exception
         * Then the exception has the code 1407312134
         */
        static::expectException(\Madj2k\FeRegister\Exception::class);
        static::expectExceptionCode(1678359846);

        /** @var \Madj2k\FeRegister\Domain\Model\GuestUser $frontendUser */
        $frontendUser = GeneralUtility::makeInstance(GuestUser::class);
        $frontendUser->setEmail('test@test.de');

        $this->fixture->setFrontendUser($frontendUser);
    }


    /**
     * @test
     * @throws \Exception
     */
    public function setFrontendUserNormalizesEmailAndUsername ()
    {
        /**
         * Scenario:
         *
         * Given a frontendUser-object
         * Given this object has a value for the email-property with uppercase set
         * Given this object has a value for the username-property with uppercase set
         * When the method is called
         * Then the email-property is changed to lowercase
         * Then the username-property is changed to lowercase*
         */

        /** @var \Madj2k\FeRegister\Domain\Model\FrontendUser $frontendUser */
        $frontendUser = GeneralUtility::makeInstance(FrontendUser::class);
        $frontendUser->setEmail('TEST@TEST.DE');
        $frontendUser->setUsername('TESTER@TEST.DE');

        $this->fixture->setFrontendUser($frontendUser);

        self::assertEquals('test@test.de', $frontendUser->getEmail());
        self::assertEquals('tester@test.de', $frontendUser->getUsername());

    }


    /**
     * @test
     * @throws \Exception
     */
    public function setFrontendUserUsesEmailAsUsername ()
    {
        /**
         * Scenario:
         *
         * Given a frontendUser-object
         * Given this object has a valid value for the email-property set
         * Given this object has no value for the username-property set
         * When the method is called
         * Then the username-property of the frontendUser-object is set to the value of the email-property
         */

        /** @var \Madj2k\FeRegister\Domain\Model\FrontendUser $frontendUser */
        $frontendUser = GeneralUtility::makeInstance(FrontendUser::class);
        $frontendUser->setEmail('test@test.de');

        $this->fixture->setFrontendUser($frontendUser);
        self::assertEquals('test@test.de', $frontendUser->getUsername());

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
         * Given is frontendUser-object
         * Given this object has a valid value for the email-property set
         * Given this object has no value for the languageKey-property set
         * When the method is called
         * Then the languageKey-property of the frontendUser-object is set to the value of the current page
         */

        /** @var \Madj2k\FeRegister\Domain\Model\FrontendUser $frontendUser */
        $frontendUser = GeneralUtility::makeInstance(FrontendUser::class);
        $frontendUser->setEmail('test@test.de');

        $this->fixture->setFrontendUser($frontendUser);
        self::assertEquals('ru', $frontendUser->getTxFeregisterLanguageKey());

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
         * Given is frontendUser-object
         * Given this object has a valid value for the email-property set
         * Given this object has a value for the LanguageKey-property set
         * When the method is called
         * Then the LanguageKey-property of the frontendUser-object is not changed
         */

        /** @var \Madj2k\FeRegister\Domain\Model\FrontendUser $frontendUser */
        $frontendUser = GeneralUtility::makeInstance(FrontendUser::class);
        $frontendUser->setEmail('test@test.de');
        $frontendUser->setTxFeregisterLanguageKey('it');

        $this->fixture->setFrontendUser($frontendUser);
        self::assertEquals('it', $frontendUser->getTxFeregisterLanguageKey());
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
         * Given a non-persisted frontendUser-object
         * Given this object has a valid value for the email-property set
         * Given this object has a value for the crdate-property set
         * When the method is called
         * Then the crdate-property of the frontendUser-object is set to the current time
         */

        /** @var \Madj2k\FeRegister\Domain\Model\FrontendUser $frontendUser */
        $frontendUser = GeneralUtility::makeInstance(FrontendUser::class);
        $frontendUser->setEmail('test@test.de');
        $frontendUser->setCrdate(10);

        $this->fixture->setFrontendUser($frontendUser);
        self::assertGreaterThan(time() -5, $frontendUser->getCrdate());

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
         * Given is frontendUser-object
         * Given this object has a valid value for the email-property set
         * Given this object has a valid value for the username-property set
         * Given this object has a value for the crdate-property set
         * When the method is called
         * Then the crdate-property of the frontendUser-object is not changed
         */

        $this->importDataSet(self::FIXTURE_PATH . '/Database/Check10.xml');

        /** @var \Madj2k\FeRegister\Domain\Model\FrontendUser $frontendUser */
        $frontendUser = $this->frontendUserRepository->findByIdentifier(10);

        $this->fixture->setFrontendUser($frontendUser);
        self::assertEquals(10, $frontendUser->getCrdate());

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
         * Given a non-persisted frontendUser-object
         * Given this object has a valid value for the email-property set
         * Given this object has a value for the disable-property set
         * When the method is called
         * Then the disable-property is set to value one
         */

        /** @var \Madj2k\FeRegister\Domain\Model\FrontendUser $frontendUser */
        $frontendUser = GeneralUtility::makeInstance(FrontendUser::class);
        $frontendUser->setEmail('test@test.de');
        $frontendUser->setDisable(0);

        $this->fixture->setFrontendUser($frontendUser);
        self::assertEquals(1, $frontendUser->getDisable());

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
         * Given a persisted frontendUser-object
         * Given this object has a valid value for the email-property set
         * Given this object has a valid value for the username-property set
         * Given this object has a value for the disable-property set
         * When the method is called
         * Then the disable-property of the frontendUser-object is not changed
         */

        $this->importDataSet(self::FIXTURE_PATH . '/Database/Check10.xml');

        /** @var \Madj2k\FeRegister\Domain\Model\FrontendUser $frontendUser */
        $frontendUser = $this->frontendUserRepository->findByIdentifier(10);

        $this->fixture->setFrontendUser($frontendUser);
        self::assertEquals(0, $frontendUser->getDisable());

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
         * Given a non-persisted frontendUser-object
         * Given this object has a valid value for the email-property set
         * Given this object has a value for the registerRemoteIp-property set
         * When the method is called
         * Then the registerRemoteIp-property of the frontendUser-object is set to the current IP
         */

        /** @var \Madj2k\FeRegister\Domain\Model\FrontendUser $frontendUser */
        $frontendUser = GeneralUtility::makeInstance(FrontendUser::class);
        $frontendUser->setEmail('test@test.de');
        $frontendUser->setTxFeregisterRegisterRemoteIp('10');

        $this->fixture->setFrontendUser($frontendUser);
        self::assertEquals('127.0.0.1', $frontendUser->getTxFeregisterRegisterRemoteIp());

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
         * Given a persisted frontendUser-object
         * Given this object has a valid value for the username-property set
         * Given this object has a value for the registerRemoteIp-property set
         * When the method is called
         * Then the registerRemoteIp-property of the frontendUser-object is not changed
         */

        $this->importDataSet(self::FIXTURE_PATH .'/Database/Check10.xml');

        /** @var \Madj2k\FeRegister\Domain\Model\FrontendUser $frontendUser */
        $frontendUser = $this->frontendUserRepository->findByIdentifier(10);

        $this->fixture->setFrontendUser($frontendUser);
        self::assertEquals('1.2.3.4', $frontendUser->getTxFeregisterRegisterRemoteIp());

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
         * Given a non-persisted frontendUser-object
         * Given this object has a valid value for the email-property set
         * Given this object has a value for the endtime-property set
         * When the method is called
         * Then the endtime-property of the frontendUser-object is set to the value from the typoscript-configuration
         */

        /** @var \Madj2k\FeRegister\Domain\Model\FrontendUser $frontendUser */
        $frontendUser = GeneralUtility::makeInstance(FrontendUser::class);
        $frontendUser->setEmail('test@test.de');
        $frontendUser->setEndtime(10);

        $this->fixture->setFrontendUser($frontendUser);
        self::assertGreaterThanOrEqual(time() + (intval(7 * 24 * 60 * 60) -5), $frontendUser->getEndtime());

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
         * Given a persisted frontendUser-object
         * Given this object has a valid value for the email-property set
         * Given this object has a valid value for the username-property set
         * Given this object has a value for the endtime-property set
         * When the method is called
         * Then the endtime-property of the frontendUser-object is not changed
         */

        $this->importDataSet(self::FIXTURE_PATH .'/Database/Check10.xml');

        /** @var \Madj2k\FeRegister\Domain\Model\FrontendUser $frontendUser */
        $frontendUser = $this->frontendUserRepository->findByIdentifier(10);
        $time = time();
        $frontendUser->setEndtime($time);

        $this->fixture->setFrontendUser($frontendUser);
        self::assertEquals($time, $frontendUser->getEndtime());

    }


    /**
     * @test
     * @throws \Exception

    public function setFrontendUserSetsPidOnNewObject ()
    {
        /**
         * Scenario:
         *
         * Given a non-persisted frontendUser-object
         * Given this object has a valid value for the email-property set
         * Given this object has a value for the pid-property set
         * When the method is called
         * Then the pid-property of the frontendUser-object is set to the value from the typoscript-configuration


        /** @var \Madj2k\FeRegister\Domain\Model\FrontendUser $frontendUser
        $frontendUser = GeneralUtility::makeInstance(FrontendUser::class);
        $frontendUser->setEmail('test@test.de');
        $frontendUser->setPid(2222);

        $this->fixture->setFrontendUser($frontendUser);
        self::assertEquals(99, $frontendUser->getPid());

    }*/


    /**
     * @test
     * @throws \Exception
     */
    public function setFrontendUserKeepsPidOnExistingObject ()
    {
        /**
         * Scenario:
         *
         * Given a persisted frontendUser-object
         * Given this object has a valid value for the email-property set
         * Given this object has a valid value for the username-property set
         * Given this object has a value for the pid-property set
         * When the method is called
         * Then the pid-property of the frontendUser-object is not changed
         */

        $this->importDataSet(self::FIXTURE_PATH .'/Database/Check10.xml');

        /** @var \Madj2k\FeRegister\Domain\Model\FrontendUser $frontendUser */
        $frontendUser = $this->frontendUserRepository->findByIdentifier(10);

        $this->fixture->setFrontendUser($frontendUser);
        self::assertEquals(88, $frontendUser->getPid());
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
         * Given a persisted frontendUser-object
         * Given this object has a valid value for the email-property set
         * Given the method has been called before with this persisted frontendUser-object
         * Given a non-persisted frontendUser-object
         * Given this object has a valid value for the email-property set
         * When the method is called with that non-persisted frontendUser-object as parameter
         * Then getFrontendUserPersisted returns null
         */
        $this->importDataSet(self::FIXTURE_PATH .'/Database/Check10.xml');

        /** @var \Madj2k\FeRegister\Domain\Model\FrontendUser $frontendUser */
        $frontendUser = $this->frontendUserRepository->findByIdentifier(10);

        $this->fixture->setFrontendUser($frontendUser);
        self::assertEquals($frontendUser->getUid(), $this->fixture->getFrontendUserPersisted()->getUid());

        $frontendUser = GeneralUtility::makeInstance(FrontendUser::class);
        $frontendUser->setEmail('my@test.de');

        $this->fixture->setFrontendUser($frontendUser);
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
         * Given a persisted frontendUser-object
         * Given this object has a valid value for the email-property set
         * Given setFrontendUserToken has been called before with a random token
         * When the method is called
         * Then getFrontendUserToken returns null
         */
        $this->importDataSet(self::FIXTURE_PATH .'/Database/Check10.xml');

        /** @var \Madj2k\FeRegister\Domain\Model\FrontendUser $frontendUser */
        $frontendUser = $this->frontendUserRepository->findByIdentifier(10);

        $this->fixture->setFrontendUserToken('test');
        $this->fixture->setFrontendUser($frontendUser);

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
         * Given a persisted frontendUser-object
         * Given this object has a valid value for the email-property set
         * Given this object has a valid value for the username-property set
         * Given a non-persisted frontendUser-object
         * Given this object has a valid value for the email-property set
         * Given a persisted optIn-object
         * Given the frontendUserUid-property of this object refers to the frontendUser-object
         * Given this object has a valid token set
         * Given setFrontendUserToken has been called with that token before
         * Given getOptInPersisted was called before
         * Given getOptInPersisted returned the corresponding optIn-object
         * When the method is called with the non-persisted frontendUser-object as parameter
         * Then getOptInPersisted returns null
         */
        $this->importDataSet(self::FIXTURE_PATH .'/Database/Check40.xml');

        $this->fixture->setFrontendUserToken('test');

        /** @var \Madj2k\FeRegister\Domain\Model\OptIn $result */
        $result = $this->fixture->getOptInPersisted();

        /** @var \Madj2k\FeRegister\Domain\Model\OptIn $OptIn */
        $optIn = $this->optInRepository->findByIdentifier(40);
        self::assertEquals($optIn, $result);

        $frontendUser = GeneralUtility::makeInstance(FrontendUser::class);
        $frontendUser->setEmail('my@test.de');

        $this->fixture->setFrontendUser($frontendUser);
        self::assertNull($this->fixture->getOptInPersisted());

    }

    #==============================================================================

    /**
     * @test
     * @throws \Exception
     */
    public function setFrontendUserTokenThrowsExceptionIfAnotherUserLoggedIn ()
    {
        /**
         * Scenario:
         *
         * Given a persisted frontendUser-object
         * Given this object has a valid value for the email-property set
         * Given this object has no value for the username-property set
         * Given a persisted optIn-object
         * Given the frontendUserUid-property of this object refers to the frontendUser-object
         * Given this object has a valid token set
         * Given another frontendUser is logged in
         * When the method is called with
         * Then the exception is an instance of \Madj2k\FeRegister\Exception
         * Then the exception has the code 1666021555
         */
        static::expectException(\Madj2k\FeRegister\Exception::class);
        static::expectExceptionCode(1666021555);

        $this->importDataSet(self::FIXTURE_PATH .'/Database/Check150.xml');

        /** @var \Madj2k\FeRegister\Domain\Model\FrontendUser $frontendUser */
        $frontendUser = $this->frontendUserRepository->findByIdentifier(151);
        $frontendUserGroup = $this->frontendUserGroupRepository->findByIdentifier(151);

        FrontendSimulatorUtility::simulateFrontendEnvironment(1);
        FrontendUserSessionUtility::simulateLogin($frontendUser, $frontendUserGroup);

        /** @var \Madj2k\FeRegister\Domain\Model\FrontendUser $frontendUser */
        $this->fixture->setFrontendUserToken('test');

        /** @var \Madj2k\FeRegister\Domain\Model\OptIn $result */
        $this->fixture->getOptInPersisted();

        FrontendSimulatorUtility::resetFrontendEnvironment();

    }


    /**
     * @test
     * @throws \Exception
     */
    public function setFrontendUserTokenResetsFrontendUser ()
    {
        /**
         * Scenario:
         *
         * Given a persisted frontendUser-object
         * Given this object has a valid value for the email-property set
         * Given setFrontendUser has been called before with that object
         * When the method is called with a random token
         * Then getFrontendUser returns null
         */
        $this->importDataSet(self::FIXTURE_PATH .'/Database/Check10.xml');

        /** @var \Madj2k\FeRegister\Domain\Model\FrontendUser $frontendUser */
        $frontendUser = $this->frontendUserRepository->findByIdentifier(10);

        $this->fixture->setFrontendUser($frontendUser);
        $this->fixture->setFrontendUserToken('abcdef');

        self::assertNull($this->fixture->getFrontendUser());

    }


    /**
     * @test
     * @throws \Exception
     */
    public function setFrontendUserTokenResetsFrontendUserPersisted ()
    {
        /**
         * Scenario:
         *
         * Given a persisted frontendUser-object
         * Given this object has a valid value for the email-property set
         * Given setFrontendUser has been called before with this persisted frontendUser-object
         * Given getFrontendUserPersisted has been called before
         * Given getFrontendUserPersisted returned the set frontendUser-object
         * When the method is called with a random token
         * Then getFrontendUserPersisted returns null
         */
        $this->importDataSet(self::FIXTURE_PATH .'/Database/Check10.xml');

        /** @var \Madj2k\FeRegister\Domain\Model\FrontendUser $frontendUser */
        $frontendUser = $this->frontendUserRepository->findByIdentifier(10);

        $this->fixture->setFrontendUser($frontendUser);
        self::assertEquals($frontendUser->getUid(), $this->fixture->getFrontendUserPersisted()->getUid());

        $this->fixture->setFrontendUserToken('abcdef');
        self::assertNull($this->fixture->getFrontendUserPersisted());

    }


    /**
     * @test
     * @throws \Exception
     */
    public function setFrontendUserTokenResetsOptInPersisted ()
    {
        /**
         * Scenario:
         *
         * Given a persisted frontendUser-object
         * Given this object has a valid value for the email-property set
         * Given this object has a valid value for the username-property set
         * Given a persisted optIn-object
         * Given the frontendUserUid-property of this object refers to the frontendUser-object
         * Given this object has a valid token set
         * Given setFrontendUserToken has been called with that token before
         * Given getOptInPersisted was called before
         * Given getOptInPersisted returned the corresponding optIn-object
         * When the method is called with a random token
         * Then getOptInPersisted returns null
         */
        $this->importDataSet(self::FIXTURE_PATH .'/Database/Check40.xml');

        $this->fixture->setFrontendUserToken('test');

        /** @var \Madj2k\FeRegister\Domain\Model\OptIn $result */
        $result = $this->fixture->getOptInPersisted();

        /** @var \Madj2k\FeRegister\Domain\Model\OptIn $OptIn */
        $optIn = $this->optInRepository->findByIdentifier(40);
        self::assertEquals($optIn, $result);

        $this->fixture->setFrontendUserToken('abcdef');
        self::assertNull($this->fixture->getOptInPersisted());

    }

    #==============================================================================

    /**
     * @test
     * @throws \Exception
     */
    public function setFrontendUserOptInUpdateReturnsOnlyDirtyProperties ()
    {
        /**
         * Scenario:
         *
         * Given a persisted frontendUser-object
         * Given two properties of this object are set to new values
         * When the method is called
         * Then an array is returned
         * Then this array contains only the new set properties
         */
        $this->importDataSet(self::FIXTURE_PATH .'/Database/Check10.xml');

        /** @var \Madj2k\FeRegister\Domain\Model\FrontendUser $frontendUser */
        $frontendUser = $this->frontendUserRepository->findByIdentifier(10);
        $frontendUser->setCity('test');
        $frontendUser->setZip(123456);

        $this->fixture->setFrontendUserOptInUpdate($frontendUser);
        $result = $this->fixture->getFrontendUserOptInUpdate();

        self::assertIsArray($result);
        self::assertCount(2, $result);
        self::assertEquals('test', $result['city']);
        self::assertEquals(123456, $result['zip']);

    }


    /**
     * @test
     * @throws \Exception
     */
    public function setFrontendUserOptInUpdateReturnsOnlyDirtyPropertiesForNewObject ()
    {
        /**
         * Scenario:
         *
         * Given a new frontendUser-object
         * Given two properties of this object are set to new values
         * When the method is called
         * Then an array is returned
         * Then this array contains only the new set properties
         */

        /** @var \Madj2k\FeRegister\Domain\Model\FrontendUser $frontendUser */
        $frontendUser = GeneralUtility::makeInstance(FrontendUser::class);
        $frontendUser->setCity('test');
        $frontendUser->setZip(123456);

        $this->fixture->setFrontendUserOptInUpdate($frontendUser);
        $result = $this->fixture->getFrontendUserOptInUpdate();

        self::assertIsArray($result);
        self::assertCount(2, $result);
        self::assertEquals('test', $result['city']);
        self::assertEquals(123456, $result['zip']);
    }


    /**
     * @test
     * @throws \Exception
     */
    public function setFrontendUserOptInUpdateIgnoresSecurityRelevantProperties ()
    {
        /**
         * Scenario:
         *
         * Given a new frontendUser-object
         * Given two properties of this object are set to new values
         * Given those to properties are username and password
         * When the method is called
         * Then an array is returned
         * Then this array is empty
         */

        /** @var \Madj2k\FeRegister\Domain\Model\FrontendUser $frontendUser */
        $frontendUser = GeneralUtility::makeInstance(FrontendUser::class);
        $frontendUser->setUsername('test');
        $frontendUser->setPassword('test');

        $this->fixture->setFrontendUserOptInUpdate($frontendUser);
        $result = $this->fixture->getFrontendUserOptInUpdate();

        self::assertIsArray($result);
        self::assertCount(0, $result);
    }

    #==============================================================================

    /**
     * @test
     * @throws \Exception
     */
    public function getFrontendUserPersistedLoadsObjectFromDatabase ()
    {
        /**
         * Scenario:
         *
         * Given a persisted frontendUser-object
         * Given this object has a valid value for the email-property set
         * Given this object has a valid value for the username-property set
         * Given this object has a value for the endtime-property set that is not persisted
         * Given setFrontendUser has been called with that object as parameter
         * When the method is called
         * Then getFrontendUserPersisted returns the given frontendUser-object newly loaded from the database
         * Then this object has no endtime-property set
         */
        $this->importDataSet(self::FIXTURE_PATH .'/Database/Check10.xml');

        /** @var \Madj2k\FeRegister\Domain\Model\FrontendUser $frontendUser */
        $frontendUser = $this->frontendUserRepository->findByIdentifier(10);
        $time = time();
        $frontendUser->setEndtime($time);

        $persistenceManager = $this->objectManager->get(PersistenceManager::class);
        $persistenceManager->clearState();

        /** @var \Madj2k\FeRegister\Domain\Model\FrontendUser $frontendUserDatabase */
        $frontendUserDatabase = $this->frontendUserRepository->findByIdentifier(10);

        $this->fixture->setFrontendUser($frontendUser);

        /** @var \Madj2k\FeRegister\Domain\Model\FrontendUser $result */
        $result = $this->fixture->getFrontendUserPersisted();

        self::assertEquals($frontendUserDatabase->getUid(), $result->getUid());
        self::assertEquals(0, $result->getEndtime());

    }


    /**
     * @test
     * @throws \Exception
     */
    public function getFrontendUserPersistedLoadsObjectViaUid ()
    {
        /**
         * Scenario:
         *
         * Given a persisted frontendUser-object
         * Given this object has a valid value for the email-property set
         * Given this object has a valid value for the username-property set
         * Given the value for the email-property set to a value that is not identical to the one in the database
         * Given the value for the username-property set to a value that is not identical to the one in the database
         * Given setFrontendUser has been called with that object as parameter
         * When the method is called
         * Then getFrontendUserPersisted returns the frontendUser-object from the database
         */
        $this->importDataSet(self::FIXTURE_PATH .'/Database/Check10.xml');

        /** @var \Madj2k\FeRegister\Domain\Model\FrontendUser $frontendUser */
        $frontendUser = $this->frontendUserRepository->findByIdentifier(10);
        $frontendUser->setEmail('bubber@test.de');
        $frontendUser->setUsername('bubber@test.de');

        $this->fixture->setFrontendUser($frontendUser);

        /** @var \Madj2k\FeRegister\Domain\Model\FrontendUser $result */
        $result = $this->fixture->getFrontendUserPersisted();

        $persistenceManager = $this->objectManager->get(PersistenceManager::class);
        $persistenceManager->clearState();

        /** @var \Madj2k\FeRegister\Domain\Model\FrontendUser $frontendUserDatabase */
        $frontendUserDatabase = $this->frontendUserRepository->findByIdentifier(10);
        self::assertEquals($frontendUserDatabase, $result);

    }


    /**
     * @test
     * @throws \Exception
     */
    public function getFrontendUserPersistedLoadsObjectViaUidIfDisabled ()
    {
        /**
         * Scenario:
         *
         * Given a persisted frontendUser-object
         * Given this object is disabled
         * Given this object has a valid value for the email-property set
         * Given this object has a valid value for the username-property set
         * Given the value for the email-property set to a value that is not identical to the one in the database
         * Given the value for the username-property set to a value that is not identical to the one in the database
         * Given setFrontendUser has been called with that object as parameter
         * When the method is called
         * Then getFrontendUserPersisted returns the frontendUser-object from the database
         */
        $this->importDataSet(self::FIXTURE_PATH .'/Database/Check20.xml');

        /** @var \Madj2k\FeRegister\Domain\Model\FrontendUser $frontendUser */
        $frontendUser = $this->frontendUserRepository->findByIdentifierIncludingDisabled(20);
        $frontendUser->setEmail('bubber@test.de');
        $frontendUser->setUsername('bubber@test.de');

        $this->fixture->setFrontendUser($frontendUser);

        /** @var \Madj2k\FeRegister\Domain\Model\FrontendUser $result */
        $result = $this->fixture->getFrontendUserPersisted();

        $persistenceManager = $this->objectManager->get(PersistenceManager::class);
        $persistenceManager->clearState();

        /** @var \Madj2k\FeRegister\Domain\Model\FrontendUser $frontendUserDatabase */
        $frontendUserDatabase = $this->frontendUserRepository->findByIdentifierIncludingDisabled(20);
        self::assertEquals($frontendUserDatabase, $result);
    }


    /**
     * @test
     * @throws \Exception
     */
    public function getFrontendUserPersistedLoadsObjectViaUsername ()
    {
        /**
         * Scenario:
         *
         * Given a persisted frontendUser-object X
         * Given this object X has a valid value for the email-property set
         * Given this object X has a valid value for the username-property set
         * Given the value for the email-property set to a value that is not identical to the one in the database
         * Given the value for the username-property set to a value that is not identical to the one in the database
         * Given a non-persisted frontendUser-object Y
         * Given the value for the email-property of object Y set to a value that is identical to the one of object X
         * Given the value for the username-property of object Y set to a value that is identical to the one of object X
         * Given setFrontendUser has been called with that object as parameter
         * When the method is called
         * Then getFrontendUserPersisted returns the frontendUser-object from the database
         */
        $this->importDataSet(self::FIXTURE_PATH .'/Database/Check10.xml');

        /** @var \Madj2k\FeRegister\Domain\Model\FrontendUser $frontendUser */
        $frontendUser = GeneralUtility::makeInstance(FrontendUser::class);
        $frontendUser->setEmail('lauterbach@spd.de');
        $frontendUser->setUsername('lauterbach@spd.de');

        $this->fixture->setFrontendUser($frontendUser);

        /** @var \Madj2k\FeRegister\Domain\Model\FrontendUser $result */
        $result = $this->fixture->getFrontendUserPersisted();

        $persistenceManager = $this->objectManager->get(PersistenceManager::class);
        $persistenceManager->clearState();

        /** @var \Madj2k\FeRegister\Domain\Model\FrontendUser $frontendUserDatabase */
        $frontendUserDatabase = $this->frontendUserRepository->findByIdentifier(10);
        self::assertEquals($frontendUserDatabase, $result);
    }


    /**
     * @test
     * @throws \Exception
     */
    public function getFrontendUserPersistedLoadsObjectViaUsernameIfDisabled ()
    {
        /**
         * Scenario:
         *
         * Given a persisted frontendUser-object X
         * Given this object is disabled
         * Given this object X has a valid value for the email-property set
         * Given this object X has a valid value for the username-property set
         * Given the value for the email-property set to a value that is not identical to the one in the database
         * Given the value for the username-property set to a value that is not identical to the one in the database
         * Given a non-persisted frontendUser-object Y
         * Given the value for the email-property of object Y set to a value that is identical to the one of object X
         * Given the value for the username-property of object Y set to a value that is identical to the one of object X
         * Given setFrontendUser has been called with that object as parameter
         * When the method is called
         * Then getFrontendUserPersisted returns the frontendUser-object from the database
         */
        $this->importDataSet(self::FIXTURE_PATH .'/Database/Check20.xml');

        /** @var \Madj2k\FeRegister\Domain\Model\FrontendUser $frontendUser */
        $frontendUser = GeneralUtility::makeInstance(FrontendUser::class);
        $frontendUser->setEmail('lauterbach@spd.de');
        $frontendUser->setUsername('lauterbach@spd.de');

        $this->fixture->setFrontendUser($frontendUser);

        /** @var \Madj2k\FeRegister\Domain\Model\FrontendUser $result */
        $result = $this->fixture->getFrontendUserPersisted();

        $persistenceManager = $this->objectManager->get(PersistenceManager::class);
        $persistenceManager->clearState();

        /** @var \Madj2k\FeRegister\Domain\Model\FrontendUser $frontendUserDatabase */
        $frontendUserDatabase = $this->frontendUserRepository->findByIdentifierIncludingDisabled(20);
        self::assertEquals($frontendUserDatabase, $result);
    }


    /**
     * @test
     * @throws \Exception
     */
    public function getFrontendUserPersistedLoadsObjectViaToken ()
    {
        /**
         * Scenario:
         *
         * Given a persisted frontendUser-object
         * Given this object has a valid value for the email-property set
         * Given this object has a valid value for the username-property set
         * Given a persisted optIn-object
         * Given the frontendUserUid-property of this object refers to the frontendUser-object
         * Given this object has a valid token set
         * Given setFrontendUserToken has been called with that token
         * When the method is called
         * Then getFrontendUserPersisted returns the frontendUser-object from the database
         */
        $this->importDataSet(self::FIXTURE_PATH .'/Database/Check40.xml');

        $this->fixture->setFrontendUserToken('test');

        /** @var \Madj2k\FeRegister\Domain\Model\FrontendUser $result */
        $result = $this->fixture->getFrontendUserPersisted();

        $persistenceManager = $this->objectManager->get(PersistenceManager::class);
        $persistenceManager->clearState();

        /** @var \Madj2k\FeRegister\Domain\Model\FrontendUser $frontendUserDatabase */
        $frontendUserDatabase = $this->frontendUserRepository->findByIdentifier(40);
        self::assertEquals($frontendUserDatabase, $result);
    }


    /**
     * @test
     * @throws \Exception
     */
    public function getFrontendUserPersistedLoadsObjectViaTokenIfDisabled ()
    {
        /**
         * Scenario:
         *
         * Given a persisted frontendUser-object
         * Given this object is disabled
         * Given this object has a valid value for the email-property set
         * Given this object has a valid value for the username-property set
         * Given a persisted optIn-object
         * Given the frontendUserUid-property of this object refers to the frontendUser-object
         * Given this object has a valid token set
         * Given setFrontendUserToken has been called with that token
         * When the method is called
         * Then getFrontendUserPersisted returns the frontendUser-object from the database
         */
        $this->importDataSet(self::FIXTURE_PATH .'/Database/Check50.xml');

        $this->fixture->setFrontendUserToken('test');

        /** @var \Madj2k\FeRegister\Domain\Model\FrontendUser $result */
        $result = $this->fixture->getFrontendUserPersisted();

        $persistenceManager = $this->objectManager->get(PersistenceManager::class);
        $persistenceManager->clearState();

        /** @var \Madj2k\FeRegister\Domain\Model\FrontendUser $frontendUserDatabase */
        $frontendUserDatabase = $this->frontendUserRepository->findByIdentifierIncludingDisabled(50);
        self::assertEquals($frontendUserDatabase, $result);
    }


    /**
     * @test
     * @throws \Exception
     */
    public function getFrontendUserPersistedLoadsObjectViaTokenIfOptInDeleted ()
    {
        /**
         * Scenario:
         *
         * Given a persisted frontendUser-object
         * Given this object has a valid value for the email-property set
         * Given this object has a valid value for the username-property set
         * Given a persisted optIn-object
         * Given the frontendUserUid-property of this object refers to the frontendUser-object
         * Given this object has a valid token set
         * Given this object is marked as deleted
         * Given setFrontendUserToken has been called with that token
         * When the method is called
         * Then getFrontendUserPersisted returns null
         */
        $this->importDataSet(self::FIXTURE_PATH .'/Database/Check90.xml');

        $this->fixture->setFrontendUserToken('test');

        /** @var \Madj2k\FeRegister\Domain\Model\FrontendUser $result */
        $result = $this->fixture->getFrontendUserPersisted();

        $persistenceManager = $this->objectManager->get(PersistenceManager::class);
        $persistenceManager->clearState();

        /** @var \Madj2k\FeRegister\Domain\Model\FrontendUser $frontendUserDatabase */
        $frontendUserDatabase = $this->frontendUserRepository->findByIdentifierIncludingDisabled(90);
        self::assertEquals($frontendUserDatabase, $result);
    }


    /**
     * @test
     * @throws \Exception
     */
    public function getFrontendUserPersistedDoesNotLoadObjectViaTokenIfOptInExpired ()
    {
        /**
         * Scenario:
         *
         * Given a persisted frontendUser-object
         * Given this object has a valid value for the email-property set
         * Given this object has a valid value for the username-property set
         * Given a persisted optIn-object
         * Given the frontendUserUid-property of this object refers to the frontendUser-object
         * Given this object has a valid token set
         * Given this object has an expired endtime-property
         * Given setFrontendUserToken has been called with that token
         * When the method is called
         * Then getFrontendUserPersisted returns null
         */
        $this->importDataSet(self::FIXTURE_PATH .'/Database/Check60.xml');

        $this->fixture->setFrontendUserToken('test');

        /** @var \Madj2k\FeRegister\Domain\Model\FrontendUser $result */
        $result = $this->fixture->getFrontendUserPersisted();

        self::assertNull($result);
    }

    #==============================================================================

    /**
     * @test
     * @throws \Exception
     */
    public function getOptInPersistedLoadsObjectViaToken ()
    {
        /**
         * Scenario:
         *
         * Given a persisted frontendUser-object
         * Given this object has a valid value for the email-property set
         * Given this object has a valid value for the username-property set
         * Given a persisted optIn-object
         * Given the frontendUserUid-property of this object refers to the frontendUser-object
         * Given this object has a valid token set
         * Given setFrontendUserToken has been called with that token
         * When the method is called
         * Then the optIn-object is returned
         */
        $this->importDataSet(self::FIXTURE_PATH .'/Database/Check40.xml');

        $this->fixture->setFrontendUserToken('test');

        /** @var \Madj2k\FeRegister\Domain\Model\OptIn $result */
        $result = $this->fixture->getOptInPersisted();

        $persistenceManager = $this->objectManager->get(PersistenceManager::class);
        $persistenceManager->clearState();

        /** @var \Madj2k\FeRegister\Domain\Model\OptIn $OptIn */
        $optIn = $this->optInRepository->findByIdentifier(40);
        self::assertEquals($optIn, $result);
    }


    /**
     * @test
     * @throws \Exception
     */
    public function getOptInPersistedLoadsObjectViaTokenIfDeleted ()
    {
        /**
         * Scenario:
         *
         * Given a persisted frontendUser-object
         * Given this object has a valid value for the email-property set
         * Given this object has a valid value for the username-property set
         * Given a persisted optIn-object
         * Given the frontendUserUid-property of this object refers to the frontendUser-object
         * Given this object has a valid token set
         * Given this object is deleted
         * Given setFrontendUserToken has been called with that token
         * When the method is called
         * Then the optIn-object is returned
         */
        $this->importDataSet(self::FIXTURE_PATH .'/Database/Check90.xml');

        $this->fixture->setFrontendUserToken('test');

        /** @var \Madj2k\FeRegister\Domain\Model\OptIn $result */
        $result = $this->fixture->getOptInPersisted();

        $persistenceManager = $this->objectManager->get(PersistenceManager::class);
        $persistenceManager->clearState();

        /** @var \Madj2k\FeRegister\Domain\Model\OptIn $OptIn */
        $optIn = $this->optInRepository->findByIdentifierIncludingDeleted(90);
        self::assertEquals($optIn, $result);
    }


    /**
     * @test
     * @throws \Exception
     */
    public function getOptInPersistedDoesNotLoadObjectViaTokenIfOptInExpired ()
    {
        /**
         * Scenario:
         *
         * Given a persisted frontendUser-object
         * Given this object has a valid value for the email-property set
         * Given this object has a valid value for the username-property set
         * Given a persisted optIn-object
         * Given the frontendUserUid-property of this object refers to the frontendUser-object
         * Given this object has a valid token set
         * Given this object has an expired endtime-property
         * Given setFrontendUserToken has been called with that token
         * When the method is called
         * Then null is returned
         */
        $this->importDataSet(self::FIXTURE_PATH .'/Database/Check60.xml');

        $this->fixture->setFrontendUserToken('test');

        /** @var \Madj2k\FeRegister\Domain\Model\OptIn $result */
        $result = $this->fixture->getOptInPersisted();

        self::assertNull($result);
    }


    /**
     * @test
     * @throws \Exception
     */
    public function getOptInPersistedLoadsObjectViaFrontendUser ()
    {
        /**
         * Scenario:
         *
         * Given a persisted frontendUser-object
         * Given this object has a valid value for the email-property set
         * Given this object has a valid value for the username-property set
         * Given a persisted optIn-object
         * Given the frontendUserUid-property of this object refers to the frontendUser-object
         * Given setFrontendUser has been called with that frontendUser
         * When the method is called
         * Then the optIn-object is returned
         */
        $this->importDataSet(self::FIXTURE_PATH .'/Database/Check40.xml');

        /** @var \Madj2k\FeRegister\Domain\Model\FrontendUser $frontendUser */
        $frontendUser = $this->frontendUserRepository->findByIdentifier(40);

        $this->fixture->setFrontendUser($frontendUser);

        /** @var \Madj2k\FeRegister\Domain\Model\OptIn $result */
        $result = $this->fixture->getOptInPersisted();

        $persistenceManager = $this->objectManager->get(PersistenceManager::class);
        $persistenceManager->clearState();

        /** @var \Madj2k\FeRegister\Domain\Model\OptIn $OptIn */
        $optIn = $this->optInRepository->findByIdentifier(40);
        self::assertEquals($optIn, $result);
    }


    /**
     * @test
     * @throws \Exception
     */
    public function getOptInPersistedLoadsObjectViaFrontendUserIfDeleted ()
    {
        /**
         * Scenario:
         *
         * Given a persisted frontendUser-object
         * Given this object has a valid value for the email-property set
         * Given this object has a valid value for the username-property set
         * Given a persisted optIn-object
         * Given the frontendUserUid-property of this object refers to the frontendUser-object
         * Given this object is deleted
         * Given setFrontendUser has been called with that frontendUser-object
         * When the method is called
         * Then the optIn-object is returned
         */
        $this->importDataSet(self::FIXTURE_PATH .'/Database/Check90.xml');

        /** @var \Madj2k\FeRegister\Domain\Model\FrontendUser $frontendUser */
        $frontendUser = $this->frontendUserRepository->findByIdentifier(90);

        $this->fixture->setFrontendUser($frontendUser);

        /** @var \Madj2k\FeRegister\Domain\Model\OptIn $result */
        $result = $this->fixture->getOptInPersisted();

        $persistenceManager = $this->objectManager->get(PersistenceManager::class);
        $persistenceManager->clearState();

        /** @var \Madj2k\FeRegister\Domain\Model\OptIn $OptIn */
        $optIn = $this->optInRepository->findByIdentifierIncludingDeleted(90);
        self::assertEquals($optIn, $result);
    }


    /**
     * @test
     * @throws \Exception
     */
    public function getOptInPersistedDoesNotLoadObjectViaFrontendUserIfOptInExpired ()
    {
        /**
         * Scenario:
         *
         * Given a persisted frontendUser-object
         * Given this object has a valid value for the email-property set
         * Given this object has a valid value for the username-property set
         * Given a persisted optIn-object
         * Given the frontendUserUid-property of this object refers to the frontendUser-object
         * Given this object has an expired endtime-property
         * Given setFrontendUser has been called with that frontendUser-object
         * When the method is called
         * Then null is returned
         */
        $this->importDataSet(self::FIXTURE_PATH .'/Database/Check60.xml');

        /** @var \Madj2k\FeRegister\Domain\Model\FrontendUser $frontendUser */
        $frontendUser = $this->frontendUserRepository->findByIdentifier(60);

        $this->fixture->setFrontendUser($frontendUser);

        /** @var \Madj2k\FeRegister\Domain\Model\OptIn $result */
        $result = $this->fixture->getOptInPersisted();

        self::assertNull($result);
    }

    #==============================================================================

    /**
     * @test
     * @throws \Exception
     */
    public function createOptInThrowsExceptionOnNonPersistedFrontendUser ()
    {
        /**
         * Scenario:
         *
         * Given a non-persisted frontendUser-object
         * Given this frontendUser-object has a valid email-address set
         * Given setFrontendUser has been called with this frontendUser-object as parameter
         * When the method is called
         * Then the exception is an instance of \Madj2k\FeRegister\Exception
         * Then the exception has the code 1659691717
         */
        static::expectException(\Madj2k\FeRegister\Exception::class);
        static::expectExceptionCode(1659691717);

        $frontendUser = GeneralUtility::makeInstance(FrontendUser::class);
        $frontendUser->setEmail('test@example.de');

        $this->fixture->setFrontendUser($frontendUser);
        $this->fixture->createOptIn();

    }


    /**
     * @test
     * @throws \Exception
     */
    public function createOptInAddsOptInObjectWithAllDataBasedOnArray ()
    {
        /**
         * Scenario:
         *
         * Given a persisted frontendUser-object A
         * Given A has a valid value for the email-property set
         * Given A has a valid value for the username-property set
         * Given setFrontendUser has been called before with the A as parameter
         * Given a new frontendUser-object B
         * Given this frontendUser-object has set to properties with new values
         * Given setFrontendUserOptInUpdate has been called before with B
         * Given setCategory has been called before with a string as parameter
         * Given setData has been called before with an array as parameter
         * When the method is called
         * Then an instance of type \Madj2k\FeRegister\Domain\Model\OptIn is returned
         * Then this instance is persisted
         * Then this instance has the frontendUserUid-property set to the given A
         * Then this instance has the FrontendUserOptInUpdate-property set as array of the two properties set to B
         * Then this instance has the category-property set to the given category
         * Then this instance has the data-property set to the given data-array
         * Then this instance has the tokenUser-property set
         * Then this instance has the tokenYes-property set
         * Then this instance has the tokenNo-property set
         * Then this instance has the endtime-property set according to the typoscript-configuration
         * Then this instance has the adminApproved-property set to 1
         */

        $this->importDataSet(self::FIXTURE_PATH .'/Database/Check10.xml');

        /** @var \Madj2k\FeRegister\Domain\Model\FrontendUser $frontendUser */
        $frontendUser = $this->frontendUserRepository->findByIdentifier(10);
        $this->fixture->setFrontendUser($frontendUser);

        /** @var \Madj2k\FeRegister\Domain\Model\FrontendUser $frontendUser */
        $FrontendUserOptInUpdate = GeneralUtility::makeInstance(FrontendUser::class);
        $FrontendUserOptInUpdate->setCity('Herborn');
        $FrontendUserOptInUpdate->setZip(35745);
        $this->fixture->setFrontendUserOptInUpdate($FrontendUserOptInUpdate);

        $this->fixture->setCategory('test');
        $this->fixture->setData(['TestenKey' => 'TestenValue']);

        /** @var \Madj2k\FeRegister\Domain\Model\OptIn $optIn */
        $optInReturn = $this->fixture->createOptIn();

        self::assertInstanceOf(OptIn::class, $optInReturn);

        $persistenceManager = $this->objectManager->get(PersistenceManager::class);
        $persistenceManager->clearState();

        /** @var \Madj2k\FeRegister\Domain\Model\OptIn $optIn */
        $optIn = $this->optInRepository->findByIdentifier(1);
        self::assertEquals($optInReturn->getUid(), $optIn->getUid());

        self::assertEquals(10, $optIn->getFrontendUserUid());
        self::assertEquals(['city' => 'Herborn', 'zip' => '35745'], $optIn->getFrontendUserUpdate());
        self::assertEquals('test', $optIn->getCategory());
        self::assertEquals(['TestenKey' => 'TestenValue'], $optIn->getData());
        self::assertNotEmpty($optIn->getTokenUser());
        self::assertNotEmpty($optIn->getTokenYes());
        self::assertNotEmpty($optIn->getTokenNo());
        self::assertGreaterThan(time() + (intval(7 * 24 * 60 * 60) -5), $optIn->getEndtime());
        self::assertEquals(1, $optIn->getAdminApproved());
    }


    /**
     * @test
     * @throws \Exception
     */
    public function createOptInAddsOptInObjectWithObjectData ()
    {
        /**
         * Scenario:
         *
         * Given a persisted frontendUser-object A
         * Given A has a valid value for the email-property set
         * Given A has a valid value for the username-property set
         * Given setFrontendUser has been called before with the A as parameter
         * Given setData has been called before with an object as parameter
         * When the method is called
         * Then an instance of type \Madj2k\FeRegister\Domain\Model\OptIn is returned
         * Then this instance is persisted
         * Then this instance has the foreignTable-property set to the table of the object set via setData
         * Then this instance has the foreignUid-property set to the uid of the object set via setData
         */

        $this->importDataSet(self::FIXTURE_PATH .'/Database/Check210.xml');

        /** @var \Madj2k\FeRegister\Domain\Model\FrontendUser $frontendUser */
        $frontendUser = $this->frontendUserRepository->findByIdentifier(210);
        $this->fixture->setFrontendUser($frontendUser);

        /** @var \Madj2k\FeRegister\Domain\Model\ShippingAddress shippingAddress */
        $shippingAddress = $this->shippingAddressRepository->findByIdentifier(210);
        $this->fixture->setData($shippingAddress);

        /** @var \Madj2k\FeRegister\Domain\Model\OptIn $optIn */
        $optInReturn = $this->fixture->createOptIn();

        self::assertInstanceOf(OptIn::class, $optInReturn);

        $persistenceManager = $this->objectManager->get(PersistenceManager::class);
        $persistenceManager->clearState();

        /** @var \Madj2k\FeRegister\Domain\Model\OptIn $optIn */
        $optIn = $this->optInRepository->findByIdentifier(1);
        self::assertEquals($optInReturn->getUid(), $optIn->getUid());

        self::assertEquals('tx_feregister_domain_model_shippingaddress', $optIn->getForeignTable());
        self::assertEquals(210, $optIn->getForeignUid());
    }


    /**
     * @test
     * @throws \Exception
     */
    public function createOptInAddsOptInObjectWithObjectDataAndDataParent ()
    {
        /**
         * Scenario:
         *
         * Given a persisted frontendUser-object A
         * Given A has a valid value for the email-property set
         * Given A has a valid value for the username-property set
         * Given setFrontendUser has been called before with the A as parameter
         * Given setData has been called before with an object as parameter
         * Given setDataParent has been called before with another object as parameter
         * When the method is called
         * Then an instance of type \Madj2k\FeRegister\Domain\Model\OptIn is returned
         * Then this instance is persisted
         * Then this instance has the foreignTable-property set to the table of the object set via setData
         * Then this instance has the foreignUid-property set to the uid of the object set via setData
         * Then this instance has the parentForeignTable-property set to the table of the object set via setDataParent
         * Then this instance has the parentForeignUid-property set to the uid of the object set via setDataParent
         */

        $this->importDataSet(self::FIXTURE_PATH .'/Database/Check220.xml');

        /** @var \Madj2k\FeRegister\Domain\Model\FrontendUser $frontendUser */
        $frontendUser = $this->frontendUserRepository->findByIdentifier(220);
        $this->fixture->setFrontendUser($frontendUser);

        /** @var \Madj2k\FeRegister\Domain\Model\ShippingAddress shippingAddress */
        $shippingAddress = $this->shippingAddressRepository->findByIdentifier(220);
        $this->fixture->setData($shippingAddress);

        /** @var \Madj2k\FeRegister\Domain\Model\Title title */
        $title = $this->titleRepository->findByIdentifier(221);
        $this->fixture->setDataParent($title);

        /** @var \Madj2k\FeRegister\Domain\Model\OptIn $optIn */
        $optInReturn = $this->fixture->createOptIn();

        self::assertInstanceOf(OptIn::class, $optInReturn);

        $persistenceManager = $this->objectManager->get(PersistenceManager::class);
        $persistenceManager->clearState();

        /** @var \Madj2k\FeRegister\Domain\Model\OptIn $optIn */
        $optIn = $this->optInRepository->findByIdentifier(1);
        self::assertEquals($optInReturn->getUid(), $optIn->getUid());

        self::assertEquals('tx_feregister_domain_model_shippingaddress', $optIn->getForeignTable());
        self::assertEquals(220, $optIn->getForeignUid());

        self::assertEquals('tx_feregister_domain_model_title', $optIn->getParentForeignTable());
        self::assertEquals(221, $optIn->getParentForeignUid());
    }


    /**
     * @test
     * @throws \Exception
     */
    public function createOptInAddsConsentObject ()
    {
        /**
         * Scenario:
         *
         * Given a persisted frontendUser-object
         * Given this object has a valid value for the email-property set
         * Given this object has a valid value for the username-property set
         * Given setFrontendUser has been called before with the frontendUser-object as parameter
         * Given setCategory has been called before with a string as parameter
         * Given setData has been called before with an array as parameter
         * Given a request-object is set
         * When the method is called
         * Then an instance of type \Madj2k\FeRegister\Domain\Model\OptIn is returned
         * Then this instance is persisted
         * Then a consent-object is created
         */

        $this->importDataSet(self::FIXTURE_PATH .'/Database/Check10.xml');

        /** @var \Madj2k\FeRegister\Domain\Model\FrontendUser $frontendUser */
        $frontendUser = $this->frontendUserRepository->findByIdentifier(10);

        $this->fixture->setFrontendUser($frontendUser);
        $this->fixture->setCategory('test');
        $this->fixture->setData(['TestenKey' => 'TestenValue']);
        $this->fixture->setRequest(GeneralUtility::makeInstance(Request::class));

        /** @var \Madj2k\FeRegister\Domain\Model\OptIn $optIn */
        $optIn = $this->fixture->createOptIn();

        self::assertInstanceOf(OptIn::class, $optIn);
        self::assertNotEmpty($optIn->getUid());

        self::assertEquals(1, $this->consentRepository->countAll());

    }


    /**
     * @test
     * @throws \Exception
     */
    public function createOptInUpdatesOptInPersisted ()
    {
        /**
         * Scenario:
         *
         * Given a persisted frontendUser-object
         * Given this object has a valid value for the email-property set
         * Given this object has a valid value for the username-property set
         * Given setFrontendUser has been called before with the frontendUser-object as parameter
         * Given setCategory has been called before with a string as parameter
         * Given setData has been called before with an array as parameter
         * Given getOptInPersisted has been called before
         * Given that call returned null
         * When the method is called
         * Then an instance of type \Madj2k\FeRegister\Domain\Model\OptIn is returned
         * Then getOptInPersisted returns the same instance
         */

        $this->importDataSet(self::FIXTURE_PATH .'/Database/Check10.xml');

        /** @var \Madj2k\FeRegister\Domain\Model\FrontendUser $frontendUser */
        $frontendUser = $this->frontendUserRepository->findByIdentifier(10);

        $this->fixture->setFrontendUser($frontendUser);
        $this->fixture->setCategory('test');
        $this->fixture->setData(['TestenKey' => 'TestenValue']);

        self::assertNull($this->fixture->getOptInPersisted());

        /** @var \Madj2k\FeRegister\Domain\Model\OptIn $optIn */
        $optIn = $this->fixture->createOptIn();

        self::assertInstanceOf(OptIn::class, $optIn);
        self::assertSame($optIn, $this->fixture->getOptInPersisted());
    }


    /**
     * @test
     * @throws \Exception
     */
    public function createOptInAddsAdminDataIfApprovalsAreSet ()
    {
        /**
         * Scenario:
         *
         * Given a persisted frontendUser-object
         * Given this object has a valid value for the email-property set
         * Given this object has a valid value for the username-property set
         * Given setFrontendUser has been called before with the frontendUser-object as parameter
         * Given two persisted backendUser-objects
         * Given this to objects are set as admins via setApproval
         * When the method is called
         * Then an instance of type \Madj2k\FeRegister\Domain\Model\OptIn is returned
         * Then this object is persisted
         * Then this optIn-object has the adminTokenYes-property set
         * Then this optIn-object has the adminTokenNo-property set
         * Then this optIn-object has the adminApproved-property set to zero
         * Then this optIn-Object has the admins-property set to the given backendUser-objects
         */

        $this->importDataSet(self::FIXTURE_PATH .'/Database/Check100.xml');

        /** @var \Madj2k\FeRegister\Domain\Model\FrontendUser $frontendUser */
        $frontendUser = $this->frontendUserRepository->findByIdentifier(100);

        /** @var \Madj2k\FeRegister\Domain\Model\BackendUser $backendUserOne */
        $backendUserOne = $this->backendUserRepository->findByIdentifier(100);

        /** @var \Madj2k\FeRegister\Domain\Model\BackendUser $backendUserTwo */
        $backendUserTwo = $this->backendUserRepository->findByIdentifier(101);

        $this->fixture->setFrontendUser($frontendUser);
        $this->fixture->addApproval($backendUserOne);
        $this->fixture->addApproval($backendUserTwo);

        /** @var \Madj2k\FeRegister\Domain\Model\OptIn $optIn */
        $optInReturn = $this->fixture->createOptIn();

        self::assertInstanceOf(OptIn::class, $optInReturn);

        $persistenceManager = $this->objectManager->get(PersistenceManager::class);
        $persistenceManager->clearState();

        /** @var \Madj2k\FeRegister\Domain\Model\OptIn $optIn */
        $optIn = $this->optInRepository->findByIdentifier(1);
        self::assertEquals($optInReturn->getUid(), $optIn->getUid());

        self::assertEquals(100, $optIn->getFrontendUserUid());
        self::assertNotEmpty($optIn->getAdminTokenYes());
        self::assertNotEmpty($optIn->getAdminTokenNo());
        self::assertEquals(0, $optIn->getAdminApproved());
        self::assertCount(2, $optIn->getAdmins());

    }


    /**
     * @test
     * @throws \Exception
     */
    public function createOptInSetsForeignTableAndUid ()
    {
        /**
         * Scenario:
         *
         * Given a persisted frontendUser-object
         * Given this object has a valid value for the email-property set
         * Given this object has a valid value for the username-property set
         * Given setFrontendUser has been called before with the frontendUser-object as parameter
         * Given setCategory has been called before with a string as parameter
         * Given setData has been called before with a persisted frontendUserGroup-object with uid = 1 as parameter
         * Given getOptInPersisted has been called before
         * Given that call returned null
         * When the method is called
         * Then an instance of type \Madj2k\FeRegister\Domain\Model\OptIn is returned
         * Then this instance has the foreignTable-property set to fe_groups
         * Then this instance has the foreignUid-property set to 1
         */

        $this->importDataSet(self::FIXTURE_PATH .'/Database/Check140.xml');

        /** @var \Madj2k\FeRegister\Domain\Model\FrontendUser $frontendUser */
        $frontendUser = $this->frontendUserRepository->findByIdentifier(140);

        /** @var \Madj2k\FeRegister\Domain\Model\FrontendUserGroup $frontendUserGroup */
        $frontendUserGroup = $this->frontendUserGroupRepository->findByIdentifier(140);

        $this->fixture->setFrontendUser($frontendUser);
        $this->fixture->setCategory('test');
        $this->fixture->setData($frontendUserGroup);

        self::assertNull($this->fixture->getOptInPersisted());

        /** @var \Madj2k\FeRegister\Domain\Model\OptIn $optIn */
        $optIn = $this->fixture->createOptIn();

        self::assertInstanceOf(OptIn::class, $optIn);
        self::assertEquals('fe_groups', $optIn->getForeignTable());
        self::assertEquals(140, $optIn->getForeignUid());

    }

    #==============================================================================

    /**
     * @test
     * @throws \Exception
     */
    public function startRegistrationThrowsExceptionIfNoFrontendUserSet ()
    {
        /**
         * Scenario:
         *
         * Given no frontendUser-object
         * When the method is called
         * Then the exception is an instance of \Madj2k\FeRegister\Exception
         * Then the exception has the code 1434997734
         */
        static::expectException(\Madj2k\FeRegister\Exception::class);
        static::expectExceptionCode(1434997734);

        $this->fixture->startRegistration();

    }


    /**
     * @test
     * @throws \Exception
     */
    public function startRegistrationThrowsExceptionIfNewRegistrationWithLoggedInUser ()
    {
        /**
         * Scenario:
         *
         * Given a non-persistent frontendUser-object
         * Given this object has a valid value for the email-property set
         * Given this object has a valid value for the username-property set
         * Given setFrontendUser has been called with this frontendUser-object as parameter
         * Given setData has not been called before
         * Given a frontendUser of instance \Madj2k\FeRegister\Domain\Model\FrontendUser is logged in
         * When the method is called
         * Then the exception is an instance of \Madj2k\FeRegister\Exception
         * Then the exception has the code 1659691717
         */
        static::expectException(\Madj2k\FeRegister\Exception::class);
        static::expectExceptionCode(1666014579);

        $this->importDataSet(self::FIXTURE_PATH .'/Database/Check30.xml');

        /** @var \Madj2k\FeRegister\Domain\Model\FrontendUser $frontendUser */
        $frontendUser = $this->frontendUserRepository->findByIdentifier(30);
        $frontendUserGroup = $this->frontendUserGroupRepository->findByIdentifier(30);

        FrontendSimulatorUtility::simulateFrontendEnvironment(1);
        FrontendUserSessionUtility::simulateLogin($frontendUser, $frontendUserGroup);

        /** @var \Madj2k\FeRegister\Domain\Model\FrontendUser $frontendUser */
        $frontendUser = GeneralUtility::makeInstance(FrontendUser::class);
        $frontendUser->setEmail('test@example.de');

        $this->fixture->setFrontendUser($frontendUser);
        $this->fixture->startRegistration();

        FrontendSimulatorUtility::resetFrontendEnvironment();

    }


    /**
     * @test
     * @throws \Exception
     */
    public function startRegistrationReturnsTrueIfNewRegistrationWithLoggedInGuestUser ()
    {
        /**
         * Scenario:
         *
         * Given a non-persistent frontendUser-object
         * Given this object has a valid value for the email-property set
         * Given this object has a valid value for the username-property set
         * Given setFrontendUser has been called with this frontendUser-object as parameter
         * Given setData has not been called before
         * Given a frontendUser of instance \Madj2k\FeRegister\Domain\Model\GuestUser is logged in
         * When the method is called
         * Then true is returned
         */
        $this->importDataSet(self::FIXTURE_PATH .'/Database/Check200.xml');

        /** @var \Madj2k\FeRegister\Domain\Model\GuestUser $guestUser */
        $guestUser = $this->guestUserRepository->findByIdentifier(200);
        $frontendUserGroup = $this->frontendUserGroupRepository->findByIdentifier(200);

        FrontendSimulatorUtility::simulateFrontendEnvironment(1);
        FrontendUserSessionUtility::simulateLogin($guestUser, $frontendUserGroup);

        /** @var \Madj2k\FeRegister\Domain\Model\FrontendUser $frontendUser */
        $frontendUser = GeneralUtility::makeInstance(FrontendUser::class);
        $frontendUser->setEmail('test@example.de');

        $this->fixture->setFrontendUser($frontendUser);
        self::assertTrue($this->fixture->startRegistration());

        FrontendSimulatorUtility::resetFrontendEnvironment();

    }


    /**
     * @test
     * @throws \Exception
     */
    public function startRegistrationCreatesNoOptInForExistingUser ()
    {
        /**
         * Scenario:
         *
         * Given a persisted frontendUser-object
         * Given this object has a valid value for the email-property set
         * Given setFrontendUser has been called with this frontendUser-object as parameter
         * Given no additional data is set
         * When the method is called
         * Then false is returned
         * Then no optIn is created
         */

        $this->importDataSet(self::FIXTURE_PATH .'/Database/Check10.xml');

        /** @var \Madj2k\FeRegister\Domain\Model\FrontendUser $frontendUser */
        $frontendUser = $this->frontendUserRepository->findByIdentifier(10);

        $this->fixture->setFrontendUser($frontendUser);

        self::assertFalse($this->fixture->startRegistration());
        self::assertEquals(0, $this->optInRepository->countAll());

    }


    /**
     * @test
     * @throws \Exception
     */
    public function startRegistrationCreatesOptInForExistingUser ()
    {
        /**
         * Scenario:
         *
         * Given a persisted frontendUser-object
         * Given this object has a valid value for the email-property set
         * Given this object has a valid value for the username-property set
         * Given setFrontendUser has been called with this frontendUser-object as parameter
         * Given additional data is set
         * When the method is called
         * Then the password-property is not overridden
         * Then the disable-property is false
         * Then true is returned
         * Then an optIn is created
         * Then no privacy-object is created
         */

        $this->importDataSet(self::FIXTURE_PATH .'/Database/Check10.xml');

        /** @var \Madj2k\FeRegister\Domain\Model\FrontendUser $frontendUser */
        $frontendUser = $this->frontendUserRepository->findByIdentifier(10);

        $this->fixture->setFrontendUser($frontendUser);
        $this->fixture->setData(['test']);

        self::assertTrue($this->fixture->startRegistration());
        self::assertEquals('test', $frontendUser->getPassword());
        self::assertFalse($frontendUser->getDisable());
        self::assertEquals(1, $this->optInRepository->countAll());
        self::assertEquals(0, $this->consentRepository->countAll());
    }


    /**
     * @test
     * @throws \Exception
     */
    public function startRegistrationCreatesOptInAndPrivacyForExistingUser ()
    {
        /**
         * Scenario:
         *
         * Given a persisted frontendUser-object
         * Given this object has a valid value for the email-property set
         * Given this object has a valid value for the username-property set
         * Given setFrontendUser has been called with this frontendUser-object as parameter
         * Given additional data is set
         * Given a request-object is set
         * When the method is called
         * Then true is returned
         * Then the password is not overridden
         * Then the disable-property is false*
         * Then an optIn is created
         * Then a privacy-object is created
         */

        $this->importDataSet(self::FIXTURE_PATH .'/Database/Check10.xml');

        /** @var \Madj2k\FeRegister\Domain\Model\FrontendUser $frontendUser */
        $frontendUser = $this->frontendUserRepository->findByIdentifier(10);

        $this->fixture->setFrontendUser($frontendUser);
        $this->fixture->setData(['test']);
        $this->fixture->setRequest(GeneralUtility::makeInstance(Request::class));

        self::assertTrue($this->fixture->startRegistration());
        self::assertEquals('test', $frontendUser->getPassword());
        self::assertFalse($frontendUser->getDisable());
        self::assertEquals(1, $this->optInRepository->countAll());
        self::assertEquals(1, $this->consentRepository->countAll());
    }


    /**
     * @test
     * @throws \Exception
     */
    public function startRegistrationCreatesOptInForNewUser ()
    {
        /**
         * Scenario:
         *
         * Given a non-persisted frontendUser-object
         * Given this object has a valid value for the email-property set
         * Given setFrontendUser has been called with this frontendUser-object as parameter
         * When the method is called
         * Then true is returned
         * Then the frontendUser-object is persisted
         * Then the password is set to a newly created
         * Then the tempPlaintextPassword-property is set to the object but not persisted
         * Then the disable-property is set to true
         * Then an optIn-object is created
         * Then no privacy-object is created
         */
        /** @var \Madj2k\FeRegister\Domain\Model\FrontendUser $frontendUser */
        $frontendUser = GeneralUtility::makeInstance(FrontendUser::class);
        $frontendUser->setEmail('test@testen.de');

        $this->fixture->setFrontendUser($frontendUser);
        self::assertTrue($this->fixture->startRegistration());

        $persistenceManager = $this->objectManager->get(PersistenceManager::class);
        $persistenceManager->clearState();

        /** @var \Madj2k\FeRegister\Domain\Model\FrontendUser $frontendUserDatabase */
        $frontendUserDatabase = $this->frontendUserRepository->findByIdentifierIncludingDisabled(1);
        self::assertEquals($frontendUserDatabase->getUsername(), $frontendUser->getUsername());
        self::assertNotEmpty($frontendUserDatabase->getPassword());
        self::assertEmpty($frontendUserDatabase->getTempPlaintextPassword());
        self::assertNotEmpty($frontendUser->getTempPlaintextPassword());
        self::assertTrue($frontendUserDatabase->getDisable());

        self::assertEquals(1, $this->optInRepository->countAll());
        self::assertEquals(0, $this->consentRepository->countAll());
    }


    /**
     * @test
     * @throws \Exception
     */
    public function startRegistrationCreatesOptInAndPrivacyForNewUser ()
    {
        /**
         * Scenario:
         *
         * Given a non-persisted frontendUser-object
         * Given this object has a valid value for the email-property set
         * Given setFrontendUser has been called with this frontendUser-object as parameter
         * Given a request-object is set
         * When the method is called
         * Then true is returned
         * Then the frontendUser-object is persisted
         * Then the password is set to a newly created
         * Then the tempPlaintextPassword-property is set to the object but not persisted
         * Then the disable-property is true
         * Then an optIn-object is created
         * Then a privacy-object is created
         */
        /** @var \Madj2k\FeRegister\Domain\Model\FrontendUser $frontendUser */
        $frontendUser = GeneralUtility::makeInstance(FrontendUser::class);
        $frontendUser->setEmail('test@testen.de');
        $this->fixture->setRequest(GeneralUtility::makeInstance(Request::class));
        $this->fixture->setFrontendUser($frontendUser);

        self::assertTrue($this->fixture->startRegistration());

        $persistenceManager = $this->objectManager->get(PersistenceManager::class);
        $persistenceManager->clearState();

        /** @var \Madj2k\FeRegister\Domain\Model\FrontendUser $frontendUserDatabase */
        $frontendUserDatabase = $this->frontendUserRepository->findByIdentifierIncludingDisabled(1);
        self::assertEquals($frontendUserDatabase->getUsername(), $frontendUser->getUsername());
        self::assertNotEmpty($frontendUserDatabase->getPassword());
        self::assertEmpty($frontendUserDatabase->getTempPlaintextPassword());
        self::assertNotEmpty($frontendUser->getTempPlaintextPassword());
        self::assertTrue($frontendUserDatabase->getDisable());

        self::assertEquals(1, $this->optInRepository->countAll());
        self::assertEquals(1, $this->consentRepository->countAll());

    }

    #==============================================================================

    /**
     * @test
     * @throws \Exception
     */
    public function validateOptInThrowsExceptionIfNoFrontendUserTokenSet ()
    {
        /**
         * Scenario:
         *
         * Given no frontendUserToken
         * When the method is called
         * Then the exception is an instance of \Madj2k\FeRegister\Exception
         * Then the exception has the code 1434997735
         */
        static::expectException(\Madj2k\FeRegister\Exception::class);
        static::expectExceptionCode(1434997735);

        $this->fixture->validateOptIn('test');

    }


    /**
     * @test
     * @throws \Exception
     */
    public function validateOptInReturns999IfOptInNotFound ()
    {
        /**
         * Scenario:
         *
         * Given a persisted frontendUser-object
         * Given this object has a valid value for the email-property set
         * Given this object is disabled
         * Given a persisted optIn-object
         * Given this object has a valid tokenUser
         * Given this object has a valid tokenYes
         * Given this object has a valid tokenNo
         * Given the frontendUserId-property refers to the frontendUser-object
         * Given setFrontendUserToken has been called before with a random token as parameter
         * When the method is called with the valid tokenYes-value as parameter
         * Then 999 is returned
         */
        $this->importDataSet(self::FIXTURE_PATH .'/Database/Check70.xml');

        $this->fixture->setFrontendUserToken('abcdef');
        self::assertEquals(999, $this->fixture->validateOptIn('test_yes'));
    }


    /**
     * @test
     * @throws \Exception
     */
    public function validateOptInReturns999IfFrontendUserNotFound ()
    {
        /**
         * Scenario:
         *
         * Given a persisted frontendUser-object
         * Given this object has a valid value for the email-property set
         * Given this object is deleted
         * Given a persisted optIn-object
         * Given this object has a valid tokenUser
         * Given this object has a valid tokenYes
         * Given this object has a valid tokenNo
         * Given the frontendUserId-property refers to the frontendUser-object
         * Given setFrontendUserToken has been called before with the valid tokenUser-value as parameter
         * When the method is called with the valid tokenYes-value as parameter
         * Then 999 is returned
         */
        $this->importDataSet(self::FIXTURE_PATH .'/Database/Check80.xml');

        $this->fixture->setFrontendUserToken('test');
        self::assertEquals(999, $this->fixture->validateOptIn('test_yes'));

    }


    /**
     * @test
     * @throws \Exception
     */
    public function validateOptInReturns299IfMatchingTokenYesOnDeleted ()
    {
        /**
         * Scenario:
         *
         * Given a persisted frontendUser-object
         * Given this object has a valid value for the email-property set
         * Given a persisted optIn-object
         * Given this object has a valid tokenUser-property
         * Given this object has a valid tokenYes-property
         * Given this object has a valid tokenNo-property
         * Given this object is deleted
         * Given a request-object is set
         * Given the frontendUserId-property refers to the frontendUser-object
         * Given setFrontendUserToken has been called before with the valid tokenUser-value as parameter
         * When the method is called with the valid tokenYes-value as parameter
         * Then 299 is returned
         * Then no privacy-object is created
         */
        $this->importDataSet(self::FIXTURE_PATH .'/Database/Check90.xml');

        $this->fixture->setFrontendUserToken('test');
        $this->fixture->setRequest(GeneralUtility::makeInstance(Request::class));

        self::assertEquals(399, $this->fixture->validateOptIn('test_yes'));
        self::assertEquals(0, $this->consentRepository->countAll());
    }


    /**
     * @test
     * @throws \Exception
     */
    public function validateOptInReturns999IfMatchingTokenNoOnApprovedDeleted ()
    {
        /**
         * Scenario:
         *
         * Given a persisted frontendUser-object
         * Given this object has a valid value for the email-property set
         * Given a persisted optIn-object
         * Given this object has a valid tokenUser-property
         * Given this object has a valid tokenYes-property
         * Given this object has a valid tokenNo-property
         * Given this object has been approved
         * Given this object is deleted
         * Given a request-object is set
         * Given the frontendUserId-property refers to the frontendUser-object
         * Given setFrontendUserToken has been called before with the valid tokenUser-value as parameter
         * When the method is called with the valid tokenYes-value as parameter
         * Then 999 is returned
         * Then no privacy-object is created
         */
        $this->importDataSet(self::FIXTURE_PATH .'/Database/Check230.xml');

        $this->fixture->setFrontendUserToken('test');
        $this->fixture->setRequest(GeneralUtility::makeInstance(Request::class));

        self::assertEquals(999, $this->fixture->validateOptIn('test_no'));
        self::assertEquals(0, $this->consentRepository->countAll());
    }


    /**
     * @test
     * @throws \Exception
     */
    public function validateOptInReturns201IfMatchingTokenYesWithoutAdminApproval ()
    {
        /**
         * Scenario:
         *
         * Given a persisted frontendUser-object
         * Given this object has a valid value for the email-property set
         * Given this object is disabled
         * Given a persisted optIn-object
         * Given this object has a valid tokenUser-property
         * Given this object has a valid tokenYes-property
         * Given this object has a valid tokenNo-property
         * Given this object has the adminApproved-property set to zero
         * Given this object has the approved-property set to zero
         * Given this object is deleted
         * Given a request-object is set
         * Given the frontendUserId-property refers to the frontendUser-object
         * Given setFrontendUserToken has been called before with the valid tokenUser-value as parameter
         * When the method is called with the valid tokenYes-value as parameter
         * Then 201 is returned
         * Then a privacy-object is created
         * Then the approved-property of the optIn-object is set to 1
         * Then the frontendUser is still disabled
         */
        $this->importDataSet(self::FIXTURE_PATH .'/Database/Check110.xml');

        $this->fixture->setFrontendUserToken('test');
        $this->fixture->setRequest(GeneralUtility::makeInstance(Request::class));

        self::assertEquals(201, $this->fixture->validateOptIn('test_yes'));
        self::assertEquals(1, $this->consentRepository->countAll());

        /** @var \Madj2k\FeRegister\Domain\Model\OptIn $optIn */
        $optIn = $this->optInRepository->findByIdentifierIncludingDeleted(110);
        self::assertEquals(1, $optIn->getApproved());

        /** @var \Madj2k\FeRegister\Domain\Model\FrontendUser $frontendUser */
        $frontendUser = $this->frontendUserRepository->findByIdentifierIncludingDisabled(110);
        self::assertTrue($frontendUser->getDisable());
    }


    /**
     * @test
     * @throws \Exception
     */
    public function validateOptInReturns201IfMatchingTokenYesTwiceWithoutAdminApproval ()
    {
        /**
         * Scenario:
         *
         * Given a persisted frontendUser-object
         * Given this object has a valid value for the email-property set
         * Given this object is disabled
         * Given a persisted optIn-object
         * Given this object has a valid tokenUser-property
         * Given this object has a valid tokenYes-property
         * Given this object has a valid tokenNo-property
         * Given this object has the adminApproved-property set to zero
         * Given this object has the approved-property set to zero
         * Given this object is deleted
         * Given a request-object is set
         * Given the frontendUserId-property refers to the frontendUser-object
         * Given setFrontendUserToken has been called before with the valid tokenUser-value as parameter
         * Given the method has been called with the valid tokenYes-value as parameter before
         * When the method is called with the valid tokenYes-value as parameter
         * Then 201 is returned
         * Then no second privacy-object is created
         * Then the frontendUser is still disabled
         */
        $this->importDataSet(self::FIXTURE_PATH .'/Database/Check110.xml');

        $this->fixture->setFrontendUserToken('test');
        $this->fixture->setRequest(GeneralUtility::makeInstance(Request::class));

        $this->fixture->validateOptIn('test_yes');

        self::assertEquals(201, $this->fixture->validateOptIn('test_yes'));
        self::assertEquals(1, $this->consentRepository->countAll());

        /** @var \Madj2k\FeRegister\Domain\Model\FrontendUser $frontendUser */
        $frontendUser = $this->frontendUserRepository->findByIdentifierIncludingDisabled(110);
        self::assertTrue($frontendUser->getDisable());
    }


    /**
     * @test
     * @throws \Exception
     */
    public function validateOptInReturns202IfMatchingAdminTokenYesWithoutUserApproval ()
    {
        /**
         * Scenario:
         *
         * Given a persisted frontendUser-object
         * Given this object has a valid value for the email-property set
         * Given this object is disabled
         * Given a persisted optIn-object
         * Given this object has a valid tokenUser-property
         * Given this object has a valid tokenYes-property
         * Given this object has a valid tokenNo-property
         * Given this object has a valid adminTokenYes-property
         * Given this object has a valid adminTokenNo-property
         * Given this object has the adminApproved-property set to zero
         * Given this object has the approved-property set to zero
         * Given this object is deleted
         * Given a request-object is set
         * Given the frontendUserId-property refers to the frontendUser-object
         * Given setFrontendUserToken has been called before with the valid tokenUser-value as parameter
         * When the method is called with the valid adminTokenYes-value as parameter
         * Then 202 is returned
         * Then no privacy-object is created
         * Then the adminApproved-property of the optIn-object is set to 1
         * Then the frontendUser is still disabled
         */
        $this->importDataSet(self::FIXTURE_PATH .'/Database/Check120.xml');

        $this->fixture->setFrontendUserToken('test');
        $this->fixture->setRequest(GeneralUtility::makeInstance(Request::class));

        self::assertEquals(202, $this->fixture->validateOptIn('test_admin_yes'));
        self::assertEquals(0, $this->consentRepository->countAll());

        /** @var \Madj2k\FeRegister\Domain\Model\OptIn $optIn */
        $optIn = $this->optInRepository->findByIdentifierIncludingDeleted(120);
        self::assertEquals(1, $optIn->getAdminApproved());

        /** @var \Madj2k\FeRegister\Domain\Model\FrontendUser $frontendUser */
        $frontendUser = $this->frontendUserRepository->findByIdentifierIncludingDisabled(120);
        self::assertTrue($frontendUser->getDisable());
    }


    /**
     * @test
     * @throws \Exception
     */
    public function validateOptInReturns202IfMatchingAdminTokenYesTwiceWithoutUserApproval ()
    {
        /**
         * Scenario:
         *
         * Given a persisted frontendUser-object
         * Given this object has a valid value for the email-property set
         * Given this object is disabled
         * Given a persisted optIn-object
         * Given this object has a valid tokenUser-property
         * Given this object has a valid tokenYes-property
         * Given this object has a valid tokenNo-property
         * Given this object has a valid adminTokenYes-property
         * Given this object has a valid adminTokenNo-property
         * Given this object has the adminApproved-property set to zero
         * Given this object has the approved-property set to zero
         * Given this object is deleted
         * Given a request-object is set
         * Given the frontendUserId-property refers to the frontendUser-object
         * Given setFrontendUserToken has been called before with the valid tokenUser-value as parameter
         * Given the method has been called with the valid adminTokenYes-value as parameter before
         * When the method is called with the valid adminTokenYes-value as parameter
         * Then 298 is returned
         * Then no privacy-object is created
         * Then the frontendUser is still disabled
         */
        $this->importDataSet(self::FIXTURE_PATH .'/Database/Check120.xml');

        $this->fixture->setFrontendUserToken('test');
        $this->fixture->setRequest(GeneralUtility::makeInstance(Request::class));

        $this->fixture->validateOptIn('test_admin_yes');

        self::assertEquals(202, $this->fixture->validateOptIn('test_admin_yes'));
        self::assertEquals(0, $this->consentRepository->countAll());

        /** @var \Madj2k\FeRegister\Domain\Model\FrontendUser $frontendUser */
        $frontendUser = $this->frontendUserRepository->findByIdentifierIncludingDisabled(120);
        self::assertTrue($frontendUser->getDisable());
    }


    /**
     * @test
     * @throws \Exception
     */
    public function validateOptInReturns200IfMatchingTokenYesWithAdminApproval ()
    {
        /**
         * Scenario:
         *
         * Given a persisted frontendUser-object
         * Given this object has a valid value for the email-property set
         * Given this object is disabled
         * Given a persisted optIn-object
         * Given this object has a valid FrontendUserOptInUpdate-property set
         * Given that FrontendUserOptInUpdate-property contains an array with the city- and the zip-property and new values for them
         * Given this object has a valid tokenUser-property
         * Given this object has a valid tokenYes-property
         * Given this object has a valid tokenNo-property
         * Given this object has the adminApproved-property set to 1
         * Given this object has the approved-property set to zero
         * Given a request-object is set
         * Given the frontendUserId-property refers to the frontendUser-object
         * Given setFrontendUserToken has been called before with the valid tokenUser-value as parameter
         * When the method is called with the valid tokenYes-value as parameter
         * Then 200 is returned
         * Then a privacy-object is created
         * Then the optIn-object is marked as deleted
         * Then the approved-property of the optIn-object is set to 1
         * Then the frontendUser is enabled
         * Then the zip- and the city-property of the frontendUser is update to the values of the array in the FrontendUserOptInUpdate-property
         */
        $this->importDataSet(self::FIXTURE_PATH .'/Database/Check70.xml');

        $this->fixture->setFrontendUserToken('test');
        $this->fixture->setRequest(GeneralUtility::makeInstance(Request::class));

        self::assertEquals(200, $this->fixture->validateOptIn('test_yes'));
        self::assertEquals(1, $this->consentRepository->countAll());

        /** @var \Madj2k\FeRegister\Domain\Model\OptIn $optIn */
        $optIn = $this->optInRepository->findByIdentifierIncludingDeleted(70);
        self::assertTrue($optIn->getDeleted());

        /** @var \Madj2k\FeRegister\Domain\Model\OptIn $optIn */
        $optIn = $this->optInRepository->findByIdentifierIncludingDeleted(70);
        self::assertEquals(1,$optIn->getApproved());

        /** @var \Madj2k\FeRegister\Domain\Model\FrontendUser $frontendUser */
        $frontendUser = $this->frontendUserRepository->findByIdentifierIncludingDisabled(70);
        self::assertFalse($frontendUser->getDisable());

        self::assertEquals('Herborn', $frontendUser->getCity());
        self::assertEquals('35745', $frontendUser->getZip());
    }


    /**
     * @test
     * @throws \Exception
     */
    public function validateOptInReturns301IfMatchingTokenYesWithAdminDisapproval ()
    {
        /**
         * Scenario:
         *
         * Given a persisted frontendUser-object
         * Given this object has a valid value for the email-property set
         * Given this object is disabled
         * Given a persisted optIn-object
         * Given this object has a valid tokenUser-property
         * Given this object has a valid tokenYes-property
         * Given this object has a valid tokenNo-property
         * Given this object has the adminApproved-property set to -1
         * Given this object has the approved-property set to zero
         * Given this object is deleted
         * Given a request-object is set
         * Given the frontendUserId-property refers to the frontendUser-object
         * Given setFrontendUserToken has been called before with the valid tokenUser-value as parameter
         * When the method is called with the valid tokenYes-value as parameter
         * Then 301 is returned
         * Then no privacy-object is created
         * Then the approved-property of the optIn-object is still zero
         * Then the frontendUser is still disabled
         */
        $this->importDataSet(self::FIXTURE_PATH .'/Database/Check160.xml');

        $this->fixture->setFrontendUserToken('test');
        $this->fixture->setRequest(GeneralUtility::makeInstance(Request::class));

        self::assertEquals(301, $this->fixture->validateOptIn('test_yes'));
        self::assertEquals(0, $this->consentRepository->countAll());

        /** @var \Madj2k\FeRegister\Domain\Model\OptIn $optIn */
        $optIn = $this->optInRepository->findByIdentifierIncludingDeleted(160);
        self::assertEquals(0, $optIn->getApproved());

        /** @var \Madj2k\FeRegister\Domain\Model\FrontendUser $frontendUser */
        $frontendUser = $this->frontendUserRepository->findByIdentifierIncludingDisabled(160);
        self::assertTrue($frontendUser->getDisable());
    }


    /**
     * @test
     * @throws \Exception
     */
    public function validateOptInReturns200IfMatchingAdminTokenYesWithUserApproval ()
    {
        /**
         * Scenario:
         *
         * Given a persisted frontendUser-object
         * Given this object has a valid value for the email-property set
         * Given this object is disabled
         * Given a persisted optIn-object
         * Given this object has a valid tokenUser-property
         * Given this object has a valid tokenYes-property
         * Given this object has a valid tokenNo-property
         * Given this object has a valid adminTokenYes-property
         * Given this object has a valid adminTokenNo-property
         * Given this object has the adminApproved-property set to zero
         * Given this object has the approved-property set to 1
         * Given a request-object is set
         * Given the frontendUserId-property refers to the frontendUser-object
         * Given setFrontendUserToken has been called before with the valid tokenUser-value as parameter
         * When the method is called with the valid adminTokenYes-value as parameter
         * Then 200 is returned
         * Then no privacy-object is created
         * Then the optIn-object is marked as deleted
         * Then the adminApproved-property of the optIn-object is set to 1
         * Then the frontendUser is enabled
         */
        $this->importDataSet(self::FIXTURE_PATH .'/Database/Check130.xml');

        $this->fixture->setFrontendUserToken('test');
        $this->fixture->setRequest(GeneralUtility::makeInstance(Request::class));

        self::assertEquals(200, $this->fixture->validateOptIn('test_admin_yes'));
        self::assertEquals(0, $this->consentRepository->countAll());

        /** @var \Madj2k\FeRegister\Domain\Model\OptIn $OptIn */
        $optIn = $this->optInRepository->findByIdentifierIncludingDeleted(130);
        self::assertTrue($optIn->getDeleted());

        /** @var \Madj2k\FeRegister\Domain\Model\OptIn $optIn */
        $optIn = $this->optInRepository->findByIdentifierIncludingDeleted(130);
        self::assertEquals(1, $optIn->getAdminApproved());

        /** @var \Madj2k\FeRegister\Domain\Model\FrontendUser $frontendUser */
        $frontendUser = $this->frontendUserRepository->findByIdentifierIncludingDisabled(130);
        self::assertFalse($frontendUser->getDisable());
    }


    /**
     * @test
     * @throws \Exception
     */
    public function validateOptInReturns302IfMatchingAdminTokenYesWithUserDisapproval ()
    {
        /**
         * Scenario:
         *
         * Given a persisted frontendUser-object
         * Given this object has a valid value for the email-property set
         * Given this object is disabled
         * Given a persisted optIn-object
         * Given this object has a valid tokenUser-property
         * Given this object has a valid tokenYes-property
         * Given this object has a valid tokenNo-property
         * Given this object has a valid adminTokenYes-property
         * Given this object has a valid adminTokenNo-property
         * Given this object has the adminApproved-property set to zero
         * Given this object has the approved-property set to -1
         * Given a request-object is set
         * Given the frontendUserId-property refers to the frontendUser-object
         * Given setFrontendUserToken has been called before with the valid tokenUser-value as parameter
         * When the method is called with the valid adminTokenYes-value as parameter
         * Then 302 is returned
         * Then no privacy-object is created
         * Then the adminApproved-property of the optIn-object is still zero
         * Then the frontendUser is still disabled
         */
        $this->importDataSet(self::FIXTURE_PATH .'/Database/Check170.xml');

        $this->fixture->setFrontendUserToken('test');
        $this->fixture->setRequest(GeneralUtility::makeInstance(Request::class));

        self::assertEquals(302, $this->fixture->validateOptIn('test_admin_yes'));
        self::assertEquals(0, $this->consentRepository->countAll());

        /** @var \Madj2k\FeRegister\Domain\Model\OptIn $optIn */
        $optIn = $this->optInRepository->findByIdentifierIncludingDeleted(170);
        self::assertEquals(0, $optIn->getAdminApproved());

        /** @var \Madj2k\FeRegister\Domain\Model\FrontendUser $frontendUser */
        $frontendUser = $this->frontendUserRepository->findByIdentifierIncludingDisabled(170);
        self::assertTrue($frontendUser->getDisable());
    }


    /**
     * @test
     * @throws \Exception
     */
    public function validateOptInReturns300IfMatchingTokenNo ()
    {
        /**
         * Scenario:
         *
         * Given a persisted frontendUser-object
         * Given this object has a valid value for the email-property set
         * Given a persisted optIn-object
         * Given this object has a valid tokenUser
         * Given this object has a valid tokenYes
         * Given this object has a valid tokenNo
         * Given the frontendUserId-property refers to the frontendUser-object
         * Given setFrontendUserToken has been called before with the valid tokenUser-value as parameter
         * When the method is called with the valid tokenNo-value as parameter
         * Then 300 is returned
         * Then no privacy-object is created
         * Then the approved-property of the optIn-object is set to -1
         * Then the optIn-object is marked as deleted
         */
        $this->importDataSet(self::FIXTURE_PATH .'/Database/Check70.xml');

        $this->fixture->setFrontendUserToken('test');
        self::assertEquals(300, $this->fixture->validateOptIn('test_no'));
        self::assertEquals(0, $this->consentRepository->countAll());

        /** @var \Madj2k\FeRegister\Domain\Model\OptIn $OptIn */
        $optIn = $this->optInRepository->findByIdentifierIncludingDeleted(70);

        self::assertEquals(-1, $optIn->getApproved());
        self::assertTrue($optIn->getDeleted());

    }


    /**
     * @test
     * @throws \Exception
     */
    public function validateOptInReturns301IfMatchingTokenNoWithAdminDisapproval ()
    {
        /**
         * Scenario:
         *
         * Given a persisted frontendUser-object
         * Given this object has a valid value for the email-property set
         * Given a persisted optIn-object
         * Given this object is marked as deleted
         * Given this object has a valid tokenUser
         * Given this object has a valid tokenYes
         * Given this object has a valid tokenNo
         * Given this object has the adminApproved-property set to  -1
         * Given this object has the approved-property set to zero
         * Given the frontendUserId-property refers to the frontendUser-object
         * Given setFrontendUserToken has been called before with the valid tokenUser-value as parameter
         * When the method is called with the valid tokenNo-value as parameter
         * Then 301 is returned
         * Then no privacy-object is created
         * Then the approved-property of the optIn-object is still zero
         */
        $this->importDataSet(self::FIXTURE_PATH .'/Database/Check190.xml');

        $this->fixture->setFrontendUserToken('test');
        self::assertEquals(301, $this->fixture->validateOptIn('test_no'));
        self::assertEquals(0, $this->consentRepository->countAll());

        /** @var \Madj2k\FeRegister\Domain\Model\OptIn $OptIn */
        $optIn = $this->optInRepository->findByIdentifierIncludingDeleted(190);

        self::assertEquals(0, $optIn->getApproved());

    }


    /**
     * @test
     * @throws \Exception
     */
    public function validateOptInReturns300IfMatchingAdminTokenNo ()
    {
        /**
         * Scenario:
         *
         * Given a persisted frontendUser-object
         * Given this object has a valid value for the email-property set
         * Given a persisted optIn-object
         * Given this object has a valid tokenUser
         * Given this object has a valid tokenYes
         * Given this object has a valid tokenNo
         * Given the frontendUserId-property refers to the frontendUser-object
         * Given setFrontendUserToken has been called before with the valid tokenUser-value as parameter
         * When the method is called with the valid adminTokenNo-value as parameter
         * Then 300 is returned
         * Then no privacy-object is created
         * Then the adminApproved-property of the optIn-object is set to -1
         * Then the optIn-object is marked as deleted
         */
        $this->importDataSet(self::FIXTURE_PATH .'/Database/Check120.xml');

        $this->fixture->setFrontendUserToken('test');
        self::assertEquals(300, $this->fixture->validateOptIn('test_admin_no'));
        self::assertEquals(0, $this->consentRepository->countAll());

        /** @var \Madj2k\FeRegister\Domain\Model\OptIn $OptIn */
        $optIn = $this->optInRepository->findByIdentifierIncludingDeleted(120);

        self::assertEquals(-1, $optIn->getAdminApproved());
        self::assertTrue($optIn->getDeleted());

    }


    /**
     * @test
     * @throws \Exception
     */
    public function validateOptInReturns302IfMatchingAdminTokenNoWithUserDisapproval ()
    {
        /**
         * Scenario:
         *
         * Given a persisted frontendUser-object
         * Given this object has a valid value for the email-property set
         * Given a persisted optIn-object
         * Given this object is marked as deleted
         * Given this object has a valid tokenUser
         * Given this object has a valid tokenYes
         * Given this object has a valid tokenNo
         * Given this object has the adminApproved-property set to zero
         * Given this object has the approved-property set to -1
         * Given the frontendUserId-property refers to the frontendUser-object
         * Given setFrontendUserToken has been called before with the valid tokenUser-value as parameter
         * When the method is called with the valid adminTokenNo-value as parameter
         * Then 302 is returned
         * Then no privacy-object is created
         * Then the adminApproved-property of the optIn-object is still zero
         */
        $this->importDataSet(self::FIXTURE_PATH .'/Database/Check180.xml');

        $this->fixture->setFrontendUserToken('test');
        self::assertEquals(302, $this->fixture->validateOptIn('test_admin_no'));
        self::assertEquals(0, $this->consentRepository->countAll());

        /** @var \Madj2k\FeRegister\Domain\Model\OptIn $OptIn */
        $optIn = $this->optInRepository->findByIdentifierIncludingDeleted(180);

        self::assertEquals(0, $optIn->getAdminApproved());
    }


    /**
     * @test
     * @throws \Exception
     */
    public function validateOptInReturns399IfMatchingTokenNoOnDeleted ()
    {
        /**
         * Scenario:
         *
         * Given a persisted frontendUser-object
         * Given this object has a valid value for the email-property set
         * Given a persisted optIn-object
         * Given this object has a valid tokenUser
         * Given this object has a valid tokenYes
         * Given this object has a valid tokenNo
         * Given this object is deleted
         * Given the frontendUserId-property refers to the frontendUser-object
         * Given setFrontendUserToken has been called before with the valid tokenUser-value as parameter
         * When the method is called with the valid tokenNo-value as parameter
         * Then 399 is returned
         * Then no privacy-object is created
         */
        $this->importDataSet(self::FIXTURE_PATH .'/Database/Check90.xml');

        $this->fixture->setFrontendUserToken('test');
        self::assertEquals(399, $this->fixture->validateOptIn('test_no'));
        self::assertEquals(0, $this->consentRepository->countAll());
    }


    #==============================================================================

    /**
     * Not relevant any more
     * @throws \Exception
     */
    public function completeRegistrationThrowsExceptionIfNoFrontendUserPersisted ()
    {
        /**
         * Scenario:
         *
         * Given a non-persisted frontendUser-object
         * Given this object has a valid value for the email-property set
         * Given setFrontendUser has been called with this frontendUser-object as parameter
         * When the method is called
         * Then the exception is an instance of \Madj2k\FeRegister\Exception
         * Then the exception has the code 1660814408
         */
        static::expectException(\Madj2k\FeRegister\Exception::class);
        static::expectExceptionCode(1660814408);

        $frontendUser = GeneralUtility::makeInstance(FrontendUser::class);
        $frontendUser->setEmail('test@testen.de');

        $this->fixture->setFrontendUser($frontendUser);
        $this->fixture->completeRegistration();

    }


    /**
     * @test
     * @throws \Exception
     */
    public function completeRegistrationReturnsFalseIfFrontendUserEnabled ()
    {
        /**
         * Scenario:
         *
         * Given a persisted frontendUser-object
         * Given that frontendUser is not disabled
         * Given this object has a valid value for the email-property set
         * Given setFrontendUser has been called with this frontendUser-object as parameter
         * When the method is called
         * Then false is returned
         */
        $this->importDataSet(self::FIXTURE_PATH .'/Database/Check10.xml');

        /** @var \Madj2k\FeRegister\Domain\Model\FrontendUser $frontendUser */
        $frontendUser = $this->frontendUserRepository->findByIdentifier(10);

        $this->fixture->setFrontendUser($frontendUser);
        self::assertFalse($this->fixture->completeRegistration());

    }


    /**
     * @test
     * @throws \Exception
     */
    public function completeRegistrationReturnsTrueIfFrontendUserDisabled ()
    {
        /**
         * Scenario:
         *
         * Given a persisted frontendUser-object
         * Given that frontendUser is disabled
         * Given that frontendUser has a valid value for the email-property set
         * Given a persisted optIn-object
         * Given that optIn-object belongs to the frontendUser
         * Given setFrontendUser has been called with this frontendUser-object as parameter
         * When the method is called
         * Then true is returned
         * Then the disable-property is set to 0
         * Then the tempPlaintextPassword-property is not empty
         * Then the password-property is not empty
         * Then the endtime-property is set to the configured value
         * The frontendUser-groups are set to the configured value
         *
         */
        $this->importDataSet(self::FIXTURE_PATH .'/Database/Check20.xml');

        /** @var \Madj2k\FeRegister\Domain\Model\FrontendUser $frontendUser */
        $frontendUser = $this->frontendUserRepository->findByIdentifierIncludingDisabled(20);

        $this->fixture->setFrontendUser($frontendUser);
        self::assertTrue($this->fixture->completeRegistration());

        $frontendUserResult = $this->fixture->getFrontendUserPersisted();
        self::assertEquals(0, $frontendUserResult->getDisable());
        self::assertNotEmpty($frontendUserResult->getTempPlaintextPassword());
        self::assertNotEmpty($frontendUserResult->getPassword());
        self::assertGreaterThan(time(), $frontendUserResult->getEndtime());
        self::assertLessThanOrEqual(time() + 10000, $frontendUserResult->getEndtime());
        self::assertEquals(2, $frontendUserResult->getUsergroup()->count());

        $usergroups = $frontendUserResult->getUsergroup()->toArray();
        self::assertEquals(20, $usergroups[0]->getUid());
        self::assertEquals(21, $usergroups[1]->getUid());

    }


    /**
     * @test
     * @throws \Exception
     */
    public function completeRegistrationReturnsTrueAndUpdatesRawFrontendUser ()
    {
        /**
         * Scenario:
         *
         * Given a persisted frontendUser-object
         * Given that frontendUser is disabled
         * Given the email-address of the object has been changed during object-lifetime without persisting
         * Given this object has a valid value for the email-property set
         * Given setFrontendUser has been called with this frontendUser-object as parameter
         * When the method is called
         * Then true is returned
         * Then getFrontendUser returns the same object as getFrontendUserPersisted
         * Then the changed email-address is dismissed
         */
        $this->importDataSet(self::FIXTURE_PATH .'/Database/Check20.xml');

        /** @var \Madj2k\FeRegister\Domain\Model\FrontendUser $frontendUser */
        $frontendUser = $this->frontendUserRepository->findByIdentifierIncludingDisabled(20);
        $frontendUser->setEmail('merz@cdu.de');

        $this->fixture->setFrontendUser($frontendUser);

        self::assertTrue($this->fixture->completeRegistration());

        $persistenceManager = $this->objectManager->get(PersistenceManager::class);
        $persistenceManager->clearState();

        $frontendUserPersisted = $this->fixture->getFrontendUserPersisted();
        $frontendUserRaw = $this->fixture->getFrontendUser();

        self::assertSame($frontendUserPersisted, $frontendUserRaw);
    }

    #==============================================================================

    /**
     * @test
     * @throws \Exception
     */
    public function cancelRegistrationThrowsExceptionIfNoFrontendUserPersisted ()
    {
        /**
         * Scenario:
         *
         * Given a non-persisted frontendUser-object
         * Given this object has a valid value for the email-property set
         * Given setFrontendUser has been called with this frontendUser-object as parameter
         * When the method is called
         * Then the exception is an instance of \Madj2k\FeRegister\Exception
         * Then the exception has the code 1660914940
         */
        static::expectException(\Madj2k\FeRegister\Exception::class);
        static::expectExceptionCode(1660914940);

        $frontendUser = GeneralUtility::makeInstance(FrontendUser::class);
        $frontendUser->setEmail('test@testen.de');

        $this->fixture->setFrontendUser($frontendUser);
        $this->fixture->cancelRegistration();

    }


    /**
     * @test
     * @throws \Exception
     */
    public function cancelRegistrationReturnsFalseIfFrontendUserEnabled ()
    {
        /**
         * Scenario:
         *
         * Given a persisted frontendUser-object
         * Given that frontendUser is not disabled
         * Given this object has a valid value for the email-property set
         * Given setFrontendUser has been called with this frontendUser-object as parameter
         * When the method is called
         * Then false is returned
         */
        $this->importDataSet(self::FIXTURE_PATH .'/Database/Check10.xml');

        /** @var \Madj2k\FeRegister\Domain\Model\FrontendUser $frontendUser */
        $frontendUser = $this->frontendUserRepository->findByIdentifier(10);

        $this->fixture->setFrontendUser($frontendUser);
        self::assertFalse($this->fixture->cancelRegistration());

    }


    /**
     * @test
     * @throws \Exception
     */
    public function cancelRegistrationReturnsTrueIfFrontendUserDisabled ()
    {
        /**
         * Scenario:
         *
         * Given a persisted frontendUser-object
         * Given that frontendUser is disabled
         * Given this object has a valid value for the email-property set
         * Given setFrontendUser has been called with this frontendUser-object as parameter
         * When the method is called
         * Then true is returned
         * Then the user is removed from the database completely
         */
        $this->importDataSet(self::FIXTURE_PATH .'/Database/Check20.xml');

        /** @var \Madj2k\FeRegister\Domain\Model\FrontendUser $frontendUser */
        $frontendUser = $this->frontendUserRepository->findByIdentifierIncludingDisabled(20);

        $this->fixture->setFrontendUser($frontendUser);
        self::assertTrue($this->fixture->cancelRegistration());

        $persistenceManager = $this->objectManager->get(PersistenceManager::class);
        $persistenceManager->clearState();

        /** @var \Madj2k\FeRegister\Domain\Model\FrontendUser $frontendUser */
        $frontendUser = $this->frontendUserRepository->findByIdentifierIncludingDeleted(20);
        self::assertNull($frontendUser);
    }


    /**
     * @test
     * @throws \Exception
     */
    public function cancelRegistrationReturnsTrueAndUpdatesRawFrontendUser ()
    {
        /**
         * Scenario:
         *
         * Given a persisted frontendUser-object
         * Given that frontendUser is disabled
         * Given the email-address of the object has been changed during object-lifetime without persisting
         * Given this object has a valid value for the email-property set
         * Given setFrontendUser has been called with this frontendUser-object as parameter
         * When the method is called
         * Then true is returned
         * Then getFrontendUser returns the same object as getFrontendUserPersisted
         * Then the changed email-address is dismissed
         */
        $this->importDataSet(self::FIXTURE_PATH .'/Database/Check20.xml');

        /** @var \Madj2k\FeRegister\Domain\Model\FrontendUser $frontendUser */
        $frontendUser = $this->frontendUserRepository->findByIdentifierIncludingDisabled(20);
        $frontendUser->setEmail('merz@cdu.de');

        $this->fixture->setFrontendUser($frontendUser);

        self::assertTrue($this->fixture->cancelRegistration());

        $persistenceManager = $this->objectManager->get(PersistenceManager::class);
        $persistenceManager->clearState();

        $frontendUserPersisted = $this->fixture->getFrontendUserPersisted();
        $frontendUserRaw = $this->fixture->getFrontendUser();

        self::assertSame($frontendUserPersisted, $frontendUserRaw);
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
         * Given a non-persisted frontendUser-object
         * Given this object has a valid value for the email-property set
         * Given setFrontendUser has been called with this frontendUser-object as parameter
         * When the method is called
         * Then the exception is an instance of \Madj2k\FeRegister\Exception
         * Then the exception has the code 1661163918
         */
        static::expectException(\Madj2k\FeRegister\Exception::class);
        static::expectExceptionCode(1661163918);

        $frontendUser = GeneralUtility::makeInstance(FrontendUser::class);
        $frontendUser->setEmail('test@testen.de');

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
         * Given a persisted frontendUser
         * Given this frontendUser is not disabled
         * Given a persisted userGroup
         * Given simulateLogin has been called with both as parameters before
         * Given simulateLogin has returned true
         * When the method is called
         * Then true is returned
         * Then the frontendUser is logged out
         */

        $this->importDataSet(self::FIXTURE_PATH . '/Database/Check30.xml');

        /** @var \Madj2k\FeRegister\Domain\Model\FrontendUser $frontendUser */
        $frontendUser = $this->frontendUserRepository->findByUid(30);

        /** @var \Madj2k\FeRegister\Domain\Model\FrontendUserGroup $frontendUserGroup */
        $frontendUserGroup = $this->frontendUserGroupRepository->findByUid(30);

        FrontendSimulatorUtility::simulateFrontendEnvironment(1);
        self::assertTrue(FrontendUserSessionUtility::simulateLogin($frontendUser, $frontendUserGroup));
        $this->fixture->setFrontendUser($frontendUser);

        self::assertTrue($this->fixture->endRegistration());
        self::assertFalse(FrontendUserSessionUtility::isUserLoggedIn($frontendUser));

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
         * Given a persisted frontendUser
         * Given this frontendUser is not disabled
         * Given a persisted userGroup
         * When the method is called
         * Then true is returned
         * Then the frontendUser is marked as deleted
         */

        $this->importDataSet(self::FIXTURE_PATH . '/Database/Check10.xml');

        /** @var \Madj2k\FeRegister\Domain\Model\FrontendUser $frontendUser */
        $frontendUser = $this->frontendUserRepository->findByUid(10);

        $this->fixture->setFrontendUser($frontendUser);
        self::assertTrue($this->fixture->endRegistration());

        $persistenceManager = $this->objectManager->get(PersistenceManager::class);
        $persistenceManager->clearState();

        /** @var \Madj2k\FeRegister\Domain\Model\FrontendUser $frontendUser */
        $frontendUser = $this->frontendUserRepository->findByIdentifierIncludingDeleted(10);
        self::assertTrue($frontendUser->getDeleted());

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
         * Given a persisted frontendUser
         * Given this frontendUser is disabled
         * Given a persisted userGroup
         * When the method is called
         * Then false is returned
         * Then the frontendUser is not marked as deleted
         */

        $this->importDataSet(self::FIXTURE_PATH . '/Database/Check20.xml');

        /** @var \Madj2k\FeRegister\Domain\Model\FrontendUser $frontendUser */
        $frontendUser = $this->frontendUserRepository->findByIdentifierIncludingDisabled(20);

        $this->fixture->setFrontendUser($frontendUser);
        self::assertFalse($this->fixture->endRegistration());

        $persistenceManager = $this->objectManager->get(PersistenceManager::class);
        $persistenceManager->clearState();

        /** @var \Madj2k\FeRegister\Domain\Model\FrontendUser $frontendUser */
        $frontendUser = $this->frontendUserRepository->findByIdentifierIncludingDeleted(20);
        self::assertFalse($frontendUser->getDeleted());

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
         * Given a persisted frontendUser-object
         * Given that frontendUser is not disabled
         * Given the email-address of the object has been changed during object-lifetime without persisting
         * Given this object has a valid value for the email-property set
         * Given setFrontendUser has been called with this frontendUser-object as parameter
         * When the method is called
         * Then true is returned
         * Then the frontendUser is marked as deleted
         * Then getFrontendUser returns the same object as getFrontendUserPersisted
         * Then the changed email-address is dismissed
         */
        $this->importDataSet(self::FIXTURE_PATH . '/Database/Check10.xml');

        /** @var \Madj2k\FeRegister\Domain\Model\FrontendUser $frontendUser */
        $frontendUser = $this->frontendUserRepository->findByUid(10);
        $frontendUser->setEmail('merz@cdu.de');

        $this->fixture->setFrontendUser($frontendUser);
        self::assertTrue($this->fixture->endRegistration());

        $persistenceManager = $this->objectManager->get(PersistenceManager::class);
        $persistenceManager->clearState();

        /** @var \Madj2k\FeRegister\Domain\Model\FrontendUser $frontendUser */
        $frontendUser = $this->frontendUserRepository->findByIdentifierIncludingDeleted(10);
        self::assertTrue($frontendUser->getDeleted());

        $frontendUserPersisted = $this->fixture->getFrontendUserPersisted();
        $frontendUserRaw = $this->fixture->getFrontendUser();

        self::assertSame($frontendUserPersisted, $frontendUserRaw);
    }

    #==============================================================================

    /**
     * TearDown
     */
    protected function teardown(): void
    {
        FrontendSimulatorUtility::resetFrontendEnvironment();

        parent::tearDown();
    }

}
