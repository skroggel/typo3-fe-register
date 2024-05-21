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

use Madj2k\Accelerator\Persistence\MarkerReducer;
use TYPO3\CMS\Extbase\Persistence\ObjectStorage;

/**
 * OptIn
 *
 * @author Steffen Kroggel <developer@steffenkroggel.de>
 * @copyright Steffen Kroggel
 * @package Madj2k_FeRegister
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License, version 3 or later
 */
class OptIn extends \TYPO3\CMS\Extbase\DomainObject\AbstractEntity
{

    /**
     * Has to be an uid only because disabled objects are not loaded via extbase
     *
     * @var int
     */
    protected int $frontendUserUid = 0;


    /**
     * @var string
     */
    protected string $frontendUserUpdate = '';


    /**
     * @var \TYPO3\CMS\Extbase\Persistence\ObjectStorage<\Madj2k\FeRegister\Domain\Model\BackendUser>|null
     */
    protected ?ObjectStorage $admins = null;


    /**
     * @var string
     */
    protected string $tokenUser = '';


    /**
     * @var string
     */
    protected string $tokenYes = '';


    /**
     * @var string
     */
    protected string $tokenNo = '';


    /**
     * @var string
     */
    protected string $adminTokenYes = '';


    /**
     * @var string
     */
    protected string $adminTokenNo = '';


    /**
     * @var string
     */
    protected string $category = '';


    /**
     * @var string
     */
    protected string $foreignTable = '';


    /**
     * @var int
     */
    protected int $foreignUid = 0;


    /**
     * @var string
     */
    protected string $parentForeignTable = '';


    /**
     * @var int
     */
    protected int $parentForeignUid = 0;


    /**
     * @var int
     */
    protected int $approved = 0;


    /**
     * @var int
     */
    protected int $adminApproved = 0;


    /**
     * @var string
     */
    protected string $data = '';


    /**
     * !!! Should never be persisted!!! !!!
     * dataRaw
     *
     * @var string
     */
    protected string $_rawdata = '';


    /**
     * @var int
     */
    protected int $starttime = 0;


    /**
     * @var int
     */
    protected int $endtime = 0;


    /**
     * @var bool
     */
    protected bool $deleted = false;


    /**
     * __construct
     */
    public function __construct()
    {
        // Do not remove the next line: It would break the functionality
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
        $this->admins = new \TYPO3\CMS\Extbase\Persistence\ObjectStorage();
    }


    /**
     * Returns the frontendUserId
     *
     * @return int $user
     */
    public function getFrontendUserUid (): int
    {
        return $this->frontendUserUid;
    }


    /**
     * Sets the frontendUserUid
     *
     * @param int $frontendUserUid
     * @return void
     */
    public function setFrontendUserUid(int $frontendUserUid): void
    {
        $this->frontendUserUid = $frontendUserUid;
    }


    /**
     * Returns the frontendUserUpdate
     *
     * @return array $frontendUserUpdate
     */
    public function getFrontendUserUpdate(): array
    {
        if ($this->frontendUserUpdate) {
            return (unserialize($this->frontendUserUpdate) ?? []);
        }

        return [];
    }


    /**
     * Sets the frontendUserUpdate
     *
     * @param array $frontendUserUpdate
     * @return void
     */
    public function setFrontendUserUpdate(array $frontendUserUpdate): void
    {
        $this->frontendUserUpdate = serialize($frontendUserUpdate);
    }


    /**
     * Adds a backendUser for the admins
     *
     * @param \Madj2k\FeRegister\Domain\Model\BackendUser
     * @return void
     * @api
     */
    public function addAdmins(BackendUser $backendUser): void
    {
        $this->admins->attach($backendUser);
    }


    /**
     * Removes a backendUser for the admins
     *
     * @param \Madj2k\FeRegister\Domain\Model\BackendUser $backendUser
     * @return void
     * @api
     */
    public function removeAdmins(BackendUser $backendUser): void
    {
        $this->admins->detach($backendUser);
    }


    /**
     * Returns the backend users for the admins
     *
     * @return \TYPO3\CMS\Extbase\Persistence\ObjectStorage<\Madj2k\FeRegister\Domain\Model\BackendUser>
     * @api
     */
    public function getAdmins(): ObjectStorage
    {
        return $this->admins;
    }


    /**
     * Sets the backend users for the admins
     *
     * @param \TYPO3\CMS\Extbase\Persistence\ObjectStorage<\Madj2k\FeRegister\Domain\Model\BackendUser> $backendUsers
     * @return void
     * @api
     */
    public function setAdmins(ObjectStorage $backendUsers): void
    {
        $this->admins = $backendUsers;
    }


    /**
     * Returns the tokenUser
     *
     * @return string $tokenUser
     */
    public function getTokenUser(): string
    {
        return $this->tokenUser;
    }


    /**
     * Sets the tokenUser
     *
     * @param string $tokenUser
     * @return void
     */
    public function setTokenUser(string $tokenUser): void
    {
        $this->tokenUser = $tokenUser;
    }


    /**
     * Returns the yesToken
     *
     * @return string $tokenYes
     */
    public function getTokenYes(): string
    {
        return $this->tokenYes;
    }


    /**
     * Sets the yesToken
     *
     * @param string $tokenYes
     * @return void
     */
    public function setTokenYes(string $tokenYes): void
    {
        $this->tokenYes = $tokenYes;
    }


