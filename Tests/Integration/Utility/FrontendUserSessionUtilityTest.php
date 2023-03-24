<?php
namespace Madj2k\FeRegister\Tests\Integration\Utility;

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

use Exception;
use Madj2k\FeRegister\Domain\Repository\GuestUserRepository;
use Nimut\TestingFramework\TestCase\FunctionalTestCase;
use Madj2k\CoreExtended\Utility\FrontendSimulatorUtility;
use Madj2k\FeRegister\Domain\Model\FrontendUser;
use Madj2k\FeRegister\Domain\Model\GuestUser;
use Madj2k\FeRegister\Domain\Repository\FrontendUserGroupRepository;
use Madj2k\FeRegister\Domain\Repository\FrontendUserRepository;
use Madj2k\FeRegister\Utility\FrontendUserSessionUtility;
use TYPO3\CMS\Core\Context\Context;
use TYPO3\CMS\Core\Context\UserAspect;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Object\ObjectManager;
use TYPO3\CMS\Frontend\Authentication\FrontendUserAuthentication;

/**
 * FrontendUserSessionUtilityTest
 *
 * @author Steffen Kroggel <developer@steffenkroggel.de>
 * @copyright Steffen Kroggel
 * @package Madj2k_FeRegister
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License, version 3 or later
 */
class FrontendUserSessionUtilityTest extends FunctionalTestCase
{

    /**
     * @const
     */
    const FIXTURE_PATH = __DIR__ . '/FrontendUserSessionUtilityTest/Fixtures';


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
        'typo3conf/ext/persisted_sanitized_routing',
        'typo3conf/ext/sr_freecap'
    ];


    /**
     * @var \Madj2k\FeRegister\Domain\Repository\FrontendUserRepository|null
     */
    private ?FrontendUserRepository $frontendUserRepository = null;


    /**
     * @var \Madj2k\FeRegister\Domain\Repository\GuestUserRepository|null
     */
    private ?GuestUserRepository $guestUserRepository = null;


    /**
     * @var \Madj2k\FeRegister\Domain\Repository\FrontendUserGroupRepository|null
     */
    private ?FrontendUserGroupRepository $frontendUserGroupRepository = null;


    /**
     * Setup
     * @throws Exception
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
        $this->frontendUserRepository = $this->objectManager->get(FrontendUserRepository::class);
        $this->guestUserRepository = $this->objectManager->get(GuestUserRepository::class);
        $this->frontendUserGroupRepository = $this->objectManager->get(FrontendUserGroupRepository::class);

        FrontendSimulatorUtility::simulateFrontendEnvironment(1);
    }

    #====================================================================================================

    /**
     * @test
     * @throws \Exception
     */
    public function getLoggedInUserReturnsNullIfNoUserLoggedIn ()
    {

        /**
         * Scenario:
         *
         * Given no user is logged in
         * When the method is called
         * Then zero is returned
         */

        self::assertNull(FrontendUserSessionUtility::getLoggedInUser());
    }


    /**
     * @test
     * @throws \Exception
     */
    public function getLoggedInUserWithNormalUserReturnsUserOfRightType ()
    {

        /**
         * Scenario:
         *
         * Given a persisted frontendUser
         * Given this frontendUser is an instance of \Madj2k\FeRegister\Domain\Model\FrontendUser
         * Given a persisted userGroup
         * Given simulateLogin has been called with both as parameters before
         * Given simulateLogin has returned true
         * When the method is called
         * Then an instance of \Madj2k\FeRegister\Domain\Model\FrontendUser is returned
         * Then the uid of the returned instance is the one of the given frontendUser
         */
        $this->importDataSet(self::FIXTURE_PATH . '/Database/Check10.xml');

        /** @var \Madj2k\FeRegister\Domain\Model\FrontendUser $frontendUser */
        $frontendUser = $this->frontendUserRepository->findByUid(10);

        /** @var \Madj2k\FeRegister\Domain\Model\FrontendUserGroup $frontendUserGroup */
        $frontendUserGroup = $this->frontendUserGroupRepository->findByUid(10);

        self::assertTrue(FrontendUserSessionUtility::simulateLogin($frontendUser, $frontendUserGroup));

        /** @var \Madj2k\FeRegister\Domain\Model\FrontendUser $frontendUser */
        $result = FrontendUserSessionUtility::getLoggedInUser();
        self::assertInstanceOf(FrontendUser::class, $result);
        self::assertEquals($frontendUser->getUid(), $result->getUid());
    }


    /**
     * @test
     * @throws \Exception
     */
    public function getLoggedInUserWithGuestUserReturnsUserOfRightType ()
    {

        /**
         * Scenario:
         *
         * Given a persisted frontendUser
         * Given this frontendUser is an instance of \Madj2k\FeRegister\Domain\Model\GuestUser
         * Given a persisted userGroup
         * Given simulateLogin has been called with both as parameters before
         * Given simulateLogin has returned true
         * When the method is called
         * Then an instance of \Madj2k\FeRegister\Domain\Model\GuestUser is returned
         * Then the uid of the returned instance is the one of the given frontendUser
         */
        $this->importDataSet(self::FIXTURE_PATH . '/Database/Check20.xml');

        /** @var \Madj2k\FeRegister\Domain\Model\GuestUser $frontendUser */
        $frontendUser = $this->guestUserRepository->findByUid(20);

        /** @var \Madj2k\FeRegister\Domain\Model\FrontendUserGroup $frontendUserGroup */
        $frontendUserGroup = $this->frontendUserGroupRepository->findByUid(20);

        self::assertTrue(FrontendUserSessionUtility::simulateLogin($frontendUser, $frontendUserGroup));

        /** @var \Madj2k\FeRegister\Domain\Model\GuestUser $frontendUser */
        $result = FrontendUserSessionUtility::getLoggedInUser();
        self::assertInstanceOf(GuestUser::class, $result);
        self::assertEquals($frontendUser->getUid(), $result->getUid());
    }

    #====================================================================================================

    /**
     * tearDown
     */
    protected function teardown(): void
    {
        parent::tearDown();
    }

}
