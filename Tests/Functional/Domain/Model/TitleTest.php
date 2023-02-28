<?php
namespace Madj2k\FeRegister\Tests\Functional\Domain;

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
 * TitleTest
 *
 * @author Christian Dilger <c.dilger@addorange.de>
 * @copyright Steffen Kroggel
 * @package Madj2k_FeRegister
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License, version 3 or later
 * @todo rework!
 */
class TitleTest extends FunctionalTestCase
{

    /**
     * @const
     */
    const FIXTURE_PATH = __DIR__ . '/TitleTest/Fixtures';


    /**
     * @var string[]
     */
    protected $testExtensionsToLoad = [
        'typo3conf/ext/core_extended',
        'typo3conf/ext/fe_register',
    ];


    /**
     * @var string[]
     */
    protected $coreExtensionsToLoad = [];


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
    private ?FrontendUserRepository $frontendUserRepository = null;


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
    public function titlePropertyIsCheckedIsPersistedToTheDatabase()
    {
        $fixture['isChecked'] = true;

        $this->title->setName("Dr. med.");
        $this->title->setIsChecked($fixture['isChecked']);

        $this->titleRepository->add($this->title);

        $this->persistenceManager->persistAll();

        $databaseResult = $this->getDatabaseConnection()->selectSingleRow('*', 'tx_feregister_domain_model_title','name = "Dr. med."');

        $this->assertNotEmpty($databaseResult);
        $this->assertNotNull($databaseResult['is_checked']);
        $this->assertTrue(isset($databaseResult['is_checked']));
        $this->assertEquals($fixture['isChecked'], (bool) $databaseResult['is_checked']);
    }


    /**
     * @test
     */
    public function onlyTitlesWithPropertyIsCheckedEqualsTrueShouldBePassedToView()
    {

        $titleDoNotPassToView = $this->objectManager->get(Title::class);
        $titleDoNotPassToView->setName("Do not pass to view");
        $titleDoNotPassToView->setIsChecked(false);

        $titleDoPassToView = $this->objectManager->get(Title::class);
        $titleDoPassToView->setName("Do pass to view");
        $titleDoPassToView->setIsChecked(true);

        $this->titleRepository->add($titleDoNotPassToView);
        $this->titleRepository->add($titleDoPassToView);

        $this->persistenceManager->persistAll();

        $titles = $this->titleRepository->findAllOfType(true, false, false);

        $this->assertCount(1, $titles);
        $this->assertEquals($titleDoPassToView, $titles->getFirst());

    }


    /**
     * @test
     */
    public function titlePropertyIsIncludedInSalutationIsPersistedToTheDatabase()
    {
        $fixture['isIncludedInSalutation'] = true;

        $this->title->setName("Dr. med.");
        $this->title->setIsIncludedInSalutation($fixture['isIncludedInSalutation']);

        $this->titleRepository->add($this->title);

        $this->persistenceManager->persistAll();

        $databaseResult = $this->getDatabaseConnection()->selectSingleRow('*', 'tx_feregister_domain_model_title','name = "Dr. med."');

        $this->assertFalse(empty($databaseResult));
        $this->assertNotNull($databaseResult['is_included_in_salutation']);
        $this->assertTrue(isset($databaseResult['is_included_in_salutation']));
        $this->assertEquals($fixture['isIncludedInSalutation'], (bool) $databaseResult['is_included_in_salutation']);
    }


    /**
     * @test
     */
    public function titlePropertyNameFemaleIsPersistedToTheDatabase()
    {
        $fixture['nameFemale'] = "Dipl.-Kauffrau";

        $this->title->setName("Dipl.-Kaufmann");
        $this->title->setNameFemale($fixture['nameFemale']);

        $this->titleRepository->add($this->title);

        $this->persistenceManager->persistAll();

        $databaseResult = $this->getDatabaseConnection()->selectSingleRow('*', 'tx_feregister_domain_model_title','name = "Dipl.-Kaufmann"');

        $this->assertNotEmpty($databaseResult);
        $this->assertTrue(isset($databaseResult['name_female']));
        $this->assertEquals($fixture['nameFemale'], $databaseResult['name_female']);
    }


