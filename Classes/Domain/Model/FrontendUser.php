<?php
namespace Madj2k\FeRegister\Domain\Model;

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

use Madj2k\Postmaster\Utility\FrontendLocalizationUtility;
use Madj2k\FeRegister\Domain\Repository\TitleRepository;
use TYPO3\CMS\Extbase\Object\ObjectManager;
use TYPO3\CMS\Extbase\Persistence\ObjectStorage;

/**
 * Class FrontendUser
 *
 * @author Maximilian Fäßler <maximilian@faesslerweb.de>
 * @author Steffen Kroggel <developer@steffenkroggel.de>
 * @copyright Steffen Kroggel
 * @package Madj2k_FeRegister
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License, version 3 or later
 *
 */
class FrontendUser extends \Madj2k\CoreExtended\Domain\Model\FrontendUser
{

    /**
     * !!!! THIS SHOULD NEVER BE PERSISTED !!!!
     *
     * @var string
     */
    protected string $tempPlaintextPassword = '';


    /**
     * !!!! THIS SHOULD NEVER BE PERSISTED !!!!
     *
     * @var \Madj2k\FeRegister\Domain\Model\FrontendUserGroup|null
     */
    protected ?FrontendUserGroup $tempFrontendUserGroup = null;


    /**
     * @var string
     */
    protected string $txFeregisterMobile = '';


    /**
     * @var int
     */
    protected int $txFeregisterGender = 99;


    /**
     * @var string
     */
    protected string $txFeregisterRegisterRemoteIp = '';


    /**
     * @var int
     */
    protected int $txFeregisterLoginErrorCount = 0;


    /**
     * @var string
     */
    protected string $txFeregisterLanguageKey = '';


    /**
     * @var string
     */
    protected string $txFeregisterFacebookUrl = '';


    /**
     * @var string
     */
    protected string $txFeregisterTwitterUrl = '';


    /**
     * @var string
     */
    protected string $txFeregisterXingUrl = '';


    /**
     * @var int
     */
    protected int $txFeregisterDataProtectionStatus = 0;


    /**
     * @var bool
     */
    protected bool $txFeregisterConsentPrivacy = false;


    /**
     * @var bool
     */
    protected bool $txFeregisterConsentTerms = false;


    /**
     * @var bool
     */
    protected bool $txFeregisterConsentMarketing = false;


    /**
     * @var \TYPO3\CMS\Extbase\Persistence\ObjectStorage<\TYPO3\CMS\Extbase\Domain\Model\Category>|null
     */
    protected ?ObjectStorage $txFeregisterConsentTopics = null;


    /**
     * @var \Madj2k\FeRegister\Domain\Model\Title|null
     */
    protected ?Title $txFeregisterTitle = null;



    /**
     * __construct
     */
    public function __construct()
    {
        parent::__construct();

        //Do not remove the next line: It would break the functionality
        $this->initStorageObjects();
    }

    /**
     * Initializes all ObjectStorage properties
     * Do not modify this method!
     * It will be rewritten on each save in the extension builder
     * You may modify the constructor of this class instead
     *
     * @return void
     */
    protected function initStorageObjects()
    {
        $this->txFeregisterConsentTopics = new \TYPO3\CMS\Extbase\Persistence\ObjectStorage();
    }


    /**
     * Gets the plaintext password
     * !!! SHOULD NEVER BE PERSISTED!!!
     *
     * @return string
     * @api
     */
    public function getTempPlaintextPassword(): string
    {
        return $this->tempPlaintextPassword;
    }


    /**
     * Sets the plaintext password
     * !!! SHOULD NEVER BE PERSISTED!!!
     *
     * @param string $tempPlaintextPassword
     * @api
     */
    public function setTempPlaintextPassword(string $tempPlaintextPassword): void
    {
        $this->tempPlaintextPassword = $tempPlaintextPassword;
    }


    /**
     * Gets the tempFrontendUserGroup
     * !!! SHOULD NEVER BE PERSISTED!!!
     *
     * @return \Madj2k\FeRegister\Domain\Model\FrontendUserGroup|null
     * @api
     */
    public function getTempFrontendUserGroup(): ?\Madj2k\FeRegister\Domain\Model\FrontendUserGroup
    {
        return $this->tempFrontendUserGroup;
    }


