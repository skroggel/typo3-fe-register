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
use Madj2k\FeRegister\Domain\Repository\FrontendUserRepository;
use Madj2k\FeRegister\Utility\PasswordUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;


/**
 * PasswordUtilityTest
 *
 * @author Maximilian Fäßler <maximilian@faesslerweb.de>
 * @copyright Steffen Kroggel
 * @package Madj2k_FeRegister
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License, version 3 or later
 */
class PasswordUtilityTest extends FunctionalTestCase
{
    /**
     * @const
     */
    const FIXTURE_PATH = __DIR__ . '/PasswordUtilityTest/Fixtures';


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

    }

    #==============================================================================

    /**
     * @test
     */
    public function generatePasswordWithCustomLengthReturnsPasswordWithCustomLength ()
    {
        /**
         * Scenario:
         *
         * Given is a custom password length
         * When a password is generated
         * Then a the password with allowed custom length is returned
         */

        $individualLength = 37;

        /** @var PasswordUtility $utility */
        $utility = GeneralUtility::makeInstance(PasswordUtility::class);

        $result = $utility->generatePassword($individualLength);

        self::assertIsString('string', $result);
        self::assertTrue(strlen($result) == $individualLength);
    }


    /**
     * @test
     */
    public function generatePasswordWithoutCertainLengthReturnsPasswordWithDefaultLength ()
    {
        /**
         * Scenario:
         *
         * Given if not specific password length
         * When a password is generated
         * Then a the password with default length is returned
         */


        /** @var PasswordUtility $utility */
        $utility = GeneralUtility::makeInstance(PasswordUtility::class);

        $result = $utility::generatePassword();

        self::assertIsString('string', $result);
        self::assertTrue(strlen($result) == PasswordUtility::PASSWORD_DEFAULT_LENGTH);
    }


    /**
     * @test
     */
    public function generatePasswordWithTooLongCustomLengthReturnsPasswordWithMaxLength ()
    {
        /**
         * Scenario:
         *
         * Given is a not possible custom password length
         * When a password is generated
         * Then a the password with maximum length is returned
         */

        $individualLength = PHP_INT_MAX;

        /** @var PasswordUtility $utility */
        $utility = GeneralUtility::makeInstance(PasswordUtility::class);

        $result = $utility->generatePassword($individualLength);

        self::assertIsString('string', $result);
        self::assertTrue(strlen($result) == PasswordUtility::PASSWORD_MAX_LENGTH);
    }


    /**
     * @test
     */
    public function generatePasswordWithTooShortCustomLengthReturnsPasswordWithMinLength ()
    {
        /**
         * Scenario:
         *
         * Given is a custom password length between 0 and the minimum length
         * When a password is generated
         * Then a the password with minimum length is returned
         */

        // @todo: Not necessary to check all values via loop

        $individualLength = 0;

        /** @var PasswordUtility $utility */
        $utility = GeneralUtility::makeInstance(PasswordUtility::class);

        do {
            $result = $utility->generatePassword($individualLength);

            self::assertIsString('string', $result);
            self::assertTrue(strlen($result) == PasswordUtility::PASSWORD_MIN_LENGTH);

            $individualLength++;
        } while ($individualLength <= PasswordUtility::PASSWORD_MIN_LENGTH);

    }


    /**
     * @test
     */
    public function generatePasswordReturnsSolelyAlphanumericSigns ()
    {
        /**
         * Scenario:
         *
         * Given is nothing special
         * When a password is generated
         * Then a the password with default settings has only alphanumeric signs
         */

        /** @var PasswordUtility $utility */
        $utility = GeneralUtility::makeInstance(PasswordUtility::class);

        $result = $utility->generatePassword();

        self::assertTrue(ctype_alnum($result));
    }


    /**
     * @test
     */
    public function generatePasswordReturnsAlsoNonAlphanumericSigns ()
    {
        /**
         * Scenario:
         *
         * Given is nothing special
         * When a password is generated
         * Then a the password with default settings has only alphanumeric signs
         */

        /** @var PasswordUtility $utility */
        $utility = GeneralUtility::makeInstance(PasswordUtility::class);

        $result = $utility->generatePassword(PasswordUtility::PASSWORD_DEFAULT_LENGTH, true);

        self::assertFalse(ctype_alnum($result));
    }


    /**
     * @test
     */
    public function saltPasswordReturnsAnEncryptedString ()
    {
        /**
         * Scenario:
         *
         * Given is a plaintext password
         * When the plaintext password is encrypted
         * Then a encrypted version of the plaintext password is returned
         */

        /** @var PasswordUtility $utility */
        $utility = GeneralUtility::makeInstance(PasswordUtility::class);

        $customPassword = 'absolutelySecret!';

        $result = $utility::saltPassword($customPassword);

        self::assertIsString('string', $result);
        self::assertNotEquals($result, $customPassword);
    }


    /**
     * @test
     */
    public function saltPasswordWithUnloadedSaltedPasswordsExtension ()
    {
        /**
         * Scenario:
         *
         * Scenario not possible:
         * TYPO3\CMS\Core\Package\Exception\ProtectedPackageKeyException : The package "saltedpasswords" is protected and cannot be deactivated.
         *
         * Given is a plaintext password
         * When the system extension "saltedpasswords" is unloaded
         * Then a a not encrypted version of the plaintext password is returned
         */


        self::assertTrue(true);
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
