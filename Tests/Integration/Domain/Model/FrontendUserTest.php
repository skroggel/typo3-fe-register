<?php
namespace Madj2k\FeRegister\Tests\Integration\Domain\Model;

use Nimut\TestingFramework\TestCase\FunctionalTestCase;
use Madj2k\FeRegister\Domain\Model\FrontendUser;
use Madj2k\FeRegister\Domain\Model\Title;
use Madj2k\FeRegister\Domain\Repository\FrontendUserRepository;
use Madj2k\FeRegister\Domain\Repository\TitleRepository;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Object\ObjectManager;
use TYPO3\CMS\Extbase\Persistence\Generic\PersistenceManager;

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


/**
 * FrontendUserTest
 *
 * @author Christian Dilger <c.dilger@addorange.de>
 * @copyright Steffen Kroggel
 * @package Madj2k_FeRegister
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License, version 3 or later
 * @todo rework!
 */
class FrontendUserTest extends FunctionalTestCase
{

    /**
     * @const
     */
    const FIXTURE_PATH = __DIR__ . '/FrontendUserTest/Fixtures';


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
     * @var \Madj2k\FeRegister\Domain\Model\FrontendUser|null
     */
    private ?FrontendUser $frontendUser = null;


    /**
     * @var \Madj2k\FeRegister\Domain\Model\Title|null
     */
    private ?Title $title = null;


    /**
     * @var \Madj2k\FeRegister\Domain\Repository\FrontendUserRepository|null
     */
    private ?FrontendUserRepository $frontendUserRepository;


    /**
     * @var \Madj2k\FeRegister\Domain\Repository\TitleRepository|null
     */
    private ?TitleRepository $titleRepository;


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
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->importDataSet(self::FIXTURE_PATH . '/Database/Global.xml');

        $this->setUpFrontendRootPage(
            1,
            [
                'EXT:fe_register/Configuration/TypoScript/setup.txt',
            ]
        );

        $this->persistenceManager = GeneralUtility::makeInstance(PersistenceManager::class);

        /** @var \TYPO3\CMS\Extbase\Object\ObjectManager $objectManager */
        $this->objectManager = GeneralUtility::makeInstance(ObjectManager::class);
        $this->frontendUserRepository = $this->objectManager->get(FrontendUserRepository::class);
        $this->titleRepository = $this->objectManager->get(TitleRepository::class);

        $this->frontendUser = $this->objectManager->get(FrontendUser::class);
        $this->title = $this->objectManager->get(Title::class);

    }

    //=============================================

    /**
     * @test
     */
    public function setTxFeregisterWithParameterRegistrationTitleEqualsNullDoesNotSetATitle()
    {

        $fixture = '';

        $this->title = null;

        $this->frontendUser->setFirstName("Erika");
        $this->frontendUser->setLastName("Musterfrau");
        $this->frontendUser->setTxFeregisterTitle($this->title);

        self::assertEquals($fixture, $this->frontendUser->getTxFeregisterTitle()->getName());

    }

    //=============================================

    /**
     * TearDown
     */
    protected function teardown(): void
    {
        parent::tearDown();
    }

}
