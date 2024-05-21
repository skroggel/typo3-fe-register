<?php
namespace Madj2k\FeRegister\Registration;

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

use Madj2k\CoreExtended\Utility\GeneralUtility;
use Madj2k\CoreExtended\Utility\SiteUtility;
use Madj2k\FeRegister\DataProtection\ConsentHandler;
use Madj2k\FeRegister\Domain\Model\BackendUser;
use Madj2k\FeRegister\Domain\Model\FrontendUser;
use Madj2k\FeRegister\Domain\Model\FrontendUserGroup;
use Madj2k\FeRegister\Domain\Model\GuestUser;
use Madj2k\FeRegister\Domain\Model\OptIn;
use Madj2k\FeRegister\Domain\Repository\FrontendUserGroupRepository;
use Madj2k\FeRegister\Domain\Repository\FrontendUserRepository;
use Madj2k\FeRegister\Domain\Repository\GuestUserRepository;
use Madj2k\FeRegister\Domain\Repository\OptInRepository;
use Madj2k\FeRegister\Exception;
use Madj2k\FeRegister\Utility\ClientUtility;
use Madj2k\FeRegister\Utility\FrontendUserSessionUtility;
use Madj2k\FeRegister\Utility\FrontendUserUtility;
use Madj2k\FeRegister\Utility\PasswordUtility;
use Madj2k\FeRegister\Utility\TitleUtility;
use TYPO3\CMS\Core\Log\Logger;
use TYPO3\CMS\Core\Log\LogLevel;
use TYPO3\CMS\Core\Log\LogManager;
use TYPO3\CMS\Extbase\DomainObject\AbstractEntity;
use TYPO3\CMS\Extbase\Mvc\Request;
use TYPO3\CMS\Extbase\Object\ObjectManager;
use TYPO3\CMS\Extbase\Persistence\Generic\Mapper\DataMapper;
use TYPO3\CMS\Extbase\Persistence\Generic\PersistenceManager;
use TYPO3\CMS\Extbase\Persistence\ObjectStorage;
use TYPO3\CMS\Extbase\SignalSlot\Dispatcher;
use TYPO3\CMS\Extbase\Utility\DebuggerUtility;

/**
 * Class AbstractRegistration
 *
 * @author Steffen Kroggel <developer@steffenkroggel.de>
 * @copyright Steffen Kroggel
 * @package Madj2k_FeRegister
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License, version 3 or later
 */
abstract class AbstractRegistration implements RegistrationInterface
{

    /**
     * Signal name for use in ext_localconf.php
     *
     * @const string
     */
    const SIGNAL_AFTER_CREATING_OPTIN = 'afterCreatingOptin';


    /**
     * Signal name for use in ext_localconf.php
     *
     * @const string
     */
    const SIGNAL_AFTER_CREATING_OPTIN_ADMIN = 'afterCreatingOptinAdmin';


    /**
     * Signal name for use in ext_localconf.php
     *
     * @const string
     */
    const SIGNAL_AFTER_APPROVAL_OPTIN = 'afterApprovalOptin';


    /**
     * Signal name for use in ext_localconf.php
     *
     * @const string
     */
    const SIGNAL_AFTER_APPROVAL_OPTIN_ADMIN = 'afterApprovalOptinAdmin';


    /**
     * Signal name for use in ext_localconf.php
     *
     * @const string
     */
    const SIGNAL_AFTER_DENIAL_OPTIN = 'afterDenialOptin';


    /**
     * Signal name for use in ext_localconf.php
     *
     * @const string
     */
    const SIGNAL_AFTER_DENIAL_OPTIN_ADMIN = 'afterDenialOptinAdmin';


    /**
     * Signal name for use in ext_localconf.php
     *
     * @const string
     */
    const SIGNAL_AFTER_REGISTRATION_COMPLETED = 'afterRegistrationCompleted';


    /**
     * Signal name for use in ext_localconf.php
     *
     * @const string
     */
    const SIGNAL_AFTER_REGISTRATION_CANCELED = 'afterRegistrationCanceled';


    /**
     * Signal name for use in ext_localconf.php
     *
     * @const string
     */
    const SIGNAL_AFTER_REGISTRATION_ENDED = 'afterRegistrationEnded';


    /**
     * @var \Madj2k\FeRegister\Domain\Model\FrontendUser|null
     */
    protected ?FrontendUser $frontendUser = null;


    /**
     * @var \Madj2k\FeRegister\Domain\Model\FrontendUser|null
     */
    protected ?FrontendUser $frontendUserPersisted = null;


    /**
     * @var string
     */
    protected string $frontendUserToken = '';


    /**
     * @var array
     */
    protected array $frontendUserOptInUpdate = [];


    /**
     * @var \Madj2k\FeRegister\Domain\Model\OptIn|null
     */
    protected ?OptIn $optInPersisted = null;


