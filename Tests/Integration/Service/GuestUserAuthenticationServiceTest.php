<?php
namespace Madj2k\FeRegister\Tests\Integration\Service;

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

use Madj2k\FeRegister\Domain\Repository\GuestUserRepository;
use Nimut\TestingFramework\TestCase\FunctionalTestCase;
use Madj2k\CoreExtended\Utility\FrontendSimulatorUtility;
use Madj2k\FeRegister\Controller\AuthGuestController;
use Madj2k\FeRegister\Domain\Repository\FrontendUserRepository;
use Madj2k\FeRegister\Registration\AbstractRegistration;
use Madj2k\FeRegister\Utility\ClientUtility;
use Madj2k\FeRegister\Utility\FrontendUserSessionUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Object\ObjectManager;
use TYPO3\CMS\Extbase\Persistence\Generic\PersistenceManager;
use TYPO3\CMS\Frontend\Authentication\FrontendUserAuthentication;

/**
 * GuestUserAuthenticationServiceTest
 *
 * @author Steffen Kroggel <developer@steffenkroggel.de>
 * @copyright Steffen Kroggel
 * @package Madj2k_FeRegister
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License, version 3 or later
 */
class GuestUserAuthenticationServiceTest extends FunctionalTestCase
{
    /**
     * @const
     */
    const FIXTURE_PATH = __DIR__ . '/GuestUserAuthenticationService/Fixtures';


    /**
     * @var string[]
     */
    protected $coreExtensionsToLoad = [
        'saltedpasswords',
        'filemetadata',
        'extensionmanager'
    ];


    /**
     * @var string[]
     */
    protected $testExtensionsToLoad = [
        'typo3conf/ext/ajax_api',
        'typo3conf/ext/core_extended',
        'typo3conf/ext/accelerator',
        'typo3conf/ext/postmaster',
        'typo3conf/ext/fe_register',
        'typo3conf/ext/persisted_sanitized_routing',
        'typo3conf/ext/sr_freecap'
    ];


    /**
     * @var \TYPO3\CMS\Extbase\Object\ObjectManager|null
     */
    private ?ObjectManager $objectManager = null;


    /**
     * @var \Madj2k\FeRegister\Domain\Repository\FrontendUserRepository|null
     */
    private ?FrontendUserRepository $frontendUserRepository = null;


