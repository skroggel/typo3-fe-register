<?php
namespace Madj2k\FeRegister\Utility;

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
use Symfony\Component\Mime\Address;
use TYPO3\CMS\Core\Mail\MailMessage;

/**
 * Class SystemMail
 *
 * @author Maximilian Fäßler <maximilian@faesslerweb.de>
 * @author Steffen Kroggel <developer@steffenkroggel.de>
 * @copyright Steffen Kroggel
 * @package Madj2k_FeRegister
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License, version 3 or later
 */
class SystemMailUtility implements \TYPO3\CMS\Core\SingletonInterface
{


    /**
     * send email
     *
     * @param string $message
     * @param string $address
     * @param string $subject
     * @return void
     */
    public static function sendMail(
        string $message,
        string $address = '',
        string $subject = 'Admin mail from your TYPO3 website'
    ): void {

        if (empty($address)) {
            $address = $GLOBALS['TYPO3_CONF_VARS']['MAIL']['defaultMailFromAddress'];
        }

        // Create the message
        $mail = GeneralUtility::makeInstance(MailMessage::class);

        $mail->from(new \Symfony\Component\Mime\Address(
            $address,
            $GLOBALS['TYPO3_CONF_VARS']['MAIL']['defaultMailFromName']
        ));
        // From RKW to RKW (use "fromName" again)
        $mail->to(new Address(
            $address,
            $GLOBALS['TYPO3_CONF_VARS']['MAIL']['defaultMailFromName']
        ));
        $mail->subject($subject);
        $mail->text($message);
        $mail->send();
    }



    /**
     * Returns logger instance
     *
     * @return \TYPO3\CMS\Core\Log\Logger
     */
    protected static function getLogger(): Logger
    {
        return GeneralUtility::makeInstance(LogManager::class)->getLogger(__CLASS__);
    }

}