    /**
     * @var \TYPO3\CMS\Extbase\Mvc\Request|null
     */
    protected ?Request $request = null;


    /**
     * @var \TYPO3\CMS\Extbase\Persistence\ObjectStorage<\Madj2k\FeRegister\Domain\Model\BackendUser>|null
     */
    protected ?ObjectStorage $approval = null;


    /**
     * @var mixed
     */
    protected $data;


    /**
     * @var mixed
     */
    protected $dataParent;


    /**
     * @var string
     */
    protected string $category = '';


    /**
     * @var \Madj2k\FeRegister\Domain\Repository\OptInRepository
     * @TYPO3\CMS\Extbase\Annotation\Inject
     */
    protected ?OptInRepository $optInRepository = null;


    /**
     * @var \Madj2k\FeRegister\Domain\Repository\FrontendUserRepository
     * @TYPO3\CMS\Extbase\Annotation\Inject
     */
    protected ?FrontendUserRepository $frontendUserRepository = null;


    /**
     * @var \Madj2k\FeRegister\Domain\Repository\GuestUserRepository
     * @TYPO3\CMS\Extbase\Annotation\Inject
     */
    protected ?GuestUserRepository $guestUserRepository = null;


    /**
     * @var \Madj2k\FeRegister\Domain\Repository\FrontendUserGroupRepository
     * @TYPO3\CMS\Extbase\Annotation\Inject
     */
    protected ?FrontendUserGroupRepository $frontendUserGroupRepository = null;


    /**
     * @var \TYPO3\CMS\Extbase\Persistence\Generic\PersistenceManager
     * @TYPO3\CMS\Extbase\Annotation\Inject
     */
    protected ?PersistenceManager $persistenceManager = null;


    /**
     * @var \TYPO3\CMS\Extbase\Object\ObjectManager
     * @TYPO3\CMS\Extbase\Annotation\Inject
     */
    protected ?ObjectManager $objectManager = null;


    /**
     * @var \TYPO3\CMS\Extbase\SignalSlot\Dispatcher
     * @TYPO3\CMS\Extbase\Annotation\Inject
     */
    protected ?Dispatcher $signalSlotDispatcher = null;


    /**
     * @var array
     */
    protected array $settings = [];


    /**
     * @var Logger|null
     */
    protected ?Logger $logger = null;


    /**
     * @var \Madj2k\FeRegister\Domain\Repository\OptInRepository
     */
    public function injectOptInRepository(OptInRepository $optInRepository)
    {
        $this->optInRepository = $optInRepository;
    }


    /**
     * @var \Madj2k\FeRegister\Domain\Repository\FrontendUserRepository
     */
    public function injectFrontendUserRepository(FrontendUserRepository $frontendUserRepository)
    {
        $this->frontendUserRepository = $frontendUserRepository;
    }


    /**
     * @var \Madj2k\FeRegister\Domain\Repository\GuestUserRepository
     */
    public function injectGuestUserRepository(GuestUserRepository $guestUserRepository)
    {
        $this->guestUserRepository = $guestUserRepository;
    }


    /**
     * @var \Madj2k\FeRegister\Domain\Repository\FrontendUserGroupRepository
     */
    public function injectFrontendUserGroupRepository(FrontendUserGroupRepository $frontendUserGroupRepository)
    {
        $this->frontendUserGroupRepository = $frontendUserGroupRepository;
    }


    /**
     * @var \TYPO3\CMS\Extbase\Persistence\Generic\PersistenceManager
     */
    public function injectPersistenceManager(PersistenceManager $persistenceManager)
    {
        $this->persistenceManager = $persistenceManager;
    }


    /**
     * @var \TYPO3\CMS\Extbase\Object\ObjectManager
     */
    public function injectObjectManager(ObjectManager $objectManager)
    {
        $this->objectManager = $objectManager;
    }


    /**
     * @var \TYPO3\CMS\Extbase\SignalSlot\Dispatcher
     */
    public function injectDispatcher(Dispatcher $dispatcher)
    {
        $this->signalSlotDispatcher = $dispatcher;
    }


    /**
     * __construct
     * @throws \TYPO3\CMS\Extbase\Configuration\Exception\InvalidConfigurationTypeException
     */
    public function __construct()
    {
        $this->approval = new \TYPO3\CMS\Extbase\Persistence\ObjectStorage();
        $this->getSettings();
    }


    /**
     * @return \Madj2k\FeRegister\Domain\Model\FrontendUser|null $frontendUser
     */
    public function getFrontendUser(): ?FrontendUser
    {
        return $this->frontendUser;
    }