    /**
     * @var \Madj2k\FeRegister\Domain\Repository\GuestRepository|null
     */
    private ?GuestUserRepository $guestUserRepository = null;


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
            ]
        );

        FrontendSimulatorUtility::simulateFrontendEnvironment(1);

        /** @var \TYPO3\CMS\Extbase\Object\ObjectManager $objectManager */
        $this->objectManager = GeneralUtility::makeInstance(ObjectManager::class);

        /** @var \Madj2k\FeRegister\Domain\Repository\FrontendUserRepository frontendUserRepository */
        $this->frontendUserRepository = $this->objectManager->get(FrontendUserRepository::class);

        /** @var \Madj2k\FeRegister\Domain\Repository\GuestUserRepository guestUserRepository */
        $this->guestUserRepository = $this->objectManager->get(GuestUserRepository::class);

    }

    #==============================================================================

    /**
     * @test
     * @throws \Exception
     */
    public function itIgnoresNormalFrontendUser ()
    {
        /**
         * Scenario:
         *
         * Given a persisted frontendUser
         * Given that frontendUser is enabled
         * Given that frontendUser has a random string as username
         * Given that username matches AbstractRegistration::RANDOM_STRING_LENGTH
         * Given that frontendUser is NOT an instance of \Madj2k\FeRegister\Domain\Model\GuestUser
         * When the frontendUser is logging in using only the username
         * Then the login fails
         * Then no login-session is generated
         */
        $this->importDataSet(self::FIXTURE_PATH .'/Database/Check10.xml');

        /** @var \Madj2k\FeRegister\Domain\Model\FrontendUser $frontendUser */
        $frontendUser = $this->frontendUserRepository->findByIdentifier(10);

        $_POST['logintype'] = 'login';
        $_POST['user'] = $frontendUser->getUsername();
        $_POST['pass'] = '';

        $authService = GeneralUtility::makeInstance(FrontendUserAuthentication::class);
        $authService->start();

        self::assertTrue($authService->loginFailure);
        self::assertFalse($authService->loginSessionStarted);

        $persistenceManager = $this->objectManager->get(PersistenceManager::class);
        $persistenceManager->clearState();

    }


    /**
     * @test
     * @throws \Exception
     */
    public function itIgnoresGuestUserWithEmailAsUsername ()
    {
        /**
         * Scenario:
         *
         * Given a persisted frontendUser
         * Given that frontendUser is enabled
         * Given that frontendUser has an email-address as username
         * Given that frontendUser IS an instance of \Madj2k\FeRegister\Domain\Model\GuestUser
         * When the frontendUser is logging in using only the username
         * Then the login fails
         * Then no login-session is generated
         */
        $this->importDataSet(self::FIXTURE_PATH .'/Database/Check20.xml');

        /** @var \Madj2k\FeRegister\Domain\Model\GuestUser $guestUser */
        $guestUser = $this->guestUserRepository->findByIdentifier(20);

        $_POST['logintype'] = 'login';
        $_POST['user'] = $guestUser->getUsername();
        $_POST['pass'] = '';

        $authService = GeneralUtility::makeInstance(FrontendUserAuthentication::class);
        $authService->start();

        self::assertTrue($authService->loginFailure);
        self::assertFalse($authService->loginSessionStarted);

        $persistenceManager = $this->objectManager->get(PersistenceManager::class);
        $persistenceManager->clearState();

    }


    /**
     * @test
     * @throws \Exception
     */
    public function itIgnoresGuestUserWithUsernameToShort ()
    {
        /**
         * Scenario:
         *
         * Given a persisted frontendUser
         * Given that frontendUser is enabled
         * Given that frontendUser has a random string as username
         * Given that username does not match AbstractRegistration::RANDOM_STRING_LENGTH
         * Given that frontendUser IS an instance of \Madj2k\FeRegister\Domain\Model\GuestUser
         * When the frontendUser is logging in using only the username
         * Then the login fails
         * Then no login-session is generated
         */
        $this->importDataSet(self::FIXTURE_PATH .'/Database/Check30.xml');

        /** @var \Madj2k\FeRegister\Domain\Model\GuestUser $guestUser */
        $guestUser = $this->guestUserRepository->findByIdentifier(30);

        $_POST['logintype'] = 'login';
        $_POST['user'] = $guestUser->getUsername();
        $_POST['pass'] = '';

        $authService = GeneralUtility::makeInstance(FrontendUserAuthentication::class);
        $authService->start();

        self::assertTrue($authService->loginFailure);
        self::assertFalse($authService->loginSessionStarted);

        $persistenceManager = $this->objectManager->get(PersistenceManager::class);
        $persistenceManager->clearState();

    }


    /**
     * @test
     * @throws \Exception
     */
    public function itLogsInGuestUser ()
    {
        /**
         * Scenario:
         *
         * Given a persisted frontendUser
         * Given that frontendUser is enabled
         * Given that frontendUser has a random string as username
         * Given that username matches AbstractRegistration::RANDOM_STRING_LENGTH
         * Given that frontendUser is instance of \Madj2k\FeRegister\Domain\Model\GuestUser
         * When the frontendUser is logging in using only the username
         * Then the login succeeds
         * Then a login-session is generated
         */
        $this->importDataSet(self::FIXTURE_PATH .'/Database/Check40.xml');

        /** @var \Madj2k\FeRegister\Domain\Model\GuestUser $guestUser */
        $guestUser = $this->guestUserRepository->findByIdentifier(40);

        $_POST['logintype'] = 'login';
        $_POST['user'] = $guestUser->getUsername();
        $_POST['pass'] = '';

        $authService = GeneralUtility::makeInstance(FrontendUserAuthentication::class);
        $authService->start();

        self::assertFalse($authService->loginFailure);
        self::assertTrue($authService->loginSessionStarted);

        $persistenceManager = $this->objectManager->get(PersistenceManager::class);
        $persistenceManager->clearState();

    }

    #==============================================================================

    /**
     * TearDown
     */
    protected function teardown(): void
    {
        parent::tearDown();

        FrontendSimulatorUtility::resetFrontendEnvironment();

    }

}