    /**
     * Sets the tempFrontendUserGroup
     * !!! SHOULD NEVER BE PERSISTED!!!
     *
     * @param \Madj2k\FeRegister\Domain\Model\FrontendUserGroup $tempFrontendUserGroup
     * @api
     */
    public function setTempFrontendUserGroup(FrontendUserGroup $tempFrontendUserGroup): void
    {
        $this->tempFrontendUserGroup = $tempFrontendUserGroup;
    }


    /**
     * Sets the mobile value
     *
     * @param string $mobile
     * @return void
     * @api
     */
    public function setTxFeregisterMobile(string $mobile): void
    {
        $this->txFeregisterMobile = $mobile;
    }


    /**
     * Returns the mobile value
     *
     * @return string
     * @api
     */
    public function getTxFeregisterMobile(): string
    {
        return $this->txFeregisterMobile;
    }


    /**
     * Sets the gender value
     *
     * @param int $gender
     * @return void
     * @api
     */
    public function setTxFeregisterGender(int $gender): void
    {
        $this->txFeregisterGender = $gender;
    }


    /**
     * Returns the gender value
     *
     * @return int
     * @api
     */
    public function getTxFeregisterGender(): int
    {
        return $this->txFeregisterGender;
    }


    /**
     * Sets the registerRemoteIp value
     *
     * @param string $remoteIp
     * @return void
     *
     */
    public function setTxFeregisterRegisterRemoteIp(string $remoteIp): void
    {
        $this->txFeregisterRegisterRemoteIp = $remoteIp;
    }


    /**
     * Returns the registerRemoteIp value
     *
     * @return string
     *
     */
    public function getTxFeregisterRegisterRemoteIp(): string
    {
        return $this->txFeregisterRegisterRemoteIp;
    }


    /**
     * Sets the loginErrorCount value
     *
     * @param int $count
     * @return void
     *
     */
    public function setTxFeregisterLoginErrorCount(int $count): void
    {
        $this->txFeregisterLoginErrorCount = $count;
    }


    /**
     * Returns the loginErrorCount value
     *
     * @return int
     *
     */
    public function getTxFeregisterLoginErrorCount(): int
    {
        return $this->txFeregisterLoginErrorCount;
    }


    /**
     * Sets the txFeregisterLanguageKey value
     *
     * @param string $languageKey
     * @return void
     *
     */
    public function setTxFeregisterLanguageKey(string $languageKey): void
    {
        $this->txFeregisterLanguageKey = $languageKey;
    }


    /**
     * Returns the txFeregisterLanguageKey value
     *
     * @return string
     *
     */
    public function getTxFeregisterLanguageKey(): string
    {
        return $this->txFeregisterLanguageKey;
    }


    /**
     * Sets the facebookUrl value
     *
     * @param string $facebookUrl
     * @return void
     * @api
     */
    public function setTxFeregisterFacebookUrl(string $facebookUrl): void
    {
        $this->txFeregisterFacebookUrl = $facebookUrl;
    }


    /**
     * Returns the facebookUrl value
     *
     * @return string
     * @api
     */
    public function getTxFeregisterFacebookUrl(): string
    {
        return $this->txFeregisterFacebookUrl;
    }


    /**
     * Sets the twitterUrl value
     *
     * @param string $twitter
     * @return void
     * @api
     */
    public function setTxFeregisterTwitterUrl(string $twitter): void
    {
        $this->txFeregisterTwitterUrl = $twitter;
    }


    /**
     * Returns the twitterUrl value
     *
     * @return string
     * @api
     */
    public function getTxFeregisterTwitterUrl(): string
    {
        return $this->txFeregisterTwitterUrl;
    }


    /**
     * Sets the xingUrl value
     *
     * @param string $twitter
     * @return void
     * @api
     */
    public function setTxFeregisterXingUrl(string $twitter): void
    {
        $this->txFeregisterXingUrl = $twitter;
    }