    /**
     * Returns the tokenNo
     *
     * @return string $tokenNo
     */
    public function getTokenNo(): string
    {
        return $this->tokenNo;
    }


    /**
     * Sets the tokenNo
     *
     * @param string $tokenNo
     * @return void
     */
    public function setTokenNo(string $tokenNo): void
    {
        $this->tokenNo = $tokenNo;
    }


    /**
     * Returns the yesAdminToken
     *
     * @return string $adminTokenYes
     */
    public function getAdminTokenYes(): string
    {
        return $this->adminTokenYes;
    }


    /**
     * Sets the yesAdminToken
     *
     * @param string $adminTokenYes
     * @return void
     */
    public function setAdminTokenYes(string $adminTokenYes): void
    {
        $this->adminTokenYes = $adminTokenYes;
    }


    /**
     * Returns the adminTokenNo
     *
     * @return string $adminTokenNo
     */
    public function getAdminTokenNo(): string
    {
        return $this->adminTokenNo;
    }


    /**
     * Sets the adminTokenNo
     *
     * @param string $adminTokenNo
     * @return void
     */
    public function setAdminTokenNo(string $adminTokenNo): void
    {
        $this->adminTokenNo = $adminTokenNo;
    }


    /**
     * Returns the category
     *
     * @return string $category
     */
    public function getCategory(): string
    {
        return $this->category;
    }


    /**
     * Sets the category
     *
     * @param string $category
     * @return void
     */
    public function setCategory(string $category)
    {
        $this->category = $category;
    }


    /**
     * Returns the foreignTable
     *
     * @return string $foreignTable
     */
    public function getForeignTable(): string
    {
        return $this->foreignTable;
    }


    /**
     * Sets the foreignTable
     *
     * @param string $foreignTable
     * @return void
     */
    public function setForeignTable(string $foreignTable)
    {
        $this->foreignTable = $foreignTable;
    }


    /**
     * Returns the foreignUid
     *
     * @return int $foreignUid
     */
    public function getForeignUid(): int
    {
        return $this->foreignUid;
    }


    /**
     * Sets the foreignUid
     *
     * @param int $foreignUid
     * @return void
     */
    public function setForeignUid(int $foreignUid)
    {
        $this->foreignUid = $foreignUid;
    }


    /**
     * Returns the parentForeignTable
     *
     * @return string
     */
    public function getParentForeignTable(): string
    {
        return $this->parentForeignTable;
    }


    /**
     * Sets the parentForeignTable
     *
     * @param string $parentForeignTable
     * @return void
     */
    public function setParentForeignTable(string $parentForeignTable)
    {
        $this->parentForeignTable = $parentForeignTable;
    }


    /**
     * Returns the parentForeignUid
     *
     * @return int
     */
    public function getParentForeignUid(): int
    {
        return $this->parentForeignUid;
    }


    /**
     * Sets the parentForeignUid
     *
     * @param int $parentForeignUid
     * @return void
     */
    public function setParentForeignUid(int $parentForeignUid)
    {
        $this->parentForeignUid = $parentForeignUid;
    }


    /**
     * Returns the starttime
     *
     * @return int $starttime
     */
    public function getStarttime(): int
    {
        return $this->starttime;
    }


    /**
     * Returns the approved
     *
     * @return int $approved
     */
    public function getApproved(): int
    {
        return $this->approved;
    }


    /**
     * Sets the approved
     *
     * @param int $approved
     * @return void
     */
    public function setApproved(int $approved): void
    {
        $this->approved = $approved;
    }


    /**
     * Returns the adminApproved
     *
     * @return int $adminApproved
     */
    public function getAdminApproved(): int
    {
        return $this->adminApproved;
    }


    /**
     * Sets the adminApproved
     *
     * @param int $adminApproved
     * @return void
     */
    public function setAdminApproved(int $adminApproved): void
    {
        $this->adminApproved = $adminApproved;
    }


    /**
     * Returns the data
     *
     * @return mixed $data
     */
    public function getData()
    {
        if ($this->data) {
            if (! $this->_dataRaw) {
                if ($unserialized = unserialize($this->data)) {
                    $tempData = MarkerReducer::explode($unserialized);
                    $this->_dataRaw = $tempData['key'];
                }
            }

            return $this->_dataRaw;
        }

        return false;
    }


    /**
     * Sets the data
     *
     * @param mixed $data
     * @return void
     * @throws \TYPO3\CMS\Extbase\Persistence\Generic\Exception
     */
    public function setData($data): void
    {
        if ($data) {
            $this->_dataRaw = $data;
            $this->data = serialize(MarkerReducer::implode(['key' => $data]));
        }
    }

    /**
     * Sets the starttime
     *
     * @param int $starttime
     * @return void
     */
    public function setStarttime(int $starttime)
    {
        $this->starttime = $starttime;
    }


    /**
     * Returns the endtime
     *
     * @return int $endtime
     */
    public function getEndtime(): int
    {
        return $this->endtime;
    }


    /**
     * Sets the endtime
     *
     * @param int $endtime
     * @return void
     */
    public function setEndtime(int $endtime): void
    {
        $this->endtime = $endtime;
    }


    /**
     * Sets the deleted value
     *
     * @param bool $deleted
     * @return void
     *
     */
    public function setDeleted(bool $deleted): void
    {
        $this->deleted = $deleted;
    }


    /**
     * Returns the deleted value
     *
     * @return bool
     *
     */
    public function getDeleted(): bool
    {
        return $this->deleted;
    }

}
