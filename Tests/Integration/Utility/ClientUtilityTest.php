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

use Nimut\TestingFramework\TestCase\FunctionalTestCase;
use Madj2k\CoreExtended\Utility\FrontendSimulatorUtility;
use Madj2k\FeRegister\Utility\ClientUtility;

/**
 * ClientUtilityTest
 *
 * @author Maximilian Fäßler <maximilian@faesslerweb.de>
 * @author Steffen Kroggel <developer@steffenkroggel.de>
 * @copyright Steffen Kroggel
 * @package Madj2k_FeRegister
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License, version 3 or later
 */
class ClientUtilityTest extends FunctionalTestCase
{
    /**
     * @const
     */
    const FIXTURE_PATH = __DIR__ . '/ClientUtilityTest/Fixtures';


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

        FrontendSimulatorUtility::simulateFrontendEnvironment(1);

    }

    #==============================================================================

    /**
     * @test
     */
    public function isReferrerPidValidReturnsFalse ()
    {
        /**
         * Scenario:
         *
         * Given a pid as referrer that does not exist
         * When the method is called
         * Then false is returned
         */

        self::assertFalse(ClientUtility::isReferrerPidValid(999));
    }


    /**
     * @test
     */
    public function isReferrerPidValidReturnsTrue ()
    {
        /**
         * Scenario:
         *
         * Given a pid as referrer that does exist
         * When the method is called
         * Then true is returned
         */

        self::assertTrue(ClientUtility::isReferrerPidValid(2));
    }


    /**
     * @test
     */
    public function isReferrerPidValidReturnsTrueAndIgnoresAccess ()
    {
        /**
         * Scenario:
         *
         * Given a pid as referrer that does exist
         * Given that pid is restricted to logged in users
         * When the method is called
         * Then true is returned
         */

        self::assertTrue(ClientUtility::isReferrerPidValid(3));
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
