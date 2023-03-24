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

use Nimut\TestingFramework\TestCase\FunctionalTestCase;
use Madj2k\CoreExtended\Utility\FrontendSimulatorUtility;
use Madj2k\FeRegister\Domain\Repository\FrontendUserRepository;
use Madj2k\FeRegister\Utility\ClientUtility;
use Madj2k\FeRegister\Utility\FrontendUserSessionUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Object\ObjectManager;
use TYPO3\CMS\Extbase\Persistence\Generic\PersistenceManager;
use TYPO3\CMS\Frontend\Authentication\FrontendUserAuthentication;

/**
 * FrontendUserAuthenticationServiceTest
 *
 * @author Steffen Kroggel <developer@steffenkroggel.de>
 * @copyright Steffen Kroggel
 * @package Madj2k_FeRegister
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License, version 3 or later
 */
class FrontendUserAuthenticationServiceTest extends FunctionalTestCase
{
    /**
     * @const
     */
    const FIXTURE_PATH = __DIR__ . '/FrontendUserAuthenticationService/Fixtures';

    /**
     * @var string[]
     */
    protected $coreExtensionsToLoad = [
        'saltedpasswords',
        'filemetadata',
        'seo',
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
        'typo3conf/ext/persisted_sanitized_routing'
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

        /** @var \TYPO3\CMS\Extbase\Object\ObjectManager $objectManager */
        $this->objectManager = GeneralUtility::makeInstance(ObjectManager::class);

        /** @var \Madj2k\FeRegister\Domain\Repository\FrontendUserRepository frontendUserRepository */
        $this->frontendUserRepository = $this->objectManager->get(FrontendUserRepository::class);

    }

    #==============================================================================

    /**
     * @test
     * @throws \Exception
     */
    public function itIncrementsLoginErrorCounter ()
    {
        /**
         * Scenario:
         *
         * Given a persisted frontendUser
         * Given that frontendUser is enabled
         * Given the tx_feregister_login_error_count-property of the frontendUser is one
         * When the frontendUser is logging in with wrong credentials
         * Then the login fails
         * Then no login-session is generated
         * Then the tx_feregister_login_error_count-property of the frontendUser is incremented
         */
        $this->importDataSet(self::FIXTURE_PATH .'/Database/Check10.xml');

        FrontendSimulatorUtility::simulateFrontendEnvironment(1);

        /** @var \Madj2k\FeRegister\Domain\Model\FrontendUser $frontendUser */
        $frontendUser = $this->frontendUserRepository->findByIdentifier(10);
        self::assertEquals(1, $frontendUser->getTxFeregisterLoginErrorCount());

        $_POST['logintype'] = 'login';
        $_POST['user'] = $frontendUser->getUsername();
        $_POST['pass'] = 'not-test';

        $authService = GeneralUtility::makeInstance(FrontendUserAuthentication::class);
        $authService->start();

        self::assertTrue($authService->loginFailure);
        self::assertFalse($authService->loginSessionStarted);

        $persistenceManager = $this->objectManager->get(PersistenceManager::class);
        $persistenceManager->clearState();

        /** @var \Madj2k\FeRegister\Domain\Model\FrontendUser $frontendUser */
        $frontendUser = $this->frontendUserRepository->findByIdentifier(10);
        self::assertEquals(2, $frontendUser->getTxFeregisterLoginErrorCount());

        FrontendSimulatorUtility::resetFrontendEnvironment();
    }


    /**
     * @test
     * @throws \Exception
     */
    public function itLogsInUser ()
    {
        /**
         * Scenario:
         *
         * Given a persisted frontendUser
         * Given that frontendUser is enabled
         * Given the tx_feregister_login_error_count-property of the frontendUser is one
         * When the frontendUser is logging in with correct credentials
         * Then the login succeeds
         * Then a login-session is generated
         * Then the tx_feregister_login_error_count-property of the frontendUser is reset
         */
        $this->importDataSet(self::FIXTURE_PATH .'/Database/Check10.xml');

        FrontendSimulatorUtility::simulateFrontendEnvironment(1);

        /** @var \Madj2k\FeRegister\Domain\Model\FrontendUser $frontendUser */
        $frontendUser = $this->frontendUserRepository->findByIdentifier(10);
        self::assertEquals(1, $frontendUser->getTxFeregisterLoginErrorCount());

        $_POST['logintype'] = 'login';
        $_POST['user'] = $frontendUser->getUsername();
        $_POST['pass'] = 'test';

        $authService = GeneralUtility::makeInstance(FrontendUserAuthentication::class);
        $authService->start();

        self::assertFalse($authService->loginFailure);
        self::assertTrue($authService->loginSessionStarted);

        $persistenceManager = $this->objectManager->get(PersistenceManager::class);
        $persistenceManager->clearState();

        /** @var \Madj2k\FeRegister\Domain\Model\FrontendUser $frontendUser */
        $frontendUser = $this->frontendUserRepository->findByIdentifier(10);
        self::assertEquals(0, $frontendUser->getTxFeregisterLoginErrorCount());

        FrontendSimulatorUtility::resetFrontendEnvironment();
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
