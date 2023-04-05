<?php
namespace Madj2k\FeRegister\Service;

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
use TYPO3\CMS\Core\Authentication\AbstractUserAuthentication;
use TYPO3\CMS\Core\Log\Logger;
use TYPO3\CMS\Core\Log\LogManager;
use TYPO3\CMS\Extbase\Configuration\ConfigurationManagerInterface;

/**
 * Class AbstractAuthenticationService
 *
 * @author Steffen Kroggel <developer@steffenkroggel.de>
 * @copyright Steffen Kroggel
 * @package Madj2k_FeRegister
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License, version 3 or later
 */
class AbstractAuthenticationService extends \TYPO3\CMS\Core\Authentication\AuthenticationService
{

    /**
     * Initialize authentication service
     *
     * @param string $mode Subtype of the service which is used to call the service.
     * @param array $loginData Submitted login form data
     * @param array $authInfo Information array. Holds submitted form data etc.
     * @param AbstractUserAuthentication $pObj Parent object
     * @return void
     * @throws \TYPO3\CMS\Extbase\Configuration\Exception\InvalidConfigurationTypeException
     */
    public function initAuth($mode, $loginData, $authInfo, $pObj): void
    {
        parent::initAuth($mode, $loginData, $authInfo, $pObj);

        // set relevant fields and storage pid according to settings
        $this->db_user['type_column'] = 'tx_extbase_type';
        $this->db_user['userpassword_column'] = 'password';
        $this->db_user['usercounter_column'] = 'tx_feregister_login_error_count';
        $this->db_user['check_pid_clause'] = '`pid` IN (' . $this->getStoragePids() . ')';

    }


    /**
     * Process the submitted credentials.
     * In this case hash the clear text password if it has been submitted.
     *
     * @param array $loginData Credentials that are submitted and potentially modified by other services
     * @param string $passwordTransmissionStrategy Keyword of how the password has been hashed or encrypted before submission
     * @return bool
     */
    public function processLoginData(array &$loginData, $passwordTransmissionStrategy): bool
    {
        return parent::processLoginData($loginData, $passwordTransmissionStrategy);
    }


    /**
     * Find usergroup records, currently only for frontend
     *
     * @param array $user Data of user.
     * @param array $knownGroups Group data array of already known groups. This is handy if you want select other related groups. Keys in this array are unique IDs of those groups.
     * @return mixed Groups array, keys = uid which must be unique
     */
    public function getGroups($user, $knownGroups)
    {
        return parent::getGroups($user, $knownGroups);
    }


    /**
     * Returns storagePid
     *
     * @param
     * @return string
     * @throws \TYPO3\CMS\Extbase\Configuration\Exception\InvalidConfigurationTypeException
     */
    protected function getStoragePids(): string
    {
        $storagePid = 0;
        $settings = GeneralUtility::getTypoScriptConfiguration(
            'fe_register',
            ConfigurationManagerInterface::CONFIGURATION_TYPE_FULL_TYPOSCRIPT
        );

        if ($settings['plugin.']['tx_feregister.']['persistence.']['storagePid']) {
            $storagePid = trim(preg_replace('#[^\d,]+#', '', $settings['plugin.']['tx_feregister.']['persistence.']['storagePid']));
        }

        return !empty($storagePid) ? $storagePid : "0";
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
