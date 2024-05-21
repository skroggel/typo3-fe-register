<?php
namespace Madj2k\FeRegister\Tests\Integration\DataProtection;


use Nimut\TestingFramework\TestCase\FunctionalTestCase;

use Madj2k\FeRegister\DataProtection\DataProtectionHandler;
use Madj2k\FeRegister\Domain\Model\Consent;
use Madj2k\FeRegister\Domain\Repository\FrontendUserRepository;
use Madj2k\FeRegister\Domain\Repository\BackendUserRepository;
use Madj2k\FeRegister\Domain\Repository\ShippingAddressRepository;
use Madj2k\FeRegister\Domain\Repository\ConsentRepository;
use Madj2k\FeRegister\Domain\Repository\EncryptedDataRepository;

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
 * DataProtectionHandlerTest
 *
 * @author Steffen Kroggel <developer@steffenkroggel.de>
 * @copyright Steffen Kroggel
 * @package Madj2k_FeRegister
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License, version 3 or later
 */
class DataProtectionHandlerTest extends FunctionalTestCase
{
    /**
     * @const
     */
    const FIXTURE_PATH = __DIR__ . '/DataProtectionHandlerTest/Fixtures';


    /**
     * @var string[]
     */
    protected $testExtensionsToLoad = [
        'typo3conf/ext/ajax_api',
        'typo3conf/ext/core_extended',
        'typo3conf/ext/accelerator',
        'typo3conf/ext/fe_register',
    ];


    /**
     * @var string[]
     */
    protected $coreExtensionsToLoad = [];


    /**
     * @var \Madj2k\FeRegister\DataProtection\DataProtectionHandler|null
     */
    private ?DataProtectionHandler $subject = null;


    /**
     * @var \Madj2k\FeRegister\Domain\Repository\FrontendUserRepository|null
     */
    private ?FrontendUserRepository $frontendUserRepository = null;


    /**
     * @var \Madj2k\FeRegister\Domain\Repository\BackendUserRepository|null
     */
    private ?BackendUserRepository $backendUserRepository = null;


    /**
     * @var \Madj2k\FeRegister\Domain\Repository\ShippingAddressRepository|null
     */
    private ?ShippingAddressRepository $shippingAddressRepository = null;


    /**
     * @var \Madj2k\FeRegister\Domain\Repository\ConsentRepository|null
     */
    private ?ConsentRepository $consentRepository = null;


    /**
     * @var \Madj2k\FeRegister\Domain\Repository\EncryptedDataRepository|null
     */
    private ?EncryptedDataRepository $encryptedDataRepository = null;


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
        $this->persistenceManager = GeneralUtility::makeInstance(PersistenceManager::class);

        /** @var \TYPO3\CMS\Extbase\Object\ObjectManager $objectManager */
        $this->objectManager = GeneralUtility::makeInstance(ObjectManager::class);
        $this->subject = $this->objectManager->get(DataProtectionHandler::class);
        $this->frontendUserRepository = $this->objectManager->get(FrontendUserRepository::class);
        $this->backendUserRepository = $this->objectManager->get(BackendUserRepository::class);
        $this->shippingAddressRepository = $this->objectManager->get(ShippingAddressRepository::class);
        $this->consentRepository = $this->objectManager->get(ConsentRepository::class);
        $this->encryptedDataRepository = $this->objectManager->get(EncryptedDataRepository::class);

