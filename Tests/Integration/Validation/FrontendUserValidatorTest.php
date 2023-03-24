<?php
namespace Madj2k\FeRegister\Tests\Integration\Validation;

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
use Madj2k\CoreExtended\Utility\GeneralUtility;
use Madj2k\FeRegister\Domain\Model\FrontendUser;
use Madj2k\FeRegister\Domain\Repository\FrontendUserGroupRepository;
use Madj2k\FeRegister\Domain\Repository\FrontendUserRepository;
use Madj2k\FeRegister\Utility\FrontendUserSessionUtility;
use Madj2k\FeRegister\Validation\FrontendUserValidator;
use TYPO3\CMS\Extbase\Object\ObjectManager;

/**
 * Class FrontendUserValidatorTest
 *
 * @author Maximilian Fäßler <maximilian@faesslerweb.de>
 * @author Steffen Kroggel <developer@steffenkroggel.de>
 * @copyright Steffen Kroggel
 * @package Madj2k_FeRegister
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License, version 3 or later
 */
class FrontendUserValidatorTest extends FunctionalTestCase
{
    /**
     * @const
     */
    const FIXTURE_PATH = __DIR__ . '/FrontendUserValidatorTest/Fixtures';


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
     * @var \Madj2k\FeRegister\Domain\Repository\FrontendUserGroupRepository|null
     */
    private ?FrontendUserGroupRepository $frontendUserGroupRepository = null;


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
                'EXT:fe_register/Configuration/TypoScript/setup.typoscript',
                self::FIXTURE_PATH . '/Frontend/Configuration/Rootpage.typoscript',
            ]
        );

        FrontendSimulatorUtility::simulateFrontendEnvironment(1);

        /** @var \TYPO3\CMS\Extbase\Object\ObjectManager objectManager */
        $this->objectManager = GeneralUtility::makeInstance(ObjectManager::class);

        /** @var  \Madj2k\FeRegister\Domain\Repository\FrontendUserRepository frontendUserRepository */
        $this->frontendUserRepository = $this->objectManager->get(FrontendUserRepository::class);

        /** @var  \Madj2k\FeRegister\Domain\Repository\FrontendUserGroupRepository frontendUserGroupRepository */
        $this->frontendUserGroupRepository = $this->objectManager->get(FrontendUserGroupRepository::class);

    }

    #==============================================================================

    /**
     * @test
     */
    public function isValidWithCompleteMandatoryFieldsReturnsTrue ()
    {
        /**
         * Scenario:
         *
         * Given a valid configuration of mandatory fields
         * Given an email address that has not been used by another user
         * Given all mandatory fields are filled
         * When the validator is called
         * Then true is returned
         */

        /** @var \Madj2k\FeRegister\Domain\Model\FrontendUser $frontendUserFormData */
        $frontendUserFormData = GeneralUtility::makeInstance(FrontendUser::class);
        $frontendUserFormData->setEmail('test@gmx.de');
        $frontendUserFormData->setFirstName('Först naime');
        $frontendUserFormData->setLastName('Säcond naime');

        /** @var \Madj2k\FeRegister\Validation\FrontendUserValidator $frontendUserValidator */
        $frontendUserValidator = $this->objectManager->get(FrontendUserValidator::class);

        // workaround start: for creating $this->result of the validator
        $frontendUserValidator->validate($frontendUserFormData);
        // workaround end

        $result = $frontendUserValidator->isValid($frontendUserFormData);
        self::assertTrue($result);
    }


    /**
     * @test
     */
    public function isValidWithEmailOnlyAndWithoutMandatoryFieldsConfiguredReturnsTrue ()
    {
        /**
         * Scenario:
         *
         * Given a valid configuration of mandatory fields with no mandatory fields set
         * Given no fields are filled out
         * When the validator function is called
         * Then true is returned
         */

        FrontendSimulatorUtility::resetFrontendEnvironment();

        $this->setUpFrontendRootPage(
            1,
            [
                'EXT:core_extended/Configuration/TypoScript/setup.typoscript',
                'EXT:fe_register/Configuration/TypoScript/setup.typoscript',
                self::FIXTURE_PATH . '/Frontend/Configuration/Check10.typoscript',
            ]
        );

        FrontendSimulatorUtility::simulateFrontendEnvironment(1);

        /** @var \Madj2k\FeRegister\Domain\Model\FrontendUser $frontendUserFormData */
        $frontendUserFormData = GeneralUtility::makeInstance(FrontendUser::class);
        $frontendUserFormData->setEmail('test@gmx.de');

        /** @var \Madj2k\FeRegister\Validation\FrontendUserValidator $frontendUserValidator */
        $frontendUserValidator = $this->objectManager->get(FrontendUserValidator::class);

        // workaround start: for creating $this->result of the validator
        $frontendUserValidator->validate($frontendUserFormData);
        // workaround end

        $result = $frontendUserValidator->isValid($frontendUserFormData);
        self::assertTrue($result);
    }


    /**
     * @test
     */
    public function isValidWithIncompleteMandatoryFieldsReturnFalse ()
    {
        /**
         * Scenario:
         *
         * Given a valid configuration of mandatory fields
         * Given an email address that has not been used by another user
         * Given not all mandatory fields are filled out
         * When the validator is called
         * Then false is returned
         */

        /** @var \Madj2k\FeRegister\Domain\Model\FrontendUser $frontendUserFormData */
        $frontendUserFormData = GeneralUtility::makeInstance(FrontendUser::class);
        $frontendUserFormData->setEmail('test@gmx.de');
        $frontendUserFormData->setFirstName('');
        $frontendUserFormData->setLastName('');

        /** @var \Madj2k\FeRegister\Validation\FrontendUserValidator $frontendUserValidator */
        $frontendUserValidator = $this->objectManager->get(FrontendUserValidator::class);

        // workaround start: for creating $this->result of the validator
        $frontendUserValidator->validate($frontendUserFormData);
        // workaround end

        $result = $frontendUserValidator->isValid($frontendUserFormData);
        self::assertFalse($result);
    }


    /**
     * @test
     */
    public function isValidWithIncompleteMandatoryFieldsForGroupReturnFalse ()
    {
        /**
         * Scenario:
         *
         * Given a valid configuration of mandatory fields
         * Given all mandatory fields of that configuration are filled out
         * Given an email address that has not been used by another user
         * Given the frontendUser belongs to a frontendUserGroup
         * Given that frontendUserGroup has an additional mandatory field set
         * Given that mandatory field is not filled out
         * When the validator is called
         * Then false is returned
         */
        $this->importDataSet(self::FIXTURE_PATH . '/Database/Check20.xml');

        /** @var \Madj2k\FeRegister\Domain\Model\FrontendUserGroup $frontendUserGroup */
        $frontendUserGroup = $this->frontendUserGroupRepository->findByUid(20);

        /** @var \Madj2k\FeRegister\Domain\Model\FrontendUser $frontendUserFormData */
        $frontendUserFormData = GeneralUtility::makeInstance(FrontendUser::class);
        $frontendUserFormData->setEmail('test@gmx.de');
        $frontendUserFormData->setFirstName('FirstName');
        $frontendUserFormData->setLastName('LastName');
        $frontendUserFormData->addUsergroup($frontendUserGroup);

        /** @var \Madj2k\FeRegister\Validation\FrontendUserValidator $frontendUserValidator */
        $frontendUserValidator = $this->objectManager->get(FrontendUserValidator::class);

        // workaround start: for creating $this->result of the validator
        $frontendUserValidator->validate($frontendUserFormData);
        // workaround end

        $result = $frontendUserValidator->isValid($frontendUserFormData);
        self::assertFalse($result);
    }


    /**
     * @test
     */
    public function isValidWithCompleteMandatoryFieldsForGroupReturnTrue ()
    {
        /**
         * Scenario:
         *
         * Given a valid configuration of mandatory fields
         * Given all mandatory fields of that configuration are filled out
         * Given an email address that has not been used by another user
         * Given the frontendUser belongs to a frontendUserGroup
         * Given that frontendUserGroup has an additional mandatory field set
         * Given that mandatory field is filled out
         * When the validator is called
         * Then true is returned
         */
        $this->importDataSet(self::FIXTURE_PATH . '/Database/Check20.xml');

        /** @var \Madj2k\FeRegister\Domain\Model\FrontendUserGroup $frontendUserGroup */
        $frontendUserGroup = $this->frontendUserGroupRepository->findByUid(20);

        /** @var \Madj2k\FeRegister\Domain\Model\FrontendUser $frontendUserFormData */
        $frontendUserFormData = GeneralUtility::makeInstance(FrontendUser::class);
        $frontendUserFormData->setEmail('test@gmx.de');
        $frontendUserFormData->setFirstName('FirstName');
        $frontendUserFormData->setLastName('LastName');
        $frontendUserFormData->addUsergroup($frontendUserGroup);
        $frontendUserFormData->setTxFeregisterFacebookUrl('https://www.facebook.de');

        /** @var \Madj2k\FeRegister\Validation\FrontendUserValidator $frontendUserValidator */
        $frontendUserValidator = $this->objectManager->get(FrontendUserValidator::class);

        // workaround start: for creating $this->result of the validator
        $frontendUserValidator->validate($frontendUserFormData);
        // workaround end

        $result = $frontendUserValidator->isValid($frontendUserFormData);
        self::assertTrue($result);
    }


    /**
     * @test
     */
    public function isValidWithIncompleteMandatoryFieldsForTemporaryGroupReturnFalse ()
    {
        /**
         * Scenario:
         *
         * Given a valid configuration of mandatory fields
         * Given all mandatory fields of that configuration are filled out
         * Given an email address that has not been used by another user
         * Given the frontendUser temporarily belongs to a frontendUserGroup
         * Given that frontendUserGroup has an additional mandatory field set
         * Given that mandatory field is not filled out
         * When the validator is called
         * Then false is returned
         */
        $this->importDataSet(self::FIXTURE_PATH . '/Database/Check20.xml');

        /** @var \Madj2k\FeRegister\Domain\Model\FrontendUserGroup $frontendUserGroup */
        $frontendUserGroup = $this->frontendUserGroupRepository->findByUid(20);

        /** @var \Madj2k\FeRegister\Domain\Model\FrontendUser $frontendUserFormData */
        $frontendUserFormData = GeneralUtility::makeInstance(FrontendUser::class);
        $frontendUserFormData->setEmail('test@gmx.de');
        $frontendUserFormData->setFirstName('FirstName');
        $frontendUserFormData->setLastName('LastName');
        $frontendUserFormData->setTempFrontendUserGroup($frontendUserGroup);

        /** @var \Madj2k\FeRegister\Validation\FrontendUserValidator $frontendUserValidator */
        $frontendUserValidator = $this->objectManager->get(FrontendUserValidator::class);

        // workaround start: for creating $this->result of the validator
        $frontendUserValidator->validate($frontendUserFormData);
        // workaround end

        $result = $frontendUserValidator->isValid($frontendUserFormData);
        self::assertFalse($result);
    }


    /**
     * @test
     * @throws \Exception
     */
    public function isValidWithCompleteMandatoryFieldsForTemporaryGroupReturnTrue ()
    {
        /**
         * Scenario:
         *
         * Given a valid configuration of mandatory fields
         * Given all mandatory fields of that configuration are filled out
         * Given an email address that has not been used by another user
         * Given the frontendUser temporarily belongs to a frontendUserGroup
         * Given that frontendUserGroup has an additional mandatory field set
         * Given that mandatory field is filled out
         * When the validator is called
         * Then true is returned
         */
        $this->importDataSet(self::FIXTURE_PATH . '/Database/Check20.xml');

        /** @var \Madj2k\FeRegister\Domain\Model\FrontendUserGroup $frontendUserGroup */
        $frontendUserGroup = $this->frontendUserGroupRepository->findByUid(20);

        /** @var \Madj2k\FeRegister\Domain\Model\FrontendUser $frontendUserFormData */
        $frontendUserFormData = GeneralUtility::makeInstance(FrontendUser::class);
        $frontendUserFormData->setEmail('test@gmx.de');
        $frontendUserFormData->setFirstName('FirstName');
        $frontendUserFormData->setLastName('LastName');
        $frontendUserFormData->setTempFrontendUserGroup($frontendUserGroup);
        $frontendUserFormData->setTxFeregisterFacebookUrl('https://www.facebook.de');

        /** @var \Madj2k\FeRegister\Validation\FrontendUserValidator $frontendUserValidator */
        $frontendUserValidator = $this->objectManager->get(FrontendUserValidator::class);

        // workaround start: for creating $this->result of the validator
        $frontendUserValidator->validate($frontendUserFormData);
        // workaround end

        $result = $frontendUserValidator->isValid($frontendUserFormData);
        self::assertTrue($result);
    }


    /**
     * @test
     * @throws \Exception
     */
    public function isValidWithAlreadyUsedEmailAddressReturnsFalse ()
    {
        /**
         * Scenario:
         *
         * Given a valid configuration of mandatory fields
         * Given an email address that has been used by another user
         * Given all mandatory fields are filled
         * When the validator function is called
         * Then false is returned
         */
        $this->importDataSet(self::FIXTURE_PATH . '/Database/Check10.xml');

        /** @var \Madj2k\FeRegister\Domain\Model\FrontendUser $frontendUserFormData */
        $frontendUserFormData = GeneralUtility::makeInstance(FrontendUser::class);
        $frontendUserFormData->setEmail('lauterbach@spd.de');
        $frontendUserFormData->setFirstName('Först naime');
        $frontendUserFormData->setLastName('Säcond naime');

        /** @var \Madj2k\FeRegister\Validation\FrontendUserValidator $frontendUserValidator */
        $frontendUserValidator = $this->objectManager->get(FrontendUserValidator::class);

        // workaround start: for creating $this->result of the validator
        $frontendUserValidator->validate($frontendUserFormData);
        // workaround end

        $result = $frontendUserValidator->isValid($frontendUserFormData);
        self::assertFalse($result);
    }


    /**
     * @test
     * @throws \Exception
     */
    public function isValidWithAlreadyUsedEmailAddressButLoggedInReturnsTrue ()
    {
        /**
         * Scenario:
         *
         * Given a valid configuration of mandatory fields
         * Given an email address that has been used
         * Given the user that used this email is logged in
         * Given all mandatory fields are filled
         * When the validator function is called
         * Then true is returned
         */
        $this->importDataSet(self::FIXTURE_PATH . '/Database/Check10.xml');

        /** @var \Madj2k\FeRegister\Domain\Model\FrontendUser $frontendUser */
        $frontendUser = $this->frontendUserRepository->findByUid(10);

        /** @var \Madj2k\FeRegister\Domain\Model\FrontendUserGroup $frontendUserGroup */
        $frontendUserGroup = $this->frontendUserGroupRepository->findByUid(10);

        FrontendSimulatorUtility::simulateFrontendEnvironment(1);
        FrontendUserSessionUtility::simulateLogin($frontendUser, $frontendUserGroup);

        /** @var \Madj2k\FeRegister\Domain\Model\FrontendUser $frontendUserFormData */
        $frontendUserFormData = GeneralUtility::makeInstance(FrontendUser::class);
        $frontendUserFormData->setEmail('lauterbach@spd.de');
        $frontendUserFormData->setFirstName('Först naime');
        $frontendUserFormData->setLastName('Säcond naime');

        /** @var \Madj2k\FeRegister\Validation\FrontendUserValidator $frontendUserValidator */
        $frontendUserValidator = $this->objectManager->get(FrontendUserValidator::class);

        // workaround start: for creating $this->result of the validator
        $frontendUserValidator->validate($frontendUserFormData);
        // workaround end

        $result = $frontendUserValidator->isValid($frontendUserFormData);
        self::assertTrue($result);

        FrontendSimulatorUtility::resetFrontendEnvironment();
    }


    /**
     * @test
     * @throws \Exception
     */
    public function isValidWithInvalidEmailAddressReturnsFalse ()
    {
        /**
         * Scenario:
         *
         * Given a valid configuration of mandatory fields
         * Given an invalid email address
         * Given all mandatory fields are filled
         * When the validator function is called
         * Then false is returned
         */

        /** @var \Madj2k\FeRegister\Domain\Model\FrontendUser $frontendUserFormData */
        $frontendUserFormData = GeneralUtility::makeInstance(FrontendUser::class);
        $frontendUserFormData->setEmail('lauterbach');
        $frontendUserFormData->setFirstName('Först naime');
        $frontendUserFormData->setLastName('Säcond naime');

        /** @var \Madj2k\FeRegister\Validation\FrontendUserValidator $frontendUserValidator */
        $frontendUserValidator = $this->objectManager->get(FrontendUserValidator::class);

        // workaround start: for creating $this->result of the validator
        $frontendUserValidator->validate($frontendUserFormData);
        // workaround end

        $result = $frontendUserValidator->isValid($frontendUserFormData);

        self::assertFalse($result);
    }


    /**
     * @test
     */
    public function isValidWithCorrectZipReturnsTrue ()
    {
        /**
         * Scenario:
         *
         * Given a valid configuration of mandatory fields
         * Given a valid email-address
         * Given a correct zip
         * Given all mandatory fields are filled
         * When the validator function is called
         * Then true is returned
         */

        /** @var \Madj2k\FeRegister\Domain\Model\FrontendUser $frontendUserFormData */
        $frontendUserFormData = GeneralUtility::makeInstance(FrontendUser::class);
        $frontendUserFormData->setEmail('lauterbach@spd.de');
        $frontendUserFormData->setFirstName('Först naime');
        $frontendUserFormData->setLastName('Säcond naime');
        $frontendUserFormData->setZip(35745);

        /** @var \Madj2k\FeRegister\Validation\FrontendUserValidator $frontendUserValidator */
        $frontendUserValidator = $this->objectManager->get(FrontendUserValidator::class);

        // workaround start: for creating $this->result of the validator
        $frontendUserValidator->validate($frontendUserFormData);
        // workaround end

        $result = $frontendUserValidator->isValid($frontendUserFormData);

        self::assertTrue($result);
    }


    /**
     * @test
     */
    public function isValidInvalidZipReturnsFalse ()
    {
        /**
         * Scenario:
         *
         * Given a valid configuration of mandatory fields
         * Given a valid email-address
         * Given an incorrect zip
         * Given all mandatory fields are filled
         * When the validator function is called
         * Then false is returned
         */

        /** @var \Madj2k\FeRegister\Domain\Model\FrontendUser $frontendUserFormData */
        $frontendUserFormData = GeneralUtility::makeInstance(FrontendUser::class);
        $frontendUserFormData->setEmail('lauterbach@spd.de');
        $frontendUserFormData->setFirstName('Först naime');
        $frontendUserFormData->setLastName('Säcond naime');
        $frontendUserFormData->setZip(35);

        /** @var \Madj2k\FeRegister\Validation\FrontendUserValidator $frontendUserValidator */
        $frontendUserValidator = $this->objectManager->get(FrontendUserValidator::class);

        // workaround start: for creating $this->result of the validator
        $frontendUserValidator->validate($frontendUserFormData);
        // workaround end

        $result = $frontendUserValidator->isValid($frontendUserFormData);

        self::assertFalse($result);
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