    /**
     * Sets the frontendUser
     *
     * @var \Madj2k\FeRegister\Domain\Model\FrontendUser $frontendUser
     * @return self
     * @throws Exception
     * @throws \TYPO3\CMS\Core\Context\Exception\AspectNotFoundException
     * @throws \TYPO3\CMS\Extbase\Configuration\Exception\InvalidConfigurationTypeException
     * @throws \TYPO3\CMS\Extbase\Persistence\Exception\IllegalObjectTypeException
     */
    public function setFrontendUser(FrontendUser $frontendUser): self
    {
        // check if a user is logged in. In this case the given user has to be same!
        // but only, if it is a "real" frontendUser and not a faggot guestUser
        if (
            ($frontendUserUid = FrontendUserSessionUtility::getLoggedInUserId())
            && (! FrontendUserUtility::isGuestUser(FrontendUserSessionUtility::getLoggedInUser()))
        ){

            if ($frontendUser->getUid() != $frontendUserUid) {
                throw new Exception(
                    'The given frontendUser is not identical with the user that is currently logged in.',
                    1666014579
                );
            }
        }

        /** @todo auslagern in reset-Methode */
        $this->frontendUser = $frontendUser;
        $this->frontendUserToken = '';
        $this->frontendUserPersisted = null;
        $this->optInPersisted = null;

        $this->prepareFrontendUser();

        return $this;
    }


    /**
     * Get the frontendUserToken
     *
     * @return string
     */
    public function getFrontendUserToken(): string
    {
        return $this->frontendUserToken;
    }


    /**
     * Set the frontendUserToken
     *
     * @param string $frontendUserToken
     * @return self
     * @throws Exception
     * @throws \TYPO3\CMS\Core\Context\Exception\AspectNotFoundException
     * @throws \TYPO3\CMS\Extbase\Persistence\Exception\InvalidQueryException
     */
    public function setFrontendUserToken(string $frontendUserToken): self
    {
        // check if a user is logged in. In this case the given user has to be same!
        // but only, if it is a "real" frontendUser and not a faggot guestUser
        if (
            ($frontendUserUid = FrontendUserSessionUtility::getLoggedInUserId())
            && (! FrontendUserUtility::isGuestUser(FrontendUserSessionUtility::getLoggedInUser()))
        ){

            /** @var \Madj2k\FeRegister\Domain\Model\OptIn optIn */
            $optIn = $this->optInRepository->findOneByTokenUserIncludingDeleted($frontendUserToken);
            if ($optIn->getFrontendUserUid() != $frontendUserUid) {
                throw new Exception(
                    'The frontendUser that owns the given token is not identical with the user that is currently logged in.',
                    1666021555
                );
            }
        }

        /** @todo: Unterfunktion */
        $this->frontendUserToken = $frontendUserToken;
        $this->frontendUserPersisted = null;
        $this->frontendUser = null;
        $this->optInPersisted = null;
        return $this;
    }


    /**
     * @return array
     */
    public function getFrontendUserOptInUpdate(): array
    {
        return $this->frontendUserOptInUpdate;
    }


    /**
     * @var \Madj2k\FeRegister\Domain\Model\FrontendUser $frontendUser
     * @var array $ignoreProperties
     * @return self
     * @throws \TYPO3\CMS\Extbase\Persistence\Generic\Exception\TooDirtyException
     */
    public function setFrontendUserOptInUpdate(
        FrontendUser $frontendUser,
        array $ignoreProperties = []
    ): self {

        $ignoreProperties = array_merge($ignoreProperties, [
            'uid', 'pid', 'username', 'password', 'disable', 'deleted',
            'crdate', 'tstamp', 'starttime', 'endtime', 'usergroup',
            'lastlogin', 'lockToDomain', 'captchaResponse',  'txFeregisterDataProtectionStatus',
            'txFeregisterRegisterRemoteIp', 'txFeregisterLoginErrorCount',
            'tx_feregister_consent', 'tx_feregister_consent_privacy', 'tx_feregister_consent_terms',
            'tx_feregister_consent_marketing', 'tx_feregister_consent_topics'
        ]);

        // take array to reduce size in the database
        // remove all evil properties !!!
        $this->frontendUserOptInUpdate = array_diff_key(
            FrontendUserUtility::convertObjectToArray($frontendUser, true),
            array_flip($ignoreProperties)
        );

        return $this;
    }


