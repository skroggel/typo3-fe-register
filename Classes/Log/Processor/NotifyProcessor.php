<?php

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

namespace Madj2k\FeRegister\Log\Processor;

use Madj2k\FeRegister\Utility\SystemMailUtility;
use TYPO3\CMS\Core\Log\LogRecord;
use TYPO3\CMS\Core\Log\Processor\AbstractProcessor;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Web log processor to automatically add web request related data to a log
 * record.
 */
class NotifyProcessor extends AbstractProcessor
{
    /**
     * Send mails active or not
     * Default is false
     *
     * @var bool
     */
    protected bool $sendMails = false;

    /**
     * The email address to send errors to
     *
     * @var string
     */
    protected string $emailAddress = '';

    /**
     * @param bool $sendMails
     * @return void
     */
    public function setSendMails(bool $sendMails): void
    {
        $this->sendMails = $sendMails;
    }

    /**
     * @return bool
     */
    public function getSendMails(): bool
    {
        return $this->sendMails;
    }

    /**
     * Sets the email address
     *
     * @param $emailAddress
     */
    public function setEmailAddress(string $emailAddress)
    {
        $this->emailAddress = $emailAddress;
    }

    /**
     * Returns wthe email address
     *
     * @return string
     */
    public function getEmailAddress(): string
    {
        return $this->emailAddress;
    }

    /**
     * Processes a log record and adds webserver environment data.
     * We use the usual "Debug System Information"
     *
     * @param \TYPO3\CMS\Core\Log\LogRecord $logRecord The log record to process
     * @return \TYPO3\CMS\Core\Log\LogRecord The processed log record with additional data
     * @see \TYPO3\CMS\Core\Utility\GeneralUtility::getIndpEnv()
     */
    public function processLogRecord(LogRecord $logRecord)
    {


        // @toDo: how to only activate if its FeRegister extension?


        if ($this->getSendMails()) {
           // $test = 'Sende Mails ist aktiv';
            SystemMailUtility::sendMail(
                $logRecord->getMessage(),
                $this->getEmailAddress()
            );
        } else {
           // $test = 'Sende Mails ist NICHT aktiv';
        }




        //$logRecord->setMessage($test);

        return $logRecord;
    }
}