    /**
     * Returns the xingUrl value
     *
     * @return string
     * @api
     */
    public function getTxFeregisterXingUrl(): string
    {
        return $this->txFeregisterXingUrl;
    }


    /**
     * Sets the txFeregisterDataProtectionStatus value
     *
     * @param int $txFeregisterDataProtectionStatus
     * @return void
     *
     */
    public function setTxFeregisterDataProtectionStatus(int $txFeregisterDataProtectionStatus): void
    {
        $this->txFeregisterDataProtectionStatus = $txFeregisterDataProtectionStatus;
    }


    /**
     * Returns the txFeregisterDataProtectionStatus value
     * @return int
     */
    public function getTxFeregisterDataProtectionStatus(): int
    {
        return $this->txFeregisterDataProtectionStatus;
    }


    /**
     * Sets the txFeregisterConsentTerms value
     *
     * @param bool $txFeregisterConsentPrivacy
     * @return void
     *
     */
    public function setTxFeregisterConsentPrivacy(bool $txFeregisterConsentPrivacy): void
    {
        $this->txFeregisterConsentPrivacy = $txFeregisterConsentPrivacy;
    }


    /**
     * Returns the txFeregisterConsentPrivacyvalue
     * @return bool
     */
    public function getTxFeregisterConsentPrivacy(): bool
    {
        return $this->txFeregisterConsentPrivacy;
    }



    /**
     * Sets the txFeregisterConsentTerms value
     *
     * @param bool $txFeregisterConsentTerms
     * @return void
     *
     */
    public function setTxFeregisterConsentTerms(bool $txFeregisterConsentTerms): void
    {
        $this->txFeregisterConsentTerms = $txFeregisterConsentTerms;
    }


    /**
     * Returns the txFeregisterConsentTerms value
     * @return bool
     */
    public function getTxFeregisterConsentTerms(): bool
    {
        return $this->txFeregisterConsentTerms;
    }


    /**
     * Sets the txFeregisterConsentMarketing value
     *
     * @param bool $txFeregisterConsentMarketing
     * @return void
     *
     */
    public function setTxFeregisterConsentMarketing(bool $txFeregisterConsentMarketing): void
    {
        $this->txFeregisterConsentMarketing = $txFeregisterConsentMarketing;
    }


    /**
     * Returns the txFeregisterConsentMarketing value
     * @return bool
     */
    public function getTxFeregisterConsentMarketing(): bool
    {
        return $this->txFeregisterConsentMarketing;
    }


    /**
     * Adds a txFeregisterConsentTopics
     *
     * @param \TYPO3\CMS\Extbase\Domain\Model\Category $txFeregisterConsentTopics
     * @return void
     */
    public function addTxFeregisterConsentTopics(\TYPO3\CMS\Extbase\Domain\Model\Category $txFeregisterConsentTopics)
    {
        $this->txFeregisterConsentTopics->attach($txFeregisterConsentTopics);
    }


    /**
     * Removes a txFeregisterConsentTopics
     *
     * @param \TYPO3\CMS\Extbase\Domain\Model\Category $txFeregisterConsentTopicsToRemove The Category to be removed
     * @return void
     */
    public function removeTxFeregisterConsentTopics(\TYPO3\CMS\Extbase\Domain\Model\Category $txFeregisterConsentTopics)
    {
        $this->txFeregisterConsentTopics->detach($txFeregisterConsentTopics);
    }


    /**
     * Returns the txFeregisterConsentTopics
     *
     * @return \TYPO3\CMS\Extbase\Persistence\ObjectStorage<\TYPO3\CMS\Extbase\Domain\Model\Category> $txFeregisterConsentTopics
     */
    public function getTxFeregisterConsentTopics()
    {
        return $this->txFeregisterConsentTopics;
    }


    /**
     * Sets the txFeregisterConsentTopics
     *
     * @param \TYPO3\CMS\Extbase\Persistence\ObjectStorage<\TYPO3\CMS\Extbase\Domain\Model\Category> $txFeregisterConsentTopics
     * @return void
     */
    public function setTxFeregisterConsentTopics(\TYPO3\CMS\Extbase\Persistence\ObjectStorage $txFeregisterConsentTopics)
    {
        $this->txFeregisterConsentTopics = $txFeregisterConsentTopics;
    }