    /**
     * @test
     */
    public function titlePropertyNameFemaleLongIsPersistedToTheDatabase()
    {
        $fixture['nameFemale'] = "Dipl.-Kauffrau";
        $fixture['nameFemaleLong'] = "Diplom-Kauffrau";

        $this->title->setName("Dipl.-Kaufmann");
        $this->title->setNameFemale($fixture['nameFemale']);
        $this->title->setNameFemaleLong($fixture['nameFemaleLong']);

        $this->titleRepository->add($this->title);

        $this->persistenceManager->persistAll();

        $databaseResult = $this->getDatabaseConnection()->selectSingleRow('*', 'tx_feregister_domain_model_title','name = "Dipl.-Kaufmann"');

        $this->assertNotEmpty($databaseResult);
        $this->assertTrue(isset($databaseResult['name_female']));
        $this->assertTrue(isset($databaseResult['name_female_long']));
        $this->assertEquals($fixture['nameFemale'], $databaseResult['name_female']);
        $this->assertEquals($fixture['nameFemaleLong'], $databaseResult['name_female_long']);
    }


    /**
     * @test
     */
    public function aTitleWithIsAfterEqualsTrueIsRenderedAfterFullName()
    {

        $fixture = "Herr Max Mustermann, Magister artium";

        $this->title->setName("Magister artium");
        $this->title->setIsTitleAfter(true);
        $this->title->setIsIncludedInSalutation(true);

        $this->titleRepository->add($this->title);

        $this->persistenceManager->persistAll();

        $this->frontendUser->setFirstName("Max");
        $this->frontendUser->setLastName("Mustermann");
        $this->frontendUser->setTxFeregisterGender(0);
        $this->frontendUser->setTxFeregisterTitle($this->title);
        $this->frontendUser->setTxFeregisterLanguageKey('de');

        self::assertEquals($fixture, $this->frontendUser->getCompleteSalutationText($checkIncludedInSalutation = true));

    }


    /**
     * @test
     */
    public function aTitleWithIsAfterEqualsFalseIsRenderedBeforeFullName()
    {
        $fixture = "Herr Dr. med. Max Mustermann";

        $this->title->setName("Dr. med.");
        $this->title->setIsTitleAfter(false);

        $this->frontendUser->setFirstName("Max");
        $this->frontendUser->setLastName("Mustermann");
        $this->frontendUser->setTxFeregisterGender(0);
        $this->frontendUser->setTxFeregisterTitle($this->title);
        $this->frontendUser->setTxFeregisterLanguageKey('de');

        self::assertEquals($fixture, $this->frontendUser->getCompleteSalutationText());

    }


    /**
     * @test
     */
    public function aNotSetTitleRendersOnlyFullName()
    {
        $fixture = "Herr Max Mustermann";

        $this->frontendUser->setFirstName("Max");
        $this->frontendUser->setLastName("Mustermann");
        $this->frontendUser->setTxFeregisterGender(0);
        $this->frontendUser->setTxFeregisterLanguageKey('de');

        self::assertEquals($fixture, $this->frontendUser->getCompleteSalutationText());

    }


    /**
     * @test
     */
    public function aTitleMustHaveAnAttributeIsIncludedInSalutation()
    {
        $this->title->setName("Dr. med.");
        $this->title->setIsIncludedInSalutation(true);

        self::assertTrue($this->title->getIsIncludedInSalutation());
    }