    /**
     * Returns the clean and save frontendUser from the database
     *
     * @return \Madj2k\FeRegister\Domain\Model\FrontendUser|null
     * @throws \TYPO3\CMS\Extbase\Persistence\Generic\Exception\NotImplementedException
     * @throws \TYPO3\CMS\Extbase\Persistence\Exception\InvalidQueryException
     * @throws \TYPO3\CMS\Extbase\Object\Exception
     */
    public function getFrontendUserPersisted(): ?FrontendUser
    {
        if (!$this->frontendUserPersisted) {

            // load by frontendUser
            if ($this->frontendUser) {

                // sad but true: we have to clear the persistence cache here in order to get the object new from the database!
                /** @var \TYPO3\CMS\Extbase\Persistence\Generic\PersistenceManager $persistenceManager */
                $persistenceManager = $this->objectManager->get(PersistenceManager::class);
                $persistenceManager->clearState();

                if ($this->frontendUser->getUid()) {
                    /** @var \Madj2k\FeRegister\Domain\Model\FrontendUser frontendUserPersisted */
                    $this->frontendUserPersisted = $this->getContextAwareFrontendUserRepository()->findByIdentifierIncludingDisabled($this->frontendUser->getUid());
                } else {
                    /** @var \Madj2k\FeRegister\Domain\Model\FrontendUser frontendUserPersisted */
                    $this->frontendUserPersisted = $this->getContextAwareFrontendUserRepository()->findOneByUsernameIncludingDisabled($this->frontendUser->getUsername());
                }

            // load by token
            } else if ($optIn = $this->getOptInPersisted()) {
                /** @var \Madj2k\FeRegister\Domain\Model\FrontendUser frontendUserPersisted */
                $this->frontendUserPersisted = $this->getContextAwareFrontendUserRepository()->findByIdentifierIncludingDisabled($optIn->getFrontendUserUid());
            }
        }

        return $this->frontendUserPersisted;
    }


    /**
     * @return \Madj2k\FeRegister\Domain\Model\OptIn|null
     * @throws \TYPO3\CMS\Extbase\Persistence\Exception\InvalidQueryException
     */
    public function getOptInPersisted(): ?OptIn
    {
        if (! $this->optInPersisted) {

            if ($this->frontendUserToken) {

                /** @var \Madj2k\FeRegister\Domain\Model\OptIn optInPersisted */
                $this->optInPersisted = $this->optInRepository->findOneByTokenUserIncludingDeleted($this->frontendUserToken);

            } else if ($this->frontendUser) {

                /** @var \Madj2k\FeRegister\Domain\Model\OptIn optInPersisted */
                $this->optInPersisted = $this->optInRepository->findOneByFrontendUserIncludingDeleted($this->frontendUser);
            }
        }

        return $this->optInPersisted;
    }


    /**
     * @return \TYPO3\CMS\Extbase\Mvc\Request|null
     */
    public function getRequest(): ?Request
    {
        return $this->request;
    }


    /**
     * @var \TYPO3\CMS\Extbase\Mvc\Request $request
     * @return self
     */
    public function setRequest(Request $request): self
    {
        $this->request = $request;
        return $this;
    }


    /**
     * Adds a backendUser for the approval
     *
     * @param \Madj2k\FeRegister\Domain\Model\BackendUser for the approval $backendUser
     * @return void
     * @api
     */
    public function addApproval(BackendUser $backendUser): void
    {
        $this->approval->attach($backendUser);
    }


    /**
     * Removes a backendUser for the approval
     *
     * @param \Madj2k\FeRegister\Domain\Model\BackendUser $backendUser
     * @return void
     * @api
     */
    public function removeApproval(BackendUser $backendUser): void
    {
        $this->approval->detach($backendUser);
    }


    /**
     * Returns the backend users for the approval
     *
     * @return \TYPO3\CMS\Extbase\Persistence\ObjectStorage<\Madj2k\FeRegister\Domain\Model\BackendUser>
     * @api
     */
    public function getApproval(): ObjectStorage
    {
        return $this->approval;
    }


    /**
     * Sets the backend users for the approval
     *
     * @param \TYPO3\CMS\Extbase\Persistence\ObjectStorage<\Madj2k\FeRegister\Domain\Model\BackendUser> $backendUsers
     * @return self
     * @api
     */
    public function setApproval(ObjectStorage $backendUsers): self
    {
        $this->approval = $backendUsers;
        return $this;
    }


    /**
     * Gets the data
     *
     * @return mixed
     */
    public function getData()
    {
        return $this->data;
    }


    /**
     * Sets the data
     *
     * @var mixed $data
     * @return self
     */
    public function setData($data): self
    {
        $this->data = $data;
        return $this;
    }


    /**
     * Gets the dataParent
     *
     * @return mixed
     */
    public function getDataParent()
    {
        return $this->dataParent;
    }


    /**
     * Sets the dataParent
     *
     * @var mixed $dataParent
     * @return self
     */
    public function setDataParent($dataParent): self
    {
        $this->dataParent = $dataParent;
        return $this;
    }


    /**
     * @return string
     */
    public function getCategory(): string
    {
        return $this->category;
    }


    /**
     * @var string $category
     * @return self
     */
    public function setCategory(string $category): self
    {
        $this->category = $category;
        return $this;
    }


