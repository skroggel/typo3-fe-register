<?php

namespace Madj2k\FeRegister\DataProtection;

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

use Madj2k\FeRegister\Domain\Model\FrontendUser;
use Madj2k\FeRegister\Domain\Model\OptIn;
use Madj2k\FeRegister\Domain\Model\Consent;
use Madj2k\FeRegister\Domain\Repository\CategoryRepository;
use Madj2k\FeRegister\Domain\Repository\ConsentRepository;
use Madj2k\FeRegister\Domain\Repository\FrontendUserRepository;
use Madj2k\FeRegister\Utility\ClientUtility;
use Madj2k\FeRegister\Utility\FrontendUserSessionUtility;
use Madj2k\FeRegister\ViewHelpers\ConsentViewHelper;
use Madj2k\FeRegister\ViewHelpers\TopicCheckboxViewHelper;
use Madj2k\FeRegister\ViewHelpers\TopicViewHelper;
use TYPO3\CMS\Core\Context\Exception\AspectNotFoundException;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Mvc\Request;
use TYPO3\CMS\Extbase\Object\Exception;
use TYPO3\CMS\Extbase\Object\ObjectManager;
use TYPO3\CMS\Extbase\Persistence\Generic\Mapper\DataMapper;
use TYPO3\CMS\Extbase\Persistence\Generic\PersistenceManager;
use TYPO3\CMS\Extbase\Persistence\ObjectStorage;

/**
 * Class Privacy
 *
 * @author Maximilian Fäßler <maximilian@faesslerweb.de>
 * @author Steffen Kroggel <developer@steffenkroggel.de>
 * @copyright Steffen Kroggel
 * @package Madj2k_FeRegister
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License, version 3 or later
 */
class ConsentHandler implements \TYPO3\CMS\Core\SingletonInterface
{
    /**
     * Constants for extended network
     *
     * @const string
     */
    const CONSENT_EXTENDED_NETWORK = 'extended_network';


