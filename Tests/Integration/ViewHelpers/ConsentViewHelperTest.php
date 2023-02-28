<?php
namespace Madj2k\FeRegister\Tests\Integration\ViewHelpers;

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
use TYPO3\CMS\Extbase\Object\ObjectManager;
use TYPO3\CMS\Fluid\View\StandaloneView;

/**
 * Class ConsentViewHelper
 *
 * @author Maximilian Fäßler <maximilian@faesslerweb.de>
 * @copyright Steffen Kroggel
 * @package Madj2k_FeRegister
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License, version 3 or later
 */
class ConsentViewHelperTest extends FunctionalTestCase
{
    /**
     * @const
     */
    const FIXTURE_PATH = __DIR__ . '/ConsentViewHelperTest/Fixtures';


    /**
     * @var string[]
     */
    protected $testExtensionsToLoad = [
        'typo3conf/ext/ajax_api',
        'typo3conf/ext/core_extended',
        'typo3conf/ext/fe_register'
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
                'EXT:fe_register/Configuration/TypoScript/setup.txt',
                self::FIXTURE_PATH . '/Frontend/Configuration/Rootpage.typoscript',
            ],
            ['example.com' => self::FIXTURE_PATH .  '/Frontend/Configuration/config.yaml']
        );

        FrontendSimulatorUtility::simulateFrontendEnvironment(1);

        /** @var \TYPO3\CMS\Extbase\Object\ObjectManager $objectManager */
        $this->objectManager = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(ObjectManager::class);

        $this->standAloneViewHelper = $this->objectManager->get(StandaloneView::class);
        $this->standAloneViewHelper->setTemplateRootPaths(
            [
                0 => self::FIXTURE_PATH . '/Frontend/Templates'
            ]
        );

    }

    #==============================================================================

    /**
     * @test
     */
    public function itRendersMarketingTemplate ()
    {

        /**
         * Scenario:
         *
         * Given this ViewHelper is used in a template
         * Given the type-parameter is set to the value 'marketing'
         * When it is rendered
         * Then the checkbox for the marketing consent is rendered
         * Then the checkbox uses the namespace tx_feregister
         */

        $this->standAloneViewHelper->setTemplate('Check10.html');
        $result = trim($this->standAloneViewHelper->render());

        self::assertStringContainsString('tx_feregister[marketing]', $result);
    }


    /**
     * @test
     */
    public function itRendersTermsTemplate ()
    {

        /**
         * Scenario:
         *
         * Given this ViewHelper is used in a template
         * Given the type-parameter is set to the value 'terms'
         * When it is rendered
         * Then the checkbox for the marketing consent is rendered
         * Then the checkbox uses the namespace tx_feregister
         */

        $this->standAloneViewHelper->setTemplate('Check20.html');
        $result = trim($this->standAloneViewHelper->render());

        self::assertStringContainsString('tx_feregister[terms]', $result);
    }


    /**
     * @test
     */
    public function itRendersPrivacyTemplate ()
    {

        /**
         * Scenario:
         *
         * Given this ViewHelper is used in a template
         * Given the type-parameter is set to the value 'privacy'
         * When it is rendered
         * Then the checkbox for the marketing consent is rendered
         * Then the checkbox uses the namespace tx_feregister
         */

        $this->standAloneViewHelper->setTemplate('Check30.html');
        $result = trim($this->standAloneViewHelper->render());

        self::assertStringContainsString('tx_feregister[privacy]', $result);
    }


    /**
     * @test
     */
    public function itRendersTextAccordingToGivenKey ()
    {

        /**
         * Scenario:
         *
         * Given this ViewHelper is used in a template
         * Given the type-parameter is set to the value 'terms'
         * Given the key-parameter is set to the value 'events'
         * When it is rendered
         * Then the text for events is rendered
         */

        $this->standAloneViewHelper->setTemplate('Check40.html');
        $result = trim($this->standAloneViewHelper->render());

        self::assertStringContainsString('We would like to point out that images, sound and video recordings may be made at this event', $result);
    }


    /**
     * @test
     */
    public function itRendersLinks ()
    {

        /**
         * Scenario:
         *
         * Given this ViewHelper is used in a template
         * Given the type-parameter is set to the value 'terms'
         * Given the key-parameter is set to the value 'events'
         * When it is rendered
         * Then both links to the terms are rendered
         */

        $this->standAloneViewHelper->setTemplate('Check40.html');
        $result = trim($this->standAloneViewHelper->render());

        self::assertStringContainsString('/terms-1/', $result);
        self::assertStringContainsString('/terms-2/', $result);

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