    /**
     * Creates an opt-in for a frontendUser
     *
     * @return \Madj2k\FeRegister\Domain\Model\OptIn
     * @throws \Exception
     * @throws \Madj2k\FeRegister\Exception
     * @throws \TYPO3\CMS\Extbase\Persistence\Exception\IllegalObjectTypeException
     * @throws \TYPO3\CMS\Extbase\Persistence\Generic\Exception\NotImplementedException
     * @api
     */
    public function createOptIn(): OptIn
    {
        if (! $frontendUserPersisted = $this->getFrontendUserPersisted()) {
            throw new Exception('The frontendUser-object has to be persisted to create an opt-in.',1659691717);
        }

        $settings = $this->getSettings();
        /** @var  $optIn */
        $optIn = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(OptIn::class);
        $optIn->setFrontendUserUid($frontendUserPersisted->getUid());
        $optIn->setFrontendUserUpdate($this->getFrontendUserOptInUpdate());
        $optIn->setCategory($this->getCategory());
        $optIn->setData($this->getData());
        $optIn->setTokenUser(GeneralUtility::getUniqueRandomString());
        $optIn->setTokenYes(GeneralUtility::getUniqueRandomString());
        $optIn->setTokenNo(GeneralUtility::getUniqueRandomString());
        $optIn->setEndtime(strtotime("+" . $settings['users']['daysForOptIn'] . " day", time()));
        $optIn->setAdminApproved(1);

        // set information about table and uid used in data-objects
        // this is needed for e.g. group-registration
        $this->appendForeignTableInformation($optIn);

        $this->optInRepository->add($optIn);
        $this->persistenceManager->persistAll();

        // update object locally
        $this->optInPersisted = $optIn;

        // check if there are admins for the approval set
        $this->appendApprovals($optIn);

        // add privacy-object for non-existing user
        if ($request = $this->getRequest()) {
            ConsentHandler::add(
                $request,
                $frontendUserPersisted,
                $optIn,
                sprintf(
                    'Created opt-in for user "%s" (disabled=%s, id=%s, category=%s).',
                    strtolower($frontendUserPersisted->getUsername()),
                    intval($frontendUserPersisted->getDisable()),
                    $frontendUserPersisted->getUid(),
                    $this->getCategory()
                )
            );
        }

        // we do NOT set a category-parameter here. We use the append-method instead.
        // This way we either send a mail from this extension or from another - never both!
        $this->dispatchSignalSlot(self::SIGNAL_AFTER_CREATING_OPTIN . ucfirst($this->getCategory()));

        return $optIn;
    }


    /**
     * @param \Madj2k\FeRegister\Domain\Model\OptIn $optIn
     * @return void
     * @throws \TYPO3\CMS\Extbase\Persistence\Generic\Exception
     */
    protected function appendForeignTableInformation(\Madj2k\FeRegister\Domain\Model\OptIn $optIn)
    {
        $objectManager = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(ObjectManager::class);

        // set information about table and uid used in data-objects
        // this is needed for e.g. group-registration
        if (
            ($data = $this->getData())
            && ($data instanceOf AbstractEntity)
        ){
            $dataMapper = $objectManager->get(DataMapper::class);
            $tableName = $dataMapper->getDataMap(get_class($this->getData()))->getTableName();
            $optIn->setForeignTable($tableName);
            if ($uid = $data->getUid()) {
                $optIn->setForeignUid($uid);
            }
        }

        if (
            ($dataParent = $this->getDataParent())
            && ($dataParent instanceOf AbstractEntity)
        ){
            $dataMapper = $objectManager->get(DataMapper::class);
            $tableName = $dataMapper->getDataMap(get_class($this->getDataParent()))->getTableName();
            $optIn->setParentForeignTable($tableName);
            if ($uid = $dataParent->getUid()) {
                $optIn->setParentForeignUid($uid);
            }
        }
    }


    /**
     * Set approvals for optIn
     *
     * @param \Madj2k\FeRegister\Domain\Model\OptIn $optIn
     * @return void
     * @throws \TYPO3\CMS\Extbase\Persistence\Exception\IllegalObjectTypeException
     * @throws \TYPO3\CMS\Extbase\Persistence\Exception\InvalidQueryException
     * @throws \TYPO3\CMS\Extbase\Persistence\Exception\UnknownObjectException
     * @throws \TYPO3\CMS\Extbase\Persistence\Generic\Exception\NotImplementedException
     * @throws \TYPO3\CMS\Extbase\SignalSlot\Exception\InvalidSlotException
     * @throws \TYPO3\CMS\Extbase\SignalSlot\Exception\InvalidSlotReturnException
     * @throws \Exception
     */
    protected function appendApprovals(OptIn $optIn): void
    {
        if (count($this->getApproval())) {

            // use a loop because via set the _is-dirty is false!
            /** @var \Madj2k\FeRegister\Domain\Model\BackendUser $backendUser */
            foreach ($this->getApproval() as $backendUser) {
                $optIn->addAdmins($backendUser);
            }

            $optIn->setAdminApproved(0);
            $optIn->setAdminTokenYes(GeneralUtility::getUniqueRandomString());
            $optIn->setAdminTokenNo(GeneralUtility::getUniqueRandomString());

            $this->optInRepository->update($optIn);
            $this->persistenceManager->persistAll();

            // update object locally
            $this->optInPersisted = $optIn;

            // we do NOT set a category-parameter here. We use the append-method instead.
            // This way we either send a mail from this extension or from another - never both!
            $this->dispatchSignalSlot(self::SIGNAL_AFTER_CREATING_OPTIN_ADMIN . ucfirst($this->getCategory()));
        }
    }