    /**
     * setObject
     * Use this function to set basic data
     * The $referenceObject is the element for what the privacy dataset will be created for (e.g. an order, or a new alert) !
     * Hint for optIn (two privacy-entries will be created):
     * 1. The first privacy-dataset of the optIn is created by the registration automatically. If the $dataObject is of type
     *    Madj2k\FeRegister\Domain\Model\Registration it will be automatically identified and set below in $this->setDataObject
     * 2. After successful optIn the 5th param is used to create the relationship between the two created privacy-datasets
     *
     * @param \TYPO3\CMS\Extbase\Mvc\Request $request
     * @param \Madj2k\FeRegister\Domain\Model\FrontendUser $frontendUser
     * @param \TYPO3\CMS\Extbase\DomainObject\AbstractEntity|\TYPO3\CMS\Extbase\Persistence\ObjectStorage|null $referenceObject
     * @param string $comment
     * @return void
     * @throws \TYPO3\CMS\Extbase\Persistence\Exception\IllegalObjectTypeException
     * @throws \TYPO3\CMS\Extbase\Persistence\Exception\UnknownObjectException
     * @throws \TYPO3\CMS\Extbase\Persistence\Generic\Exception
     * @throws \TYPO3\CMS\Extbase\Object\Exception
     * @throws \TYPO3\CMS\Core\Context\Exception\AspectNotFoundException
     */
    protected static function setObject(
        Request $request,
        FrontendUser $frontendUser,
        $referenceObject = null,
        string $comment = ''
    ): Consent {

        /** @var \TYPO3\CMS\Extbase\Object\ObjectManager $objectManager */
        $objectManager = GeneralUtility::makeInstance(ObjectManager::class);

        /** @var \Madj2k\FeRegister\Domain\Model\Consent $consent */
        $consent = GeneralUtility::makeInstance(Consent::class);

        // set frontendUser
        $consent->setFrontendUser($frontendUser);

        // set IP-address
        $consent->setIpAddress(ClientUtility::getIp());

        // set domain name
        $consent->setServerHost(filter_var($_SERVER['HTTP_HOST'], FILTER_SANITIZE_URL));

        // set path of url
        $consent->setServerUri(filter_var($_SERVER['REQUEST_URI'], FILTER_SANITIZE_URL));

        // set referer url
        $consent->setServerRefererUrl(filter_var($_SERVER['HTTP_REFERER'], FILTER_SANITIZE_URL));

        // set userAgent
        $consent->setUserAgent((string) $_SERVER['HTTP_USER_AGENT']);

        // set extension-, plugin-, controller- and action-name
        $consent->setExtensionName((string) $request->getControllerExtensionName());
        $consent->setPluginName((string) $request->getPluginName());
        $consent->setControllerName((string) $request->getControllerName());
        $consent->setActionName((string) $request->getControllerActionName());

        // set consent-fields
        $dirtyFormData = GeneralUtility::_GP(ConsentViewHelper::NAMESPACE);
        foreach (ConsentViewHelper::IDENTIFIERS as $key) {

            $setter = 'setConsent' . ucfirst($key);
            if (isset($dirtyFormData[$key]['confirmed'])) {
                $consent->$setter(intval($dirtyFormData[$key]['confirmed']));
            }
            if (isset($dirtyFormData[$key]['subType'])) {
                $consent->addSubType(htmlspecialchars($dirtyFormData[$key]['subType']));
            }
        }

        // set consent-topics
        $dirtyFormData = GeneralUtility::_GP(TopicCheckboxViewHelper::NAMESPACE);
        if (
            isset($dirtyFormData[TopicCheckboxViewHelper::IDENTIFIER])
            && is_array($dirtyFormData[TopicCheckboxViewHelper::IDENTIFIER])
        ){
            /** @var \Madj2k\FeRegister\Domain\Repository\CategoryRepository $categoryRepository */
            $categoryRepository = $objectManager->get(CategoryRepository::class);
            foreach ($dirtyFormData[TopicCheckboxViewHelper::IDENTIFIER] as $categoryId => $subArray) {

                if ($subArray) {
                    /** @var \Madj2k\FeRegister\Domain\Model\Category $category */
                    $category = $categoryRepository->findByUid(intval($categoryId));
                    if ($category) {
                        $consent->addConsentTopics($category);
                    }
                }
            }
        }

        // set informed consent reason - optional freeText field
        $consent->setComment($comment);

        // set reference object and maybe override it
        self::setReference($consent, $referenceObject);

        // if we have an optIn here, we can differentiate between the optIn and it's approval
        if ($referenceObject instanceof OptIn) {

            // set parent privacy entry in final step on opt-in
            if (
                ($referenceObject->getApproved())
                && ($referenceObject->getAdminApproved())
            ){

                // get former consent-entry via optIn
                /** @var \Madj2k\FeRegister\Domain\Repository\ConsentRepository $consentRepository */
                $consentRepository = $objectManager->get(ConsentRepository::class);
                $consentParent = $consentRepository->findOneByOptIn($referenceObject);
                if ($consentParent) {
                    $consent->setParent($consentParent);
                    $consentParent->unsetOptIn();
                    $consentRepository->update($consentParent);
                }

                // set consent in frontendUser
                /** @var \Madj2k\FeRegister\Domain\Repository\FrontendUserRepository $frontendUserRepository */
                $frontendUserRepository = $objectManager->get(FrontendUserRepository::class);
                if ($consentParent) {
                    $doReset = (bool) ($referenceObject->getFrontendUserUpdate() && !$referenceObject->getData());
                    self::setConsentProperties($consentParent, $frontendUser, $doReset);
                }
                $frontendUserRepository->update($frontendUser);

            // not yet confirmed: set optIn for child-parent-relation after optIn-confirmation
            } else {
                $consent->setOptIn($referenceObject);
            }

            // override reference-infos with object-infos from optIn
            if ($referenceObject->getForeignTable()) {
                $consent->setForeignTable($referenceObject->getForeignTable());
                $consent->setForeignUid($referenceObject->getForeignUid());
            }
            if ($referenceObject->getParentForeignTable()) {
                $consent->setForeignTable($referenceObject->getParentForeignTable());
                $consent->setForeignUid($referenceObject->getParentForeignUid());
            }

        // normal object
        } else {

            // set consent in frontendUser
            /** @var \Madj2k\FeRegister\Domain\Repository\FrontendUserRepository $frontendUserRepository */
            $frontendUserRepository = $objectManager->get(FrontendUserRepository::class);
            self::setConsentProperties($consent, $frontendUser);

            $frontendUserRepository->update($frontendUser);
        }

        return $consent;
    }


