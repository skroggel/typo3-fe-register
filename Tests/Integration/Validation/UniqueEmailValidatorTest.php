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
use Madj2k\FeRegister\Validation\UniqueEmailValidator;
use TYPO3\CMS\Extbase\Object\ObjectManager;

/**
 * Class UniqueEmailValidatorTest
 *
 * @author Maximilian Fäßler <maximilian@faesslerweb.de>
 * @copyright Steffen Kroggel
 * @package Madj2k_FeRegister
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License, version 3 or later
 */
class UniqueEmailValidatorTest extends FunctionalTestCase
{

    /**
     * @const
     */
    const FIXTURE_PATH = __DIR__ . '/UniqueEmailValidatorTest/Fixtures';


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

        FrontendSimulatorUtility::simulateFrontendEnvironment(1);
    }

    #==============================================================================

    /**
     * @test
     */
    public function isValidWithInvalidEmailReturnsFalse ()
    {

        /**
         * Scenario:
         *
         * Given is a persistent frontendUser with a valid email-address
         * Given a new frontendUser with a different, but invalid email-address
         * When the validator is called
         * Then false is returned
         */

        /** @var \Madj2k\FeRegister\Validation\UniqueEmailValidator $uniqueEmailValidator */
        $uniqueEmailValidator = $this->objectManager->get(UniqueEmailValidator::class);

        /** @var \Madj2k\FeRegister\Domain\Model\FrontendUser $frontendUser */
        $frontendUser = $this->objectManager->get(FrontendUser::class);
        $frontendUser->setEmail('lauterbach');

        // workaround start: for creating $this->result of the validator
        $uniqueEmailValidator->validate($frontendUser);
        // workaround end

        $result = $uniqueEmailValidator->isValid($frontendUser);

        self::assertFalse($result);

    }


    /**
     * @test
     */
    public function isValidWithExistingUserReturnsTrue ()
    {
        /**
         * Scenario:
         *
         * Given is a persistent frontendUser with a valid email-address
         * Given is that frontendUser is logged in
         * When the validator is called
         * Then true is returned
         */

        /** @var \Madj2k\FeRegister\Validation\UniqueEmailValidator $uniqueEmailValidator */
        $uniqueEmailValidator = $this->objectManager->get(UniqueEmailValidator::class);

        /** @var \Madj2k\FeRegister\Domain\Model\FrontendUser $frontendUser */
        $frontendUser = $this->frontendUserRepository->findByUid(1);

        /** @var \Madj2k\FeRegister\Domain\Model\FrontendUserGroup $frontendUserGroup */
        $frontendUserGroup = $this->frontendUserGroupRepository->findByUid(1);

        FrontendUserSessionUtility::simulateLogin($frontendUser, $frontendUserGroup);

        // workaround start: for creating $this->result of the validator
        $uniqueEmailValidator->validate($frontendUser);
        // workaround end

        $result = $uniqueEmailValidator->isValid($frontendUser);

        self::assertTrue($result);
    }


    /**
     * @test
     */
    public function isValidWithAlreadyExistingEmailReturnsFalse ()
    {

        /**
         * Scenario:
         *
         * Given is a persistent frontendUser with a valid email-address
         * Given a new frontendUser with the same valid email-address
         * When the validator is called
         * Then false is returned
         */

        /** @var \Madj2k\FeRegister\Validation\UniqueEmailValidator $uniqueEmailValidator */
        $uniqueEmailValidator = $this->objectManager->get(UniqueEmailValidator::class);

        /** @var \Madj2k\FeRegister\Domain\Model\FrontendUser $frontendUser */
        $frontendUser = $this->objectManager->get(FrontendUser::class);
        $frontendUser->setEmail('lauterbach@spd.de');

        // workaround start: for creating $this->result of the validator
        $uniqueEmailValidator->validate($frontendUser);
        // workaround end

        $result = $uniqueEmailValidator->isValid($frontendUser);

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
         * Given is a persistent frontendUser with a valid email-address
         * Given a new frontendUser with a different valid email-address
         * When the validator is called
         * Then true is returned
         */

        /** @var \Madj2k\FeRegister\Validation\UniqueEmailValidator $uniqueEmailValidator */
        $uniqueEmailValidator = $this->objectManager->get(UniqueEmailValidator::class);

        /** @var \Madj2k\FeRegister\Domain\Model\FrontendUser $frontendUser */
        $frontendUser = $this->objectManager->get(FrontendUser::class);
        $frontendUser->setEmail('merkel@cdu.de');

        // workaround start: for creating $this->result of the validator
        $uniqueEmailValidator->validate($frontendUser);
        // workaround end

        $result = $uniqueEmailValidator->isValid($frontendUser);

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