    /**
     * Completes the registration of the frontendUser or his service
     *
     * @return bool
     * @throws \Madj2k\FeRegister\Exception
     * @throws \TYPO3\CMS\Extbase\Persistence\Exception\IllegalObjectTypeException
     * @throws \TYPO3\CMS\Extbase\Persistence\Exception\UnknownObjectException
     * @throws \TYPO3\CMS\Extbase\Persistence\Generic\Exception\NotImplementedException
     * @throws \TYPO3\CMS\Extbase\SignalSlot\Exception\InvalidSlotException
     * @throws \TYPO3\CMS\Extbase\SignalSlot\Exception\InvalidSlotReturnException
     * @throws \TYPO3\CMS\Extbase\Configuration\Exception\InvalidConfigurationTypeException
     * @throws \TYPO3\CMS\Extbase\Persistence\Exception\InvalidQueryException
     * @throws \TYPO3\CMS\Core\Crypto\PasswordHashing\InvalidPasswordHashException
     * @api
     */
    public function completeRegistration(): bool
    {

        // check for frontendUser-object
        if (! $frontendUserPersisted = $this->getFrontendUserPersisted()) {
            throw new Exception('No persisted frontendUser-object found.', 1660814408);
        }

        $settings = $this->getSettings();

        // enable users that are disabled right now
        if ($frontendUserPersisted->getDisable()) {

            // enable user
            $frontendUserPersisted->setDisable(0);

            // generate new password
            $frontendUserPersisted->setTempPlaintextPassword(PasswordUtility::generatePassword());
            $frontendUserPersisted->setPassword(PasswordUtility::saltPassword($frontendUserPersisted->getTempPlaintextPassword()));

            // set normal lifetime
            $frontendUserPersisted->setEndtime(0);

            // override if there is set a specific frontendUser lifetime
            if (intval($settings['users']['lifetime'])) {
                $frontendUserPersisted->setEndtime(time() + intval($settings['users']['lifetime']));
            }

            // override if it's a GuestUser
            if (FrontendUserUtility::isGuestUser($frontendUserPersisted)) {
                // set guestUser lifetime
                if (intval($settings['users']['guest']['lifetime'])) {
                    $frontendUserPersisted->setEndtime(time() + intval($settings['users']['guest']['lifetime']));
                }
            }

            // set user-groups!
            $userGroups = $settings['users']['groupsOnRegister'];
            if (FrontendUserUtility::isGuestUser($frontendUserPersisted)) {
                $userGroups = $settings['users']['guest']['groupsOnRegister'];
            }

            // add groups to existing ones (if any)
            if ($userGroups) {
                $userGroupIds = GeneralUtility::trimExplode(',', $userGroups);
                $objectStorage = $frontendUserPersisted->getUsergroup() ?: GeneralUtility::makeInstance(\TYPO3\CMS\Extbase\Persistence\ObjectStorage::class);
                foreach ($userGroupIds as $groupId) {

                    /** @var \Madj2k\FeRegister\Domain\Model\FrontendUserGroup $frontendUserGroup */
                    $frontendUserGroup = $this->frontendUserGroupRepository->findByUid($groupId);
                    if ($frontendUserGroup instanceof FrontendUserGroup) {
                        $objectStorage->attach($frontendUserGroup);
                    }
                }
                $frontendUserPersisted->setUsergroup($objectStorage);
            } else {
                $this->getLogger()->log(
                    LogLevel::WARNING,
                    sprintf(
                        'User "%s" will not be usable (id=%s, category=%s). Setting users(.guest).groupsOnRegister is not defined in TypoScript.',
                        strtolower($frontendUserPersisted->getUsername()),
                        $frontendUserPersisted->getUid(),
                        $this->getCategory()
                    )
                );
            }

            // update and persist
            $this->getContextAwareFrontendUserRepository()->update($frontendUserPersisted);
            $this->persistenceManager->persistAll();

            // synchronize frontendUser-objects!
            $this->frontendUser = $frontendUserPersisted;

            $this->dispatchSignalSlot(self::SIGNAL_AFTER_REGISTRATION_COMPLETED, $this->getCategory());
            $this->getLogger()->log(
                LogLevel::INFO,
                sprintf(
                    'Registration for user "%s" successfully completed (id=%s, category=%s).',
                    strtolower($frontendUserPersisted->getUsername()),
                    $frontendUserPersisted->getUid(),
                    $this->getCategory()
                )
            );

            return true;
        }

        // e.g. if we have an opt-in for an existing user
        // we do NOT set a category-parameter here. We use the append-method instead.
        // This way we do not send mails from this extension
        if ($this->getCategory()) {
            $this->dispatchSignalSlot(self::SIGNAL_AFTER_REGISTRATION_COMPLETED . ucfirst($this->getCategory()));
        }

        return false;
    }