        $this->subject->setEncryptionKey('o4uSZ0oo4zTFIIoN2NkuBBwyS6Lv3v/EYVObucPHcW8=');

    }



    //===================================================================

    /**
     * @test
     * @throws \Exception
     */
    public function anonymizeAndEncryptAllAnonymizesAndEncryptsFrontendUserData()
    {

        /**
         * Scenario:
         *
         * Given there is a frontend user
         * Given the frontend user has been deleted since more days then configured for anonymization
         * When I anonymize all deleted users
         * Then the user data is anonymised
         * Then the dataProtectionStatus is set to 1
         * Then the user data is encrypted in separate table
         */
        $this->importDataSet(self::FIXTURE_PATH . '/Database/Check30.xml');

        $this->subject->anonymizeAndEncryptAll();
        $this->persistenceManager->persistAll();

        /** @var \Madj2k\FeRegister\Domain\Model\FrontendUser $frontendUser */
        $frontendUser = $this->frontendUserRepository->findByUid(30);

        self::assertEquals('anonymous' . $frontendUser->getUid() . '@example.de', $frontendUser->getUsername());
        self::assertEquals('anonymous' . $frontendUser->getUid() . '@example.de', $frontendUser->getEmail());
        self::assertEquals(1, $frontendUser->getTxFeregisterDataProtectionStatus());

        /** @var \Madj2k\FeRegister\Domain\Model\EncryptedData $encryptedData */
        $encryptedData = $this->encryptedDataRepository->findByUid(1);

        self::assertInstanceOf(\Madj2k\FeRegister\Domain\Model\EncryptedData::class, $encryptedData);
        self::assertEquals($frontendUser, $encryptedData->getFrontendUser());
        self::assertEquals(hash('sha256', 'lauterbach@spd.de'), $encryptedData->getSearchKey());
        self::assertEquals('fe_users', $encryptedData->getForeignTable());
        self::assertEquals(\Madj2k\FeRegister\Domain\Model\FrontendUser::class, $encryptedData->getForeignClass());
        self::assertEquals($frontendUser->getUid(), $encryptedData->getForeignUid());

    }


    /**
     * @test
     * @throws \Exception
     */
    public function anonymizeAndEncryptAllAnonymizesAndEncryptsRelatedData()
    {

        /**
         * Scenario:
         *
         * Given there is a frontend user
         * Given this frontend user has a shipping address
         * Given this frontend user has consent data according to his order
         * Given the frontend user has been deleted since more days then configured for anonymization
         * When I anonymize all deleted users
         * Then the shipping address of the frontend user is anonymised
         * Then the shipping address of the frontend user is encrypted
         * Then the shipping address of the consent data is anonymised
         * Then the shipping address of the consent data is encrypted
         */
        $this->importDataSet(self::FIXTURE_PATH . '/Database/Check40.xml');

        $this->subject->anonymizeAndEncryptAll();
        $this->persistenceManager->persistAll();

        /** @var \Madj2k\FeRegister\Domain\Model\ShippingAddress $shippingAddress */
        $shippingAddress  = $this->shippingAddressRepository->findByUid(40);

        self::assertEquals('Anonymous Anonymous', $shippingAddress->getFullName());

        /** @var \Madj2k\FeRegister\Domain\Model\EncryptedData $encryptedData */
        $encryptedData = $this->encryptedDataRepository->findByUid(2);

        /** @var \Madj2k\FeRegister\Domain\Model\FrontendUser $frontendUser */
        $frontendUser = $this->frontendUserRepository->findByUid(40);

        self::assertInstanceOf(\Madj2k\FeRegister\Domain\Model\EncryptedData::class, $encryptedData);
        self::assertEquals($frontendUser, $encryptedData->getFrontendUser());
        self::assertEquals(hash('sha256', $frontendUser->getEmail()), $encryptedData->getSearchKey());
        self::assertEquals('tx_feregister_domain_model_shippingaddress', $encryptedData->getForeignTable());
        self::assertEquals(\Madj2k\FeRegister\Domain\Model\ShippingAddress::class, $encryptedData->getForeignClass());
        self::assertEquals($shippingAddress->getUid(), $encryptedData->getForeignUid());

        /** @var \Madj2k\FeRegister\Domain\Model\Consent $consent */
        $consent  = $this->consentRepository->findByUid(40);

        self::assertEquals('127.0.0.1', $consent->getIpAddress());
        self::assertEquals('Anonymous 1.0', $consent->getUserAgent());

        /** @var \Madj2k\FeRegister\Domain\Model\EncryptedData $encryptedData */
        $encryptedData = $this->encryptedDataRepository->findByUid(3);

        /** @var \Madj2k\FeRegister\Domain\Model\FrontendUser $frontendUser */
        $frontendUser = $this->frontendUserRepository->findByUid(40);

        self::assertEquals($frontendUser->getUid(), $encryptedData->getFrontendUser()->getUid());
        self::assertEquals(hash('sha256', $frontendUser->getEmail()), $encryptedData->getSearchKey());
        self::assertEquals($consent->getUid(), $encryptedData->getForeignUid());
        self::assertEquals('tx_feregister_domain_model_consent', $encryptedData->getForeignTable());
        self::assertEquals('Madj2k\FeRegister\Domain\Model\Consent', $encryptedData->getForeignClass());
    }


    /**
     * @test
     * @throws \Exception
     */
    public function anonymizeAndEncryptAllAnonymizesAndEncryptsDeletedRelatedData()
    {

        /**
         * Scenario:
         *
         * Given there is a frontend user
         * Given this frontend user has a deleted shipping address
         * Given the frontend user has been deleted since more days then configured for anonymization
         * When I anonymize all deleted users
         * Then the shipping address of the frontend user is anonymised
         * Then the shipping address of the frontend user is encrypted
         */
        $this->importDataSet(self::FIXTURE_PATH . '/Database/Check50.xml');

        $this->subject->anonymizeAndEncryptAll();
        $this->persistenceManager->persistAll();

        /** @var \Madj2k\FeRegister\Domain\Model\ShippingAddress $shippingAddress */
        $shippingAddress  = $this->shippingAddressRepository->findByUid(50);

        self::assertEquals('Anonymous Anonymous', $shippingAddress->getFullName());

        /** @var \Madj2k\FeRegister\Domain\Model\EncryptedData $encryptedData */
        $encryptedData = $this->encryptedDataRepository->findByUid(2);

        /** @var \Madj2k\FeRegister\Domain\Model\FrontendUser $frontendUser */
        $frontendUser = $this->frontendUserRepository->findByUid(50);

        self::assertInstanceOf(\Madj2k\FeRegister\Domain\Model\EncryptedData::class, $encryptedData);
        self::assertEquals($frontendUser, $encryptedData->getFrontendUser());
        self::assertEquals(hash('sha256', $frontendUser->getEmail()), $encryptedData->getSearchKey());
        self::assertEquals('tx_feregister_domain_model_shippingaddress', $encryptedData->getForeignTable());
        self::assertEquals(\Madj2k\FeRegister\Domain\Model\ShippingAddress::class, $encryptedData->getForeignClass());
        self::assertEquals($shippingAddress->getUid(), $encryptedData->getForeignUid());

    }


    /**
     * @test
     * @throws \Exception
     */
    public function anonymizeAndEncryptAllAnonymizesAndEncryptsHiddenRelatedData()
    {

        /**
         * Scenario:
         *
         * Given there is a frontend user
         * Given this frontend user has a hidden shipping address
         * Given the frontend user has been deleted since more days then configured for anonymization
         * When I anonymize all deleted users
         * Then the shipping address of the frontend user is anonymised
         * Then the shipping address of the frontend user is encrypted
         */
        $this->importDataSet(self::FIXTURE_PATH . '/Database/Check60.xml');

        $this->subject->anonymizeAndEncryptAll();
        $this->persistenceManager->persistAll();

        /** @var \Madj2k\FeRegister\Domain\Model\ShippingAddress $shippingAddress */
        $shippingAddress  = $this->shippingAddressRepository->findByUid(60);

        self::assertEquals('Anonymous Anonymous', $shippingAddress->getFullName());

        /** @var \Madj2k\FeRegister\Domain\Model\EncryptedData $encryptedData */
        $encryptedData = $this->encryptedDataRepository->findByUid(2);

        /** @var \Madj2k\FeRegister\Domain\Model\FrontendUser $frontendUser */
        $frontendUser = $this->frontendUserRepository->findByUid(60);

        self::assertInstanceOf(\Madj2k\FeRegister\Domain\Model\EncryptedData::class, $encryptedData);
        self::assertEquals($frontendUser, $encryptedData->getFrontendUser());
        self::assertEquals(hash('sha256', $frontendUser->getEmail()), $encryptedData->getSearchKey());
        self::assertEquals('tx_feregister_domain_model_shippingaddress', $encryptedData->getForeignTable());
        self::assertEquals(\Madj2k\FeRegister\Domain\Model\ShippingAddress::class, $encryptedData->getForeignClass());
        self::assertEquals($shippingAddress->getUid(), $encryptedData->getForeignUid());
    }


    /**
     * @test
     * @throws \Exception
     */
    public function anonymizeAndEncryptAllAnonymizesIgnoresStoragePidForRelatedData()
    {

        /**
         * Scenario:
         *
         * Given there is a frontend user
         * Given this frontend user has a shipping address with a different storage pid
         * Given the frontend user has been deleted since more days then configured for anonymization
         * When I anonymize all deleted users
         * Then the shipping address of the frontend user is anonymised
         * Then the shipping address of the frontend user is encrypted
         */
        $this->importDataSet(self::FIXTURE_PATH . '/Database/Check70.xml');

        $this->subject->anonymizeAndEncryptAll();
        $this->persistenceManager->persistAll();

        /** @var \Madj2k\FeRegister\Domain\Model\ShippingAddress $shippingAddress */
        $shippingAddress  = $this->shippingAddressRepository->findByUid(70);

        self::assertEquals('Anonymous Anonymous', $shippingAddress->getFullName());

        /** @var \Madj2k\FeRegister\Domain\Model\EncryptedData $encryptedData */
        $encryptedData = $this->encryptedDataRepository->findByUid(2);

        /** @var \Madj2k\FeRegister\Domain\Model\FrontendUser $frontendUser */
        $frontendUser = $this->frontendUserRepository->findByUid(70);

        self::assertInstanceOf(\Madj2k\FeRegister\Domain\Model\EncryptedData::class, $encryptedData);
        self::assertEquals($frontendUser, $encryptedData->getFrontendUser());
        self::assertEquals(hash('sha256', $frontendUser->getEmail()), $encryptedData->getSearchKey());
        self::assertEquals('tx_feregister_domain_model_shippingaddress', $encryptedData->getForeignTable());
        self::assertEquals(\Madj2k\FeRegister\Domain\Model\ShippingAddress::class, $encryptedData->getForeignClass());
        self::assertEquals($shippingAddress->getUid(), $encryptedData->getForeignUid());

    }

    //===================================================================

    /**
     * @test
     * @throws \Exception
     */
    public function anonymizeObjectThrowsExceptionIfFeUserIsNotExisting()
    {

        /**
         * Scenario:
         *
         * Given there is a non persisted frontend user
         * When I anonymize the frontend user
         * Then an error is thrown
         */

        /** @var \Madj2k\FeRegister\Domain\Model\FrontendUser $frontendUser */
        $frontendUser = GeneralUtility::makeInstance(\Madj2k\FeRegister\Domain\Model\FrontendUser::class);

        static::expectException(\Madj2k\FeRegister\Exception::class);

        $this->subject->anonymizeObject($frontendUser, $frontendUser);

    }


    /**
     * @test
     * @throws \Exception
     */
    public function anonymizeObjectDoesNotAnonymizeIfClassIsNotConfigured()
    {

        /**
         * Scenario:
         *
         * Given there is dataset for which no configuration is defined
         * When I anonymize this data
         * Then the data is not anonymized
         */
        $this->importDataSet(self::FIXTURE_PATH . '/Database/Check10.xml');

        /** @var \Madj2k\FeRegister\Domain\Model\FrontendUser $frontendUser */
        $frontendUser = $this->frontendUserRepository->findByUid(10);

        /** @var \Madj2k\FeRegister\Domain\Model\BackendUser $backendUser */
        $backendUser = $this->backendUserRepository->findByUid(10);

        self::assertFalse($this->subject->anonymizeObject($backendUser, $frontendUser));
    }


    /**
     * @test
     * @throws \Exception
     */
    public function anonymizeObjectAnonymizesFrontendUserData()
    {

        /**
         * Scenario:
         *
         * Given there is a frontend user
         * When I anonymize the frontend user
         * Then the user data is anonymized
         */
        $this->importDataSet(self::FIXTURE_PATH . '/Database/Check11.xml');

        /** @var \Madj2k\FeRegister\Domain\Model\FrontendUser $frontendUser */
        $frontendUser = $this->frontendUserRepository->findByUid(11);

        self::assertTrue($this->subject->anonymizeObject($frontendUser, $frontendUser));

        self::assertEquals('anonymous' . $frontendUser->getUid() . '@example.de', $frontendUser->getUsername());
        self::assertEquals('anonymous' . $frontendUser->getUid() . '@example.de', $frontendUser->getEmail());
        self::assertEquals('Anonymous', $frontendUser->getFirstName());
        self::assertEquals('Anonymous', $frontendUser->getLastName());
        self::assertEquals('Anonymous Anonymous', $frontendUser->getName());
        self::assertEquals('', $frontendUser->getCompany());
        self::assertEquals('', $frontendUser->getAddress());
        self::assertEquals('', $frontendUser->getZip());
        self::assertEquals('', $frontendUser->getCity());
        self::assertEquals('', $frontendUser->getTelephone());
        self::assertEquals('', $frontendUser->getFax());
        self::assertEquals('', $frontendUser->getTitle());
        self::assertEquals('', $frontendUser->getWww());
        self::assertEquals(99, $frontendUser->getTxFeregisterGender());
        self::assertEquals('', $frontendUser->getTxFeregisterMobile());
        self::assertEquals('', $frontendUser->getTxFeregisterFacebookUrl());
        self::assertEquals('', $frontendUser->getTxFeregisterTwitterUrl());
        self::assertEquals('', $frontendUser->getTxFeregisterXingUrl());

    }

    //===================================================================

    /**
     * @test
     * @throws \Exception
     */
    public function anonymizeObjectThrowsExceptionIfShippingAddressIsNotExisting()
    {

        /**
         * Scenario:
         *
         * Given there is a non persisted shipping address
         * When I anonymize the shipping address
         * Then an error is thrown
         */
        $this->importDataSet(self::FIXTURE_PATH . '/Database/Check10.xml');

        /** @var \Madj2k\FeRegister\Domain\Model\FrontendUser $frontendUser */
        $frontendUser = $this->frontendUserRepository->findByUid(10);

        /** @var \Madj2k\FeRegister\Domain\Model\ShippingAddress $shippingAddress */
        $shippingAddress = GeneralUtility::makeInstance(\Madj2k\FeRegister\Domain\Model\ShippingAddress::class);

        static::expectException(\Madj2k\FeRegister\Exception::class);

        $this->subject->anonymizeObject($shippingAddress, $frontendUser);

    }


    /**
     * @test
     * @throws \Exception
     */
    public function anonymizeObjectAnonymizesShippingAddressesOfUser()
    {

        /**
         * Scenario:
         *
         * Given there is a shipping address
         * When I anonymize the shipping address
         * Then the shipping address is anonymized
         */
        $this->importDataSet(self::FIXTURE_PATH . '/Database/Check20.xml');

        /** @var \Madj2k\FeRegister\Domain\Model\ShippingAddress $shippingAddress */
        $shippingAddress  = $this->shippingAddressRepository->findByUid(20);

        /** @var \Madj2k\FeRegister\Domain\Model\FrontendUser $frontendUser */
        $frontendUser = $this->frontendUserRepository->findByUid(20);

        self::assertTrue($this->subject->anonymizeObject($shippingAddress, $frontendUser));
        self::assertEquals(99, $shippingAddress->getGender());
        self::assertEquals('Anonymous', $shippingAddress->getFirstName());
        self::assertEquals('Anonymous', $shippingAddress->getLastName());
        self::assertEquals('Anonymous Anonymous', $shippingAddress->getFullName());
        self::assertEquals('', $shippingAddress->getCompany());
        self::assertEquals('', $shippingAddress->getAddress());
        self::assertEquals('', $shippingAddress->getZip());
        self::assertEquals('', $shippingAddress->getCity());

    }

    //===================================================================

    /**
     * @test
     * @throws \Exception
     */
    public function encryptObjectThrowsExceptionIfFeUserIsNotExisting()
    {

        /**
         * Scenario:
         *
         * Given there is a non persisted frontend user
         * When I encrypt the frontend user
         * Then an error is thrown
         */

        /** @var \Madj2k\FeRegister\Domain\Model\FrontendUser $frontendUser */
        $frontendUser = GeneralUtility::makeInstance(\Madj2k\FeRegister\Domain\Model\FrontendUser::class);

        static::expectException(\Madj2k\FeRegister\Exception::class);

        $this->subject->encryptObject($frontendUser, $frontendUser);

    }


    /**
     * @test
     * @throws \Exception
     */
    public function encryptObjectEncryptsFrontendUserData()
    {

        /**
         * Scenario:
         *
         * Given there is a frontend user
         * When I encrypt the frontend user
         * Then the original object is not encrypted
         * Then the encrypted user data is returned
         */
        $this->importDataSet(self::FIXTURE_PATH . '/Database/Check10.xml');

        /** @var \Madj2k\FeRegister\Domain\Model\FrontendUser $frontendUser */
        $frontendUser = $this->frontendUserRepository->findByUid(10);

        $encryptedData = $this->subject->encryptObject($frontendUser, $frontendUser);

        self::assertEquals('spd@test.de', $frontendUser->getUsername());
        self::assertEquals('lauterbach@spd.de', $frontendUser->getEmail());

        self::assertInstanceOf(\Madj2k\FeRegister\Domain\Model\EncryptedData::class, $encryptedData);

        $encryptedDataArray = $encryptedData->getEncryptedData();

        self::assertInstanceOf(\Madj2k\FeRegister\Domain\Model\FrontendUser::class, $encryptedData->getFrontendUser());
        self::assertEquals($frontendUser->getUid(), $encryptedData->getFrontendUser()->getUid());
        self::assertEquals(hash('sha256', $frontendUser->getEmail()), $encryptedData->getSearchKey());
        self::assertEquals($frontendUser->getUid(), $encryptedData->getForeignUid());
        self::assertEquals('fe_users', $encryptedData->getForeignTable());
        self::assertEquals('Madj2k\FeRegister\Domain\Model\FrontendUser', $encryptedData->getForeignClass());

        self::assertCount(21, $encryptedDataArray);
        self::assertEquals(49, strlen($encryptedDataArray['username']));

    }

    //===================================================================

    /**
     * @test
     * @throws \Exception
     */
    public function encryptObjectThrowsExceptionIfShippingAddressIsNotExisting()
    {

        /**
         * Scenario:
         *
         * Given there is a non persisted shipping address
         * When I encrypt the shipping address
         * Then an error is thrown
         */
        $this->importDataSet(self::FIXTURE_PATH . '/Database/Check20.xml');

        /** @var \Madj2k\FeRegister\Domain\Model\FrontendUser $frontendUser */
        $frontendUser = $this->frontendUserRepository->findByUid(20);

        /** @var \Madj2k\FeRegister\Domain\Model\ShippingAddress $shippingAddress */
        $shippingAddress = GeneralUtility::makeInstance(\Madj2k\FeRegister\Domain\Model\ShippingAddress::class);

        static::expectException(\Madj2k\FeRegister\Exception::class);

        $this->subject->encryptObject($shippingAddress, $frontendUser);

    }

    /**
     * @test
     * @throws \Exception
     */
    public function encryptObjectEncryptsShippingAddressesOfUser()
    {

        /**
         * Scenario:
         *
         * Given there is a shipping address
         * When I encrypt the shipping address
         * Then the original object is not encrypted
         * Then the encrypted user data is returned
         */
        $this->importDataSet(self::FIXTURE_PATH . '/Database/Check20.xml');

        /** @var \Madj2k\FeRegister\Domain\Model\ShippingAddress $shippingAddress */
        $shippingAddress  = $this->shippingAddressRepository->findByUid(20);

        /** @var \Madj2k\FeRegister\Domain\Model\FrontendUser $frontendUser */
        $frontendUser = $this->frontendUserRepository->findByUid(20);

        $encryptedData = $this->subject->encryptObject($shippingAddress, $frontendUser);

        self::assertEquals('Karl', $shippingAddress->getFirstName());
        self::assertEquals('Lauterbach', $shippingAddress->getLastName());

        self::assertInstanceOf(\Madj2k\FeRegister\Domain\Model\EncryptedData::class, $encryptedData);
        $encryptedDataArray = $encryptedData->getEncryptedData();

        self::assertInstanceOf(\Madj2k\FeRegister\Domain\Model\FrontendUser::class, $encryptedData->getFrontendUser());
        self::assertEquals($frontendUser->getUid(), $encryptedData->getFrontendUser()->getUid());
        self::assertEquals(hash('sha256', $frontendUser->getEmail()), $encryptedData->getSearchKey());
        self::assertEquals($shippingAddress->getUid(), $encryptedData->getForeignUid());
        self::assertEquals('tx_feregister_domain_model_shippingaddress', $encryptedData->getForeignTable());
        self::assertEquals('Madj2k\FeRegister\Domain\Model\ShippingAddress', $encryptedData->getForeignClass());

        self::assertCount(7, $encryptedDataArray);
        self::assertEquals(49, strlen($encryptedDataArray['firstName']));
        self::assertEquals(49, strlen($encryptedDataArray['lastName']));
    }

    //===================================================================

    /**
     * @test
     * @throws \Exception
     */
    public function encryptObjectThrowsExceptionIfPrivacyDataIsNotExisting()
    {

        /**
         * Scenario:
         *
         * Given there is a non persisted consent data
         * When I encrypt the shipping address
         * Then an error is thrown
         */
        $this->importDataSet(self::FIXTURE_PATH . '/Database/Check25.xml');

        /** @var \Madj2k\FeRegister\Domain\Model\FrontendUser $frontendUser */
        $frontendUser = $this->frontendUserRepository->findByUid(25);

        /** @var \Madj2k\FeRegister\Domain\Model\Consent $consent*/
        $consent = GeneralUtility::makeInstance(\Madj2k\FeRegister\Domain\Model\Consent::class);

        static::expectException(\Madj2k\FeRegister\Exception::class);

        $this->subject->encryptObject($consent, $frontendUser);
    }


    /**
     * @test
     * @throws \Exception
     */
    public function encryptObjectEncryptsPrivacyDataOfUser()
    {

        /**
         * Scenario:
         *
         * Given there is a shipping address
         * When I encrypt the shipping address
         * Then the original object is not encrypted
         * Then the encrypted user data is returned
         */
        $this->importDataSet(self::FIXTURE_PATH . '/Database/Check25.xml');

        /** @var \Madj2k\FeRegister\Domain\Model\Consent $consent */
        $consent  = $this->consentRepository->findByUid(25);

        /** @var \Madj2k\FeRegister\Domain\Model\FrontendUser $frontendUser */
        $frontendUser = $this->frontendUserRepository->findByUid(25);

        $encryptedData = $this->subject->encryptObject($consent, $frontendUser);

        self::assertEquals('172.28.128.1', $consent->getIpAddress());
        self::assertEquals('Mozilla/5.0 (X11; Ubuntu; Linux x86_64; rv:85.0) Gecko/20100101 Firefox/85.0', $consent->getUserAgent());

        self::assertInstanceOf(\Madj2k\FeRegister\Domain\Model\EncryptedData::class, $encryptedData);
        $encryptedDataArray = $encryptedData->getEncryptedData();

        self::assertInstanceOf(\Madj2k\FeRegister\Domain\Model\FrontendUser::class, $encryptedData->getFrontendUser());
        self::assertEquals($frontendUser->getUid(), $encryptedData->getFrontendUser()->getUid());
        self::assertEquals(hash('sha256', $frontendUser->getEmail()), $encryptedData->getSearchKey());
        self::assertEquals($consent->getUid(), $encryptedData->getForeignUid());
        self::assertEquals('tx_feregister_domain_model_consent', $encryptedData->getForeignTable());
        self::assertEquals('Madj2k\FeRegister\Domain\Model\Consent', $encryptedData->getForeignClass());

        self::assertCount(2, $encryptedDataArray);
        self::assertEquals(49, strlen($encryptedDataArray['ipAddress']));
        self::assertEquals(133, strlen($encryptedDataArray['userAgent']));

    }

    //===================================================================

    /**
     * @test
     * @throws \Exception
     */
    public function decryptObjectReturnsNullIfForeignClassDoesNotExist()
    {

        /**
         * Scenario:
         *
         * Given there is an encryptedData-object
         * Given that encryptedData-object has no valid foreignClass set
         * When I decrypt the encryptedData-object
         * Then null is returned
         */
        $this->importDataSet(self::FIXTURE_PATH . '/Database/Check80.xml');

        /** @var \Madj2k\FeRegister\Domain\Model\EncryptedData $encryptedData */
        $encryptedData = $this->encryptedDataRepository->findByUid(80);

        self::assertNull($this->subject->decryptObject($encryptedData, 'lauterbach@spd.de'));

    }


    /**
     * @test
     * @throws \Exception
     */
    public function decryptObjectReturnsNullIfForeignClassHasNoPropertyMap()
    {

        /**
         * Scenario:
         *
         * Given there is an encryptedData-object
         * Given that encryptedData-object has an existing foreignClass set
         * Given that foreignClass has no propertyMap defined
         * When I decrypt the encryptedData-object
         * Then null is returned
         */
        $this->importDataSet(self::FIXTURE_PATH . '/Database/Check90.xml');

        /** @var \Madj2k\FeRegister\Domain\Model\EncryptedData $encryptedData */
        $encryptedData = $this->encryptedDataRepository->findByUid(90);

        self::assertNull($this->subject->decryptObject($encryptedData, 'lauterbach@spd.de'));
    }


    /**
     * @test
     * @throws \Exception
     */
    public function decryptObjectReturnsNullIfForeignUidDoesNotExists()
    {

        /**
         * Scenario:
         *
         * Given there is an encryptedData-object
         * Given that encryptedData-object has an existing foreignClass set
         * Given that foreignClass has no propertyMap defined
         * When I decrypt the encryptedData-object
         * Then null is returned
         */
        $this->importDataSet(self::FIXTURE_PATH . '/Database/Check100.xml');

        /** @var \Madj2k\FeRegister\Domain\Model\EncryptedData $encryptedData */
        $encryptedData = $this->encryptedDataRepository->findByUid(100);

        self::assertNull($this->subject->decryptObject($encryptedData, 'lauterbach@spd.de'));
    }


    /**
     * @test
     * @throws \Exception
     */
    public function decryptObjectReturnsDecryptedObject()
    {

        /**
         * Scenario:
         *
         * Given there is an encryptedData-object
         * Given that encryptedData-object has an existing foreignClass set
         * Given that foreignClass has a propertyMap defined
         * When I decrypt the encryptedData-object
         * Then the decrypted object is returned
         * Then all data has been decrypted again
         */
        $this->importDataSet(self::FIXTURE_PATH . '/Database/Check110.xml');

        /** @var \Madj2k\FeRegister\Domain\Model\EncryptedData $encryptedData */
        $encryptedData = $this->encryptedDataRepository->findByUid(110);

        /** @var \Madj2k\FeRegister\Domain\Model\FrontendUser $frontendUser */
        $frontendUser = $this->frontendUserRepository->findByUid(110);

        /** @var \Madj2k\FeRegister\Domain\Model\FrontendUser $result */
        $result = $this->subject->decryptObject($encryptedData, 'lauterbach@spd.de');

        self::assertInstanceOf(\Madj2k\FeRegister\Domain\Model\FrontendUser::class, $this->subject->decryptObject($encryptedData, 'lauterbach@spd.de'));
        self::assertEquals($frontendUser->getUid(), $result->getUid());
        self::assertEquals('spd@test.de', $result->getUsername());
        self::assertEquals('lauterbach@spd.de', $result->getEmail());
        self::assertEquals('Karl', $result->getFirstName());
        self::assertEquals('Lauterbach', $result->getLastName());
        self::assertEquals('SPD', $result->getCompany());
        self::assertEquals('Straßenring 123', $result->getAddress());
        self::assertEquals('10969', $result->getZip());
        self::assertEquals('Hamburg', $result->getCity());
        self::assertEquals('069/1346', $result->getTelephone());
        self::assertEquals('069/123456789', $result->getFax());
        self::assertEquals('Dr. Prof.', $result->getTitle());
        self::assertEquals('https://www.spd.de', $result->getWww());
        self::assertEquals(1, $result->getTxFeregisterGender());
        self::assertEquals('0179/100224557', $result->getTxFeregisterMobile());
        self::assertEquals('https://www.facebook.com/lauterbach', $result->getTxFeregisterFacebookUrl());
        self::assertEquals('https://www.twitter.com/lauterbach', $result->getTxFeregisterTwitterUrl());
        self::assertEquals('https://www.xing.de/lauterbach', $result->getTxFeregisterXingUrl());

    }

    //===================================================================

    /**
     * @test
     * @throws \TYPO3\CMS\Extbase\Configuration\Exception\InvalidConfigurationTypeException
     */
    public function getPropertyMapByModelClassNameChecksForExistingClasses()
    {

        /**
         * Scenario:
         *
         * Given there is no configuration for a model-class
         * When I try to fetch the propertyMap for this model-class
         * Then an empty array is returned
         */
        self::assertEmpty($this->subject->getPropertyMapByModelClassName('Test\Model'));
    }


    /**
     * @test
     * @throws \TYPO3\CMS\Extbase\Configuration\Exception\InvalidConfigurationTypeException
     */
    public function getPropertyMapByModelClassNameReturnsPropertyMap()
    {

        /**
         * Scenario:
         *
         * Given there is a configuration for a model-class
         * When I try to fetch the propertyMap for this model-class
         * Then the propertyMap is returned
         * Then the propertyMap contains the configured properties and their values
         */

        $result = $this->subject->getPropertyMapByModelClassName('Madj2k\FeRegister\Domain\Model\FrontendUser');
        self::assertIsArray( $result);
        self::assertEquals($result['username'], 'anonymous{UID}@example.de');

    }

    //===================================================================

    /**
     * @test
     * @throws \TYPO3\CMS\Extbase\Persistence\Generic\Exception
     * @throws \TYPO3\CMS\Extbase\Configuration\Exception\InvalidConfigurationTypeException
     */
    public function getFrontendUserPropertyByModelClassNameChecksForExistingClasses()
    {

        /**
         * Scenario:
         *
         * Given there is a non existing model-class
         * When I try to fetch the frontendUserGetter for this model-class
         * Then empty is returned
         */
        self::assertEmpty($this->subject->getFrontendUserPropertyByModelClassName('Test\Model'));
    }


    /**
     * @test
     * @throws \TYPO3\CMS\Extbase\Persistence\Generic\Exception
     * @throws \TYPO3\CMS\Extbase\Configuration\Exception\InvalidConfigurationTypeException
     */
    public function getFrontendUserPropertyByModelClassNameChecksForFrontendUserMethod()
    {

        /**
         * Scenario:
         *
         * Given there is a existing model-class
         * Given this model class has no reference to a frontend user
         * When I try to fetch the frontendUserGetter for this model-class
         * Then empty is returned
         */
        self::assertEmpty($this->subject->getFrontendUserPropertyByModelClassName('Madj2k\FeRegister\Domain\Model\BackendUser'));
    }


    /**
     * @test
     * @throws \TYPO3\CMS\Extbase\Persistence\Generic\Exception
     * @throws \TYPO3\CMS\Extbase\Configuration\Exception\InvalidConfigurationTypeException
    */
    public function getFrontendUserPropertyByModelClassNameChecksForValidReference()
    {

        /**
         * Scenario:
         *
         * Given there is a existing model-class
         * Given this model class has a reference to a frontend user
         * Given the configured mapping field does not refer to the fe_user table
         * When I try to fetch the frontendUserGetter for this model-class
         * Then empty is returned
        */
        self::assertEmpty($this->subject->getFrontendUserPropertyByModelClassName('Madj2k\FeRegister\Domain\Model\Service'));
    }


    /**
     * @test
     * @throws \TYPO3\CMS\Extbase\Persistence\Generic\Exception
     * @throws \TYPO3\CMS\Extbase\Configuration\Exception\InvalidConfigurationTypeException
     */
    public function getFrontendUserPropertyByModelClassNameReturnsGetterMethod()
    {

        /**
         * Scenario:
         *
         * Given there is a existing model-class
         * Given this model class has a reference to a frontend user
         * When I try to fetch the frontendUserGetter for this model-class
         * Then the corresponding frontendUserGetter is returned
         */
        self::assertEquals('frontendUser', $this->subject->getFrontendUserPropertyByModelClassName('Madj2k\FeRegister\Domain\Model\ShippingAddress'));
    }

    //===================================================================

    /**
     * @test
     */
    public function getRepositoryByModelClassNameChecksForExistingClasses()
    {

        /**
         * Scenario:
         *
         * Given there is a non existing model-class
         * When I try to fetch the repository for this model-class
         * Then null is returned
         */
        self::assertNull($this->subject->getRepositoryByModelClassName('Test\Model'));
    }


    /**
     * @test
     */
    public function getRepositoryByModelClassNameReturnsRepository()
    {

        /**
         * Scenario:
         *
         * Given there is an existing model-class
         * When I try to fetch the repository for this model-class
         * Then the corresponding repository is returned
         */
        self::assertInstanceOf(
            \Madj2k\FeRegister\Domain\Repository\ShippingAddressRepository::class,
            $this->subject->getRepositoryByModelClassName('Madj2k\FeRegister\Domain\Model\ShippingAddress')
        );
    }

    //===================================================================

    /**
     * TearDown
     */
    protected function teardown(): void
    {
        parent::tearDown();
    }
}