    /**
     * @param \Madj2k\FeRegister\Domain\Model\Consent $consent
     * @param \Madj2k\FeRegister\Domain\Model\FrontendUser $frontendUser
     * @param bool $reset
     * @return void
     * @throws \TYPO3\CMS\Core\Context\Exception\AspectNotFoundException
     */
    protected static function setConsentProperties(
        \Madj2k\FeRegister\Domain\Model\Consent $consent,
        \Madj2k\FeRegister\Domain\Model\FrontendUser $frontendUser,
        bool $reset = false
    ): void {

        if ($consent->getConsentPrivacy()) {
            $frontendUser->setTxFeregisterConsentPrivacy($consent->getConsentPrivacy());
        }
        if ($consent->getConsentTerms()) {
            $frontendUser->setTxFeregisterConsentTerms($consent->getConsentTerms());
        }
        if ($consent->getConsentMarketing() || $reset) {
            $frontendUser->setTxFeregisterConsentMarketing($consent->getConsentMarketing());
        }

        if ($consent->getConsentTopics()) {

            // if user is logged in, reset selection because in that case the selected topics have been shown in frontend
            if (FrontendUserSessionUtility::isUserLoggedIn($frontendUser) || $reset) {
                $frontendUser->setTxFeregisterConsentTopics(GeneralUtility::makeInstance(ObjectStorage::class));
            }

            /** @var \Madj2k\FeRegister\Domain\Model\Category  $category */
            foreach ($consent->getConsentTopics() as $category) {
                $frontendUser->addTxFeregisterConsentTopics($category);
            }
        }
    }


    /**
     * setReference
     *
     * @param \Madj2k\FeRegister\Domain\Model\Consent $consent
     * @param \TYPO3\CMS\Extbase\DomainObject\AbstractEntity|\TYPO3\CMS\Extbase\Persistence\ObjectStorage|null $referenceObject
     * @return void
     * @throws \TYPO3\CMS\Extbase\Persistence\Generic\Exception
     * @throws \TYPO3\CMS\Extbase\Object\Exception
     */
    protected static function setReference(
        Consent $consent,
       $referenceObject = null
    ): void {

        /** @var \TYPO3\CMS\Extbase\Object\ObjectManager $objectManager */
        $objectManager = GeneralUtility::makeInstance(ObjectManager::class);

        /** @var DataMapper $dataMapper */
        $dataMapper = $objectManager->get(DataMapper::class);

        if ($referenceObject instanceof \TYPO3\CMS\Extbase\Persistence\ObjectStorage) {

            $referenceTemp = $referenceObject->current();
            if ($referenceTemp instanceof \TYPO3\CMS\Extbase\DomainObject\AbstractEntity) {
                $consent->setForeignTable(
                    $dataMapper->getDataMap(get_class($referenceTemp))->getTableName(),
                );
            }

            $ids = [];
            foreach ($referenceObject as $referenceTemp) {
                if ($referenceTemp instanceof \TYPO3\CMS\Extbase\DomainObject\AbstractEntity) {
                    $ids[] = $referenceTemp->getUid();
                }
            }

            $consent->setForeignUid(implode(',', $ids));

            // else we determine the concrete foreignTable and foreignUid
        } else if ($referenceObject instanceof \TYPO3\CMS\Extbase\DomainObject\AbstractEntity) {

            $consent->setForeignTable(
                $dataMapper->getDataMap(get_class($referenceObject))->getTableName(),
            );
            $consent->setForeignUid($referenceObject->getUid() ?? 0);
        }
    }


    /**
     * add consent data
     *
     * @param \TYPO3\CMS\Extbase\Mvc\Request $request
     * @param \Madj2k\FeRegister\Domain\Model\FrontendUser $frontendUser
     * @param \TYPO3\CMS\Extbase\DomainObject\AbstractEntity|\TYPO3\CMS\Extbase\Persistence\ObjectStorage|null $referenceObject
     * @param string $comment
     * @return \Madj2k\FeRegister\Domain\Model\Consent
     * @throws \TYPO3\CMS\Extbase\Persistence\Exception\IllegalObjectTypeException
     * @throws \TYPO3\CMS\Extbase\Persistence\Exception\UnknownObjectException
     * @throws \TYPO3\CMS\Extbase\Persistence\Generic\Exception
     * @throws \TYPO3\CMS\Extbase\Object\Exception
     * @api
     */
    public static function add
    (
        Request $request,
        FrontendUser $frontendUser,
        $referenceObject = null,
        string $comment = ''
    ): Consent {

        $consent = self::setObject($request, $frontendUser, $referenceObject, $comment);

        /** @var \TYPO3\CMS\Extbase\Object\ObjectManager $objectManager */
        $objectManager = GeneralUtility::makeInstance(ObjectManager::class);

        /** @var \Madj2k\FeRegister\Domain\Repository\ConsentRepository $consentRepository */
        $consentRepository = $objectManager->get(ConsentRepository::class);
        $consentRepository->add($consent);

        /** @var \TYPO3\CMS\Extbase\Persistence\Generic\PersistenceManager  $persistenceManager */
        $persistenceManager = $objectManager->get(PersistenceManager::class);
        $persistenceManager->persistAll();

        return $consent;
    }
}