    //=================================================================================
    // Special-methods that are NOT simply getter or setter below
    //=================================================================================
    /**
     * Returns the txFeregisterTitle
     *
     * @return \Madj2k\FeRegister\Domain\Model\Title $txFeregisterTitle
     */
    public function getTxFeregisterTitle(): Title
    {
        if ($this->txFeregisterTitle === null) {
            $txFeregisterTitle = new Title();
            $txFeregisterTitle->setName($this->getTitle());

            return $txFeregisterTitle;
        }

        return $this->txFeregisterTitle;
    }


    /**
     * Sets the txFeregisterTitle
     *
     * Hint: default "null" is needed to make value in forms optional
     *
     * @param \Madj2k\FeRegister\Domain\Model\Title|null $txFeregisterTitle
     * @return void
     */
    public function setTxFeregisterTitle(Title $txFeregisterTitle = null): void
    {
        if (
            ($txFeregisterTitle)
            && ($txFeregisterTitle->getName() !== '')
        ){
            /** @var \TYPO3\CMS\Extbase\Object\ObjectManager $objectManager */
            $objectManager = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(ObjectManager::class);
            /** @var \Madj2k\FeRegister\Domain\Repository\TitleRepository $titleRepository */
            $titleRepository = $objectManager->get(TitleRepository::class);

            if ($existingTitle = $titleRepository->findOneByName($txFeregisterTitle->getName())) {
                $this->txFeregisterTitle = $existingTitle;
            } else {
                $this->setTitle($txFeregisterTitle->getName());
            }
        }
    }


    /**
     * Increments the loginErrorCount value
     *
     * @return void
     */
    public function incrementTxFeregisterLoginErrorCount(): void
    {
        $this->txFeregisterLoginErrorCount++;
    }


    /**
     * Returns the gender as string
     *
     * @return string
     */
    public function getGenderText(): string
    {
        if ($this->getTxFeregisterGender() < 99) {

            return FrontendLocalizationUtility::translate(
                'tx_feregister_domain_model_frontenduser.tx_feregister_gender.' . $this->getTxFeregisterGender(),
                'fe_register',
                [],
                $this->getTxFeregisterLanguageKey()
            )?: '';
        }

        return '';
    }

    /**
     * Returns the full salutation including gender, title and name
     *
     * @param bool $checkIncludedInSalutation
     * @return string
     */
    public function getCompleteSalutationText(bool $checkIncludedInSalutation = false): string
    {
        $fullSalutation = $this->getFirstName() . ' ' . $this->getLastName();
        $title = $this->getTxFeregisterTitle();

        if ($title && $title->getName()) {

            $titleName = ($this->getTxFeregisterGender() === 1 && $title->getNameFemale()) ? $title->getNameFemale() : $title->getName();
            if ($checkIncludedInSalutation) {
                if ($title->getIsIncludedInSalutation()) {
                    $fullSalutation = ($title->getIsTitleAfter()) ? $fullSalutation . ', ' . $titleName : $titleName . ' ' . $fullSalutation;
                }
            } else {
                $fullSalutation = ($title->getIsTitleAfter()) ? $fullSalutation . ', ' . $titleName : $titleName . ' ' . $fullSalutation;
            }
        }

        if ($this->getGenderText()) {
            $fullSalutation = $this->getGenderText() . ' ' . $fullSalutation;
        }

        return $fullSalutation;
    }


    /**
     * Returns the title as text
     *
     * @param bool $titleAfter
     * @return string
     */
    public function getTitleText(bool $titleAfter = false): string
    {

        if ($this->getTxFeregisterTitle()) {

            if ($this->getTxFeregisterTitle()->getIsTitleAfter() == $titleAfter) {
                return $this->getTxFeregisterTitle()->getName();
            }
        }

        if (!is_numeric($this->getTitle())) {
            return $this->getTitle();
        }

        return '';
    }


}