    /**
     * @test
     */
    public function aSalutationIsRenderedWithoutTitleIfOptionCheckIncludedInSalutationIsTrueAndTitleIsIncludedInSalutationIsFalse()
    {
        $fixture = "Herr Max Mustermann";

        $this->title->setName("Dr. med.");
        $this->title->setIsTitleAfter(false);
        $this->title->setIsIncludedInSalutation(false);

        $this->frontendUser->setFirstName("Max");
        $this->frontendUser->setLastName("Mustermann");
        $this->frontendUser->setTxFeregisterTitle($this->title);
        $this->frontendUser->setTxFeregisterGender(0);
        $this->frontendUser->setTxFeregisterLanguageKey('de');

        self::assertEquals($fixture, $this->frontendUser->getCompleteSalutationText($checkIncludedInSalutation = true));

    }


    /**
     * @test
     */
    public function aSalutationIsRenderedWithTitleIfOptionCheckIncludedInSalutationIsTrueAndTitleIsIncludedInSalutationIsTrue()
    {
        $fixture = "Herr Dr. med. Max Mustermann";

        $this->title->setName("Dr. med.");
        $this->title->setIsTitleAfter(false);
        $this->title->setIsIncludedInSalutation(true);

        $this->titleRepository->add($this->title);

        $this->persistenceManager->persistAll();

        $this->frontendUser->setFirstName("Max");
        $this->frontendUser->setLastName("Mustermann");
        $this->frontendUser->setTxFeregisterTitle($this->title);
        $this->frontendUser->setTxFeregisterGender(0);
        $this->frontendUser->setTxFeregisterLanguageKey('de');

        self::assertEquals($fixture, $this->frontendUser->getCompleteSalutationText($checkIncludedInSalutation = true));

    }


    /**
     * @test
     */
    public function aSalutationIsRenderedWithFemaleVariantOfTitleIfGenderIsWomanGivenAFemaleVariantIsSet()
    {
        $fixture = "Frau Dipl.-Kauffrau Erika Musterfrau";

        $this->title->setName("Dipl.-Kaufmann");
        $this->title->setNameFemale("Dipl.-Kauffrau");
        $this->title->setIsTitleAfter(false);
        $this->title->setIsIncludedInSalutation(true);

        $this->titleRepository->add($this->title);

        $this->persistenceManager->persistAll();

        $this->frontendUser->setFirstName("Erika");
        $this->frontendUser->setLastName("Musterfrau");
        $this->frontendUser->setTxFeregisterTitle($this->title);
        $this->frontendUser->setTxFeregisterGender(1);
        $this->frontendUser->setTxFeregisterLanguageKey('de');

        self::assertEquals($fixture, $this->frontendUser->getCompleteSalutationText($checkIncludedInSalutation = true));

    }


    /**
     * @test
     */
    public function aSalutationIsRenderedWithDefaultVariantOfTitleIfGenderIsWomanGivenNoFemaleVariantIsSet()
    {
        $fixture = "Frau Dipl.-Kaufmann Erika Musterfrau";

        $this->title->setName("Dipl.-Kaufmann");
        $this->title->setIsTitleAfter(false);
        $this->title->setIsIncludedInSalutation(true);

        $this->titleRepository->add($this->title);

        $this->persistenceManager->persistAll();

        $this->frontendUser->setFirstName("Erika");
        $this->frontendUser->setLastName("Musterfrau");
        $this->frontendUser->setTxFeregisterTitle($this->title);
        $this->frontendUser->setTxFeregisterGender(1);
        $this->frontendUser->setTxFeregisterLanguageKey('de');

        self::assertEquals($fixture, $this->frontendUser->getCompleteSalutationText($checkIncludedInSalutation = true));

    }


    /**
     * @test
     */
    public function aFrontendUserCanBeSavedWithTitleEqualsNull()
    {

        $this->title = null;

        $this->frontendUser->setFirstName("Max");
        $this->frontendUser->setLastName("Mustermann");
        $this->frontendUser->setTxFeregisterGender(0);
        $this->frontendUser->setTxFeregisterTitle($this->title);
        $this->frontendUser->setTxFeregisterLanguageKey('de');

        self::assertEquals('Max Mustermann', $this->frontendUser->getName());

    }

    #=======================================================

    /**
     * TearDown
     */
    protected function teardown(): void
    {
        parent::tearDown();
    }

}