    /**
     * Cancels the registration of the frontendUser or his service
     *
     * @return bool
     * @throws \Madj2k\FeRegister\Exception
     * @throws \TYPO3\CMS\Extbase\Persistence\Exception\IllegalObjectTypeException
     * @throws \TYPO3\CMS\Extbase\Persistence\Exception\UnknownObjectException
     * @throws \TYPO3\CMS\Extbase\Persistence\Generic\Exception\NotImplementedException
     * @throws \TYPO3\CMS\Extbase\SignalSlot\Exception\InvalidSlotException
     * @throws \TYPO3\CMS\Extbase\SignalSlot\Exception\InvalidSlotReturnException
     * @throws \TYPO3\CMS\Extbase\Persistence\Exception\InvalidQueryException
     * @throws \TYPO3\CMS\Extbase\Persistence\Generic\Exception
     * @api
     */
    public function cancelRegistration(): bool
    {

        // check for frontendUser-object
        if (! $frontendUserPersisted = $this->getFrontendUserPersisted()) {
            throw new Exception('No persisted frontendUser-object found.', 1660914940);
        }

        // delete user and registration
        // remove only disabled user!
        if ($frontendUserPersisted->getDisable()) {

            $frontendUserPersisted->setDeleted(1);
            $this->getContextAwareFrontendUserRepository()->update($frontendUserPersisted);
            $this->getContextAwareFrontendUserRepository()->removeHard($frontendUserPersisted);
            $this->persistenceManager->persistAll();

            // synchronize frontendUser-objects!
            $this->frontendUser = $frontendUserPersisted;

            $this->dispatchSignalSlot(self::SIGNAL_AFTER_REGISTRATION_CANCELED, $this->getCategory());

            $this->getLogger()->log(
                LogLevel::INFO,
                sprintf(
                    'Registration for user "%s" successfully canceled (id=%s, category=%s).',
                    strtolower($frontendUserPersisted->getUsername()),
                    $frontendUserPersisted->getUid(),
                    $this->getCategory()
                )
            );

            return true;
        }

        return false;
    }


    /**
     * Removes existing account of FE-user
     *
     * @return boolean
     * @throws \Madj2k\FeRegister\Exception
     * @throws \TYPO3\CMS\Extbase\Persistence\Exception\IllegalObjectTypeException
     * @throws \TYPO3\CMS\Extbase\SignalSlot\Exception\InvalidSlotException
     * @throws \TYPO3\CMS\Extbase\SignalSlot\Exception\InvalidSlotReturnException
     * @throws \TYPO3\CMS\Core\Context\Exception\AspectNotFoundException
     * @throws \TYPO3\CMS\Extbase\Persistence\Generic\Exception\NotImplementedException
     * @throws \TYPO3\CMS\Extbase\Persistence\Exception\InvalidQueryException
     */
    public function endRegistration(): bool
    {

        // check for frontendUser-object
        if (! $frontendUserPersisted = $this->getFrontendUserPersisted()) {
            throw new Exception('No persisted frontendUser-object found.', 1661163918);
        }

        if (! $frontendUserPersisted->getDisable()) {

            $this->dispatchSignalSlot(self::SIGNAL_AFTER_REGISTRATION_ENDED, $this->getCategory());

            // remove all open opt-ins of user
            /** @var  \Madj2k\FeRegister\Domain\Model\OptIn $topIn */
            foreach ($this->optInRepository->findByFrontendUserUid($frontendUserPersisted->getUid()) as $optIn) {
                $this->optInRepository->remove($optIn);
            }

            // logout user if logged-in
            if (FrontendUserSessionUtility::isUserLoggedIn($frontendUserPersisted)) {
                FrontendUserSessionUtility::logout();
            }

            // remove frontendUser
            $this->getContextAwareFrontendUserRepository()->remove($frontendUserPersisted);
            $this->persistenceManager->persistAll();

            // synchronize frontendUser-objects!
            $this->frontendUser = $frontendUserPersisted;

            $this->getLogger()->log(
                LogLevel::INFO,
                sprintf(
                    'Registration for user "%s" successfully ended (id=%s, category=%s).',
                    strtolower($frontendUserPersisted->getUsername()),
                    $frontendUserPersisted->getUid(),
                    $this->getCategory()
                )
            );

            return true;
        }

        return false;
    }


    /**
     * Returns repository that belongs to the given frontendUserType
     *
     * @return \Madj2k\FeRegister\Domain\Repository\FrontendUserRepository
     */
    public function getContextAwareFrontendUserRepository(): FrontendUserRepository
    {
        if (FrontendUserUtility::isGuestUser($this->frontendUser)) {
            return $this->guestUserRepository;
        }

        return $this->frontendUserRepository;
    }


