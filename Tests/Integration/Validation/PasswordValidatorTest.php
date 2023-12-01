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
use Madj2k\FeRegister\Domain\Repository\FrontendUserGroupRepository;
use Madj2k\FeRegister\Domain\Repository\FrontendUserRepository;
use Madj2k\FeRegister\Utility\FrontendUserSessionUtility;
use Madj2k\FeRegister\Validation\PasswordValidator;
use TYPO3\CMS\Extbase\Object\ObjectManager;

/**
 * Class PasswordValidatorTest
 *
 * @author Maximilian Fäßler <maximilian@faesslerweb.de>
 * @author Steffen Kroggel <developer@steffenkroggel.de>
 * @copyright Steffen Kroggel
 * @package Madj2k_FeRegister
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License, version 3 or later
 */
class PasswordValidatorTest extends FunctionalTestCase
{
    /**
     * @const
     */
    const FIXTURE_PATH = __DIR__ . '/PasswordValidatorTest/Fixtures';


    /**
     * @var string[]
     */
    protected $coreExtensionsToLoad = [
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

        /** @var \TYPO3\CMS\Extbase\Object\ObjectManager objectManager */
        $this->objectManager = GeneralUtility::makeInstance(ObjectManager::class);

        /** @var  \Madj2k\FeRegister\Domain\Repository\FrontendUserRepository frontendUserRepository */
        $this->frontendUserRepository = $this->objectManager->get(FrontendUserRepository::class);

        /** @var  \Madj2k\FeRegister\Domain\Repository\FrontendUserGroupRepository frontendUserGroupRepository */
        $this->frontendUserGroupRepository = $this->objectManager->get(FrontendUserGroupRepository::class);

        /** @var \Madj2k\FeRegister\Domain\Model\FrontendUser $frontendUser */
        $frontendUser = $this->frontendUserRepository->findByUid(1);

        /** @var \Madj2k\FeRegister\Domain\Model\FrontendUserGroup $frontendUserGroup */
        $frontendUserGroup = $this->frontendUserGroupRepository->findByUid(1);

        FrontendSimulatorUtility::simulateFrontendEnvironment(1);
        FrontendUserSessionUtility::simulateLogin($frontendUser, $frontendUserGroup);

    }

    #==============================================================================

    /**
     * @test
     */
    public function isValidWithNewPasswordNotSetReturnsFalse ()
    {
        /**
         * Scenario:
         *
         * Given a logged-in frontendUser
         * Given all relevant form-data except the new password is filled
         * When the validator is called
         * Then false is returned
         */

        /** @var \Madj2k\FeRegister\Validation\PasswordValidator $passwordValidator */
        $passwordValidator = $this->objectManager->get(PasswordValidator::class);

        $formInputArray = [
            'old' => 'testtest',
            'first' => '',  // <- not filled!
            'second' => 'newValidPassword0815'
        ];

        // workaround start: for creating $this->result of the validator
        $passwordValidator->validate($formInputArray);
        // workaround end

        $result = $passwordValidator->isValid($formInputArray);

        self::assertFalse($result);
    }


    /**
     * @test
     */
    public function isValidWithNewPasswordRepeatNotSetReturnsFalse ()
    {
        /**
         * Scenario:
         *
         * Given a logged-in frontendUser
         * Given all relevant form-data except the password-repeat is filled
         * When the validator is called
         * Then false is returned
         */

        /** @var \Madj2k\FeRegister\Validation\PasswordValidator $passwordValidator */
        $passwordValidator = $this->objectManager->get(PasswordValidator::class);

        $formInputArray = [
            'old' => 'testtest',
            'first' => 'newValidPassword0815',
            'second' => '' // <- not filled!
        ];

        // workaround start: for creating $this->result of the validator
        $passwordValidator->validate($formInputArray);
        // workaround end

        $result = $passwordValidator->isValid($formInputArray);

        self::assertFalse($result);
    }


    /**
     * @test
     */
    public function isValidWithOldPassworNotSetReturnsFalse ()
    {
        /**
         * Scenario:
         *
         * Given a logged-in frontendUser
         * Given all relevant form-data except the old password is filled
         * When the validator is called
         * Then false is returned
         */

        /** @var \Madj2k\FeRegister\Validation\PasswordValidator $passwordValidator */
        $passwordValidator = $this->objectManager->get(PasswordValidator::class);

        $formInputArray = [
            'old' => '', // <- not filled!
            'first' => 'newValidPassword0815',
            'second' => 'newValidPassword0815'
        ];

        // workaround start: for creating $this->result of the validator
        $passwordValidator->validate($formInputArray);
        // workaround end

        $result = $passwordValidator->isValid($formInputArray);

        self::assertFalse($result);
    }


    /**
     * @test
     */
    public function isValidWithWrongOldPasswordReturnsFalse ()
    {
        /**
         * Scenario:
         *
         * Given a logged-in frontendUser
         * Given all relevant form-data except
         * Given the old password is wrong
         * When the validator is called
         * Then false is returned
         */

        /** @var \Madj2k\FeRegister\Validation\PasswordValidator $passwordValidator */
        $passwordValidator = $this->objectManager->get(PasswordValidator::class);

        $formInputArray = [
            'old' => 'blabla',
            'first' => 'newValidPassword0815',
            'second' => 'newValidPassword0815'
        ];

        // workaround start: for creating $this->result of the validator
        $passwordValidator->validate($formInputArray);
        // workaround end

        $result = $passwordValidator->isValid($formInputArray);

        self::assertFalse($result);
    }


