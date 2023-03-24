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

use TYPO3\CMS\Extbase\Persistence\ObjectStorage;

/**
 * Class FrontendUserGroup
 *
 * @author Maximilian Fäßler <maximilian@faesslerweb.de>
 * @author Steffen Kroggel <developer@steffenkroggel.de>
 * @copyright Steffen Kroggel
 * @package Madj2k_FeRegister
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License, version 3 or later
 */
class FrontendUserGroup extends \Madj2k\CoreExtended\Domain\Model\FrontendUserGroup
{

    /**
     * @var bool
     */
    protected bool $txFeregisterIsMembership = false;


    /**
     * @var int
     */
    protected int $txFeregisterMembershipOpeningDate = 0;


    /**
     * @var int
     */
    protected int $txFeregisterMembershipClosingDate = 0;


    /**
     * @var string
     */
    protected string $txFeregisterMembershipMandatoryFields = '';


    /**
     * @var \TYPO3\CMS\Extbase\Persistence\ObjectStorage<\Madj2k\FeRegister\Domain\Model\BackendUser>|null
     */
    protected ?ObjectStorage $txFeregisterMembershipAdmins = null;


    /**
     * @var int
     */
    protected int $txFeregisterMembershipPid = 0;


    /**
     * __construct
     */
    public function __construct()
    {

        parent::__construct();

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
        $this->txFeregisterMembershipAdmins = new \TYPO3\CMS\Extbase\Persistence\ObjectStorage();
    }


    /**
     * Sets the txFeregisterIsMembership value
     *
     * @param bool $txFeregisterIsMembership
     * @return void
     * @api
     */
    public function setTxFeregisterIsMembership(bool $txFeregisterIsMembership): void
    {
        $this->txFeregisterIsMembership = $txFeregisterIsMembership;
    }


    /**
     * Returns the txFeregisterIsMembership value
     *
     * @return bool
     * @api
     */
    public function getTxFeregisterIsMembership(): bool
    {
        return $this->txFeregisterIsMembership;
    }


    /**
     * Sets the txFeregisterMembershipOpeningDate value
     *
     * @param int $txFeregisterMembershipOpeningDate
     * @return void
     * @api
     */
    public function setTxFeregisterMembershipOpeningDate(int $txFeregisterMembershipOpeningDate): void
    {
        $this->txFeregisterMembershipOpeningDate = $txFeregisterMembershipOpeningDate;
    }


    /**
     * Returns the txFeregisterOpeningDate value
     *
     * @return int
     * @api
     */
    public function getTxFeregisterMembershipOpeningDate(): int
    {
        return $this->txFeregisterMembershipOpeningDate;
    }


    /**
     * Sets the txFeregistertxFeregisterClosingDate value
     *
     * @param int $txFeregisterMembershipClosingDate
     * @return void
     * @api
     */
    public function setTxFeregisterMembershipClosingDate(int $txFeregisterMembershipClosingDate)
    {
        $this->txFeregisterMembershipClosingDate = $txFeregisterMembershipClosingDate;
    }


    /**
     * Returns the txFeregisterMembershipClosingDate value
     *
     * @return int
     * @api
     */
    public function getTxFeregisterMembershipClosingDate(): int
    {
        return $this->txFeregisterMembershipClosingDate;
    }


    /**
     * Sets the txFeregisterMembershipMandatoryFields
     *
     * @param string $txFeregisterMembershipMandatoryFields
     * @return void
     * @api
     */
    public function setTxFeregisterMembershipMandatoryFields(string $txFeregisterMembershipMandatoryFields): void
    {
        $this->txFeregisterMembershipMandatoryFields = $txFeregisterMembershipMandatoryFields;
    }


    /**
     * Returns the txFeregisterMembershipMandatoryFields
     *
     * @return string
     * @api
     */
    public function getTxFeregisterMembershipMandatoryFields(): string
    {
        return $this->txFeregisterMembershipMandatoryFields;
    }


    /**
     * Adds a BackendUser
     *
     * @param \Madj2k\FeRegister\Domain\Model\BackendUser $admin
     * @return void
     */
    public function addTxFeregisterMembershipAdmins(BackendUser $admin): void
    {
        $this->txFeregisterMembershipAdmins->attach($admin);
    }


    /**
     * Removes a BackendUser
     *
     * @param \Madj2k\FeRegister\Domain\Model\BackendUser $adminToRemove
     * @return void
     */
    public function removeTxFeregisterMembershipAdmins(BackendUser $adminToRemove): void
    {
        $this->txFeregisterMembershipAdmins->detach($adminToRemove);
    }


    /**
     * Returns the TxFeregisterMembershipAdmins
     *
     * @return \TYPO3\CMS\Extbase\Persistence\ObjectStorage<\Madj2k\FeRegister\Domain\Model\BackendUser>
     */
    public function getTxFeregisterMembershipAdmins(): ObjectStorage
    {
        return $this->txFeregisterMembershipAdmins;
    }


    /**
     * Sets the TxFeregisterMembershipAdmins
     *
     * @param \TYPO3\CMS\Extbase\Persistence\ObjectStorage<\Madj2k\FeRegister\Domain\Model\BackendUser> $admins
     * @return void
     */
    public function setTxFeregisterMembershipAdmins(ObjectStorage $admins): void
    {
        $this->txFeregisterMembershipAdmins = $admins;
    }


    /**
     * Sets the txFeregisterMembershipPid value
     *
     * @param int $txFeregisterMembershipPid
     * @return void
     * @api
     */
    public function setTxFeregisterMembershipPid(int $txFeregisterMembershipPid): void
    {
        $this->txFeregisterMembershipPid = $txFeregisterMembershipPid;
    }


    /**
     * Returns the txFeregisterMembershipPid value
     *
     * @return int
     * @api
     */
    public function getTxFeregisterMembershipPid(): int
    {
        return $this->txFeregisterMembershipPid;
    }

}