    /**
     * sets some basic data to a frontendUser (if not already set)
     *
     * @return void
     * @throws \Exception
     * @throws \Madj2k\FeRegister\Exception
     * @throws \TYPO3\CMS\Extbase\Configuration\Exception\InvalidConfigurationTypeException
     * @throws \TYPO3\CMS\Extbase\Persistence\Exception\IllegalObjectTypeException
     */
    protected function prepareFrontendUser(): void
    {
        $settings = $this->getSettings();

        // lowercase username and email!
        $this->frontendUser->setEmail(strtolower($this->frontendUser->getEmail()));
        $this->frontendUser->setUsername(strtolower($this->frontendUser->getUsername()));

        if (FrontendUserUtility::isGuestUser($this->frontendUser)) {

            // clear email-address and set random username
            $this->frontendUser->setEmail('');
            $this->frontendUser->setUsername(GeneralUtility::getUniqueRandomString());

        } else {

            // check email
            if (!FrontendUserUtility::isEmailValid($this->frontendUser->getEmail())) {
                throw new Exception('No valid email given.', 1407312133);
            }

            // set email as fallback
            if (!$this->frontendUser->getUsername()) {
                $this->frontendUser->setUsername($this->frontendUser->getEmail());
            }

            // check username
            if (!FrontendUserUtility::isEmailValid($this->frontendUser->getUsername())) {
                throw new Exception('No valid username given.', 1407312134);
            }
        }

        // migrate title
        if ($this->frontendUser->getTitle()) {
            $this->frontendUser->setTxFeregisterTitle(TitleUtility::extractTxRegistrationTitle($this->frontendUser->getTitle(), $settings));
            $this->frontendUser->setTitle('');
        }

        // set languageKey of current site!
        if (!$this->frontendUser->getTxFeregisterLanguageKey()) {
            $this->frontendUser->setTxFeregisterLanguageKey(SiteUtility::getCurrentTypo3Language());
        }

        // things we only do with new frontendUser-objects
        if ($this->frontendUser->_isNew()) {

            $this->frontendUser->setCrdate(time());
            // $this->frontendUser->setPid(intval($settings['users']['storagePid']));

            $this->frontendUser->setDisable(1);
            $this->frontendUser->setTxFeregisterRegisterRemoteIp(ClientUtility::getIp());

            // set opt-in lifetime
            if (intval($settings['users']['daysForOptIn'])) {
                $this->frontendUser->setEndtime(time() + (intval($settings['users']['daysForOptIn']) * 24 * 60 * 60));
            }
        }
    }


    /**
     * Dispatches the SignalSlots in two versions: with and without appended category-name
     *
     * @param string $name
     * @param string $category
     * @return void
     * @throws \TYPO3\CMS\Extbase\SignalSlot\Exception\InvalidSlotException
     * @throws \TYPO3\CMS\Extbase\SignalSlot\Exception\InvalidSlotReturnException
     * @throws \TYPO3\CMS\Extbase\Persistence\Generic\Exception\NotImplementedException
     * @throws \TYPO3\CMS\Extbase\Persistence\Exception\InvalidQueryException
     */
    protected function dispatchSignalSlot (string $name, string $category = '')
    {
        // no emails for GuestUser!
        if (FrontendUserUtility::isGuestUser($this->getFrontendUserPersisted())) {
            return;
        }

        $data = [
            $this->getFrontendUserPersisted(),
            $this->getOptInPersisted(),
            count($this->getApproval()) ? $this->getApproval() : ($this->getOptInPersisted() ? $this->getOptInPersisted()->getAdmins(): null)
        ];

        // Signal for this extension, e.g. for E-Mails
        $this->signalSlotDispatcher->dispatch(
            __CLASS__,
            $name,
            $data
        );

        if ($category) {

            // Signal for other extensions
            $this->signalSlotDispatcher->dispatch(
                __CLASS__,
                $name . ucfirst($category),
                $data
            );
        }
    }


    /**
     * Returns TYPO3 settings
     *
     * @param string $type
     * @return array
     * @throws \TYPO3\CMS\Extbase\Configuration\Exception\InvalidConfigurationTypeException
     */
    protected function getSettings(string $type = \TYPO3\CMS\Extbase\Configuration\ConfigurationManagerInterface::CONFIGURATION_TYPE_SETTINGS): array
    {
        if (!$this->settings) {
            $this->settings = GeneralUtility::getTypoScriptConfiguration('Feregister', $type);
        }

        if ($this->settings) {
            return  $this->settings;
        }
        return [];
    }


    /**
     * Returns logger instance
     *
     * @return \TYPO3\CMS\Core\Log\Logger
     */
    protected function getLogger(): Logger
    {
        if (!$this->logger instanceof Logger) {
            $this->logger = GeneralUtility::makeInstance(LogManager::class)->getLogger(__CLASS__);
        }

        return $this->logger;
    }

}