    /**
     * @test
     */
    public function isValidWithNonEqualNewPasswordsReturnsFalse ()
    {
        /**
         * Scenario:
         *
         * Given a logged-in frontendUser
         * Given all relevant form-data except
         * Given the old password is correct
         * Given the value of the password-repeat is not equal to the new password
         * When the validator is called
         * Then false is returned
         */

        /** @var \Madj2k\FeRegister\Validation\PasswordValidator $passwordValidator */
        $passwordValidator = $this->objectManager->get(PasswordValidator::class);

        $formInputArray = [
            'old' => 'testtest',
            'first' => 'newValidPassword0814',
            'second' => 'newValidPassword0815'
        ];

        // workaround start: for creating $this->result of the validator
        $passwordValidator->validate($formInputArray);
        // workaround end

        $result = $passwordValidator->isValid($formInputArray);

        self::assertFalse($result);
    }


    /**
     * @test
     */
    public function isValidWithTooShortNewPasswordReturnsFalse ()
    {
        /**
         * Scenario:
         *
         * Given a logged-in frontendUser
         * Given all relevant form-data except
         * Given the old password is correct
         * Given the value of the password-repeat is equal to the new password
         * Given the new password is too short
         * When the validator is called
         * Then false is returned
         */

        /** @var \Madj2k\FeRegister\Validation\PasswordValidator $passwordValidator */
        $passwordValidator = $this->objectManager->get(PasswordValidator::class);

        $formInputArray = [
            'old' => 'testtest',
            'first' => 'short',
            'second' => 'short'
        ];

        // workaround start: for creating $this->result of the validator
        $passwordValidator->validate($formInputArray);
        // workaround end

        $result = $passwordValidator->isValid($formInputArray);

        self::assertFalse($result);
    }


    /**
     * @test
     */
    public function isValidWithTooLongNewPasswordReturnsFalse ()
    {
        /**
         * Scenario:
         *
         * Given a logged-in frontendUser
         * Given all relevant form-data except
         * Given the old password is correct
         * Given the value of the password-repeat is equal to the new password
         * Given the new password is too long
         * When the validator is called
         * Then false is returned
         */

        /** @var \Madj2k\FeRegister\Validation\PasswordValidator $passwordValidator */
        $passwordValidator = $this->objectManager->get(PasswordValidator::class);

        $formInputArray = [
            'old' => 'testtest',
            'first' => 'looooooooooooooooooooooooooooooooooooooooooooooooooooooooooooong',
            'second' => 'looooooooooooooooooooooooooooooooooooooooooooooooooooooooooooong'
        ];

        // workaround start: for creating $this->result of the validator
        $passwordValidator->validate($formInputArray);
        // workaround end

        $result = $passwordValidator->isValid($formInputArray);

        self::assertFalse($result);
    }


    /**
     * @test
     */
    public function isValidWithNonNumericNewPasswordsReturnsFalse ()
    {
        /**
         * Scenario:
         *
         * Given a logged-in frontendUser
         * Given all relevant form-data except
         * Given the old password is correct
         * Given the value of the password-repeat is equal to the new password
         * Given the new password has the right length
         * Given the new password contains no numbers
         * When the validator is called
         * Then false is returned
         */

        /** @var \Madj2k\FeRegister\Validation\PasswordValidator $passwordValidator */
        $passwordValidator = $this->objectManager->get(PasswordValidator::class);

        $formInputArray = [
            'old' => 'testtest',
            'first' => 'newValidPassword',
            'second' => 'newValidPassword'
        ];

        // workaround start: for creating $this->result of the validator
        $passwordValidator->validate($formInputArray);
        // workaround end

        $result = $passwordValidator->isValid($formInputArray);

        self::assertFalse($result);
    }


    /**
     * @test
     */
    public function isValidWithNonLetterNewPasswordsReturnsFalse ()
    {
        /**
         * Scenario:
         *
         * Given a logged-in frontendUser
         * Given all relevant form-data except
         * Given the old password is correct
         * Given the value of the password-repeat is equal to the new password
         * Given the new password has the right length
         * Given the new password contains no letters
         * When the validator is called
         * Then false is returned
         */

        /** @var \Madj2k\FeRegister\Validation\PasswordValidator $passwordValidator */
        $passwordValidator = $this->objectManager->get(PasswordValidator::class);

        $formInputArray = [
            'old' => 'testtest',
            'first' => '123456789',
            'second' => '123456789'
        ];

        // workaround start: for creating $this->result of the validator
        $passwordValidator->validate($formInputArray);
        // workaround end

        $result = $passwordValidator->isValid($formInputArray);

        self::assertFalse($result);
    }


    /**
     * @test
     */
    public function isValidReturnsTrue ()
    {
        /**
         * Scenario:
         *
         * Given a logged-in frontendUser
         * Given all relevant form-data except
         * Given the old password is correct
         * Given the value of the password-repeat is equal to the new password
         * Given the new password has the right length
         * Given the new password contains letters
         * Given the new password contains numbers
         * When the validator is called
         * Then true is returned
         */

        /** @var \Madj2k\FeRegister\Validation\PasswordValidator $passwordValidator */
        $passwordValidator = $this->objectManager->get(PasswordValidator::class);

        $formInputArray = [
            'old' => 'testtest',
            'first' => 'newValidPassword0815',
            'second' => 'newValidPassword0815'
        ];

        // workaround start: for creating $this->result of the validator
        $passwordValidator->validate($formInputArray);
        // workaround end

        $result = $passwordValidator->isValid($formInputArray);

        self::assertTrue($result);
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
