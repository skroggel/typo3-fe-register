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
use Madj2k\FeRegister\ViewHelpers\GetAllFlashMessageIdentifierViewHelper;
use TYPO3\CMS\Extbase\Object\ObjectManager;
use TYPO3\CMS\Fluid\View\StandaloneView;

/**
 * Class GetAllFlashMessageIdentifierViewHelper
 *
 * @author Maximilian Fäßler <maximilian@faesslerweb.de>
 * @copyright Steffen Kroggel
 * @package Madj2k_FeRegister
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License, version 3 or later
 */
class GetAllFlashMessageIdentifierViewHelperTest extends FunctionalTestCase
{
    /**
     * @const
     */
    const FIXTURE_PATH = __DIR__ . '/GetAllFlashMessageIdentifierViewHelperTest/Fixtures';


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
            ]
        );

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
    public function renderReturnsArrayWithFlashMessageIdentifiers ()
    {

        /**
         * Scenario:
         *
         * Given ViewHelper reads the extensions TypoScript
         * When the ViewHelper is rendered
         * Then a list (array) of flashMessage identifiers is returned
         */

        /** @var GetAllFlashMessageIdentifierViewHelper $viewHelper */
        $viewHelper = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(GetAllFlashMessageIdentifierViewHelper::class);

        $result = $viewHelper->render();

        foreach ($result as $item) {
            self::assertStringStartsWith('extbase.flashmessages.tx_feregister_', $item);
            self::assertStringEndsNotWith('extbase.flashmessages.tx_feregister_', $item);
        }

        $expectedCount = 10;
        self::assertGreaterThanOrEqual($expectedCount, $result);
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
