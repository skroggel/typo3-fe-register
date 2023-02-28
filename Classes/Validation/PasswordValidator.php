<?php

namespace Madj2k\FeRegister\Validation;


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
use Madj2k\FeRegister\Utility\FrontendUserSessionUtility;
use Madj2k\FeRegister\Utility\FrontendUserUtility;
use TYPO3\CMS\Extbase\Error\Error;
use TYPO3\CMS\Extbase\Utility\LocalizationUtility;

/**
 * Class PasswordValidator
 *
 * @author Maximilian Fäßler <maximilian@faesslerweb.de>
 * @author Steffen Kroggel <developer@steffenkroggel.de>
 * @copyright Steffen Kroggel
 * @package Madj2k_FeRegister
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License, version 3 or later
 */
class PasswordValidator extends \TYPO3\CMS\Extbase\Validation\Validator\AbstractValidator
{
    /**
     * @var array
     */
    protected array $passwordArray = [];


    /**
     * @var array
     */
    protected array $passwordSettings = [];


    /**
     * @var bool
     */
    protected bool $isValid = true;


    /**
     * Validation of password
     *
     * @param array $value
     * @return boolean
     * @throws \TYPO3\CMS\Core\Context\Exception\AspectNotFoundException
     * @throws \TYPO3\CMS\Extbase\Configuration\Exception\InvalidConfigurationTypeException
     */
    public function isValid($value): bool
    {
        $this->passwordArray = $value;

        $settings = GeneralUtility::getTypoScriptConfiguration('Feregister');
        $this->passwordSettings = $settings['users']['passwordSettings'];

        $this->checkOldPasswordGiven();
        $this->checkNewPasswordGiven();

        if ($this->isValid) {
            $this->checkOldPasswordValid();
            $this->checkEquality();
            $this->checkLength();
            $this->checkMandatorySigns();
        }

        return $this->isValid;
    }


    /**
     * checkIfPasswordIsGiven
     *
     * @return void
     */
    protected function checkNewPasswordGiven()
    {
        // are the passwords set?
        if (
            (!$this->passwordArray['first'])
            || (!$this->passwordArray['second'])
        ) {

            $this->result->addError(
                new Error(
                    LocalizationUtility::translate(
                        'validator.passwordsNotAllSet',
                        'fe_register'
                    ), 1435068293
                )
            );

            $this->isValid = false;
        }
    }

    /**
     * checkIfOldPasswordIsSet
     *
     * @return void
     */
    protected function checkOldPasswordGiven()
    {
        if (!$this->passwordArray['old']) {

            $this->result->addError(
                new Error(
                    LocalizationUtility::translate(
                        'validator.oldPasswordNotSet',
                        'fe_register'
                    ), 1649148502
                )
            );
            $this->isValid = false;
        }
    }


    /**
     * checkIfOldPasswordIsSet
     *
     * @return void
     * @throws \TYPO3\CMS\Core\Context\Exception\AspectNotFoundException
     */
    protected function checkOldPasswordValid()
    {

        if (!FrontendUserUtility::isPasswordValid(
            FrontendUserSessionUtility::getLoggedInUser(),
            $this->passwordArray['old']
        )) {

            $this->result->addError(
                new Error(
                    LocalizationUtility::translate(
                        'validator.passwordOldWrong',
                        'fe_register'
                    ), 1649151982
                )
            );

            $this->isValid = false;
        }
    }


    /**
     * checkEquality
     *
     * @return void
     */
    protected function checkEquality()
    {
        if ($this->passwordArray['first'] != $this->passwordArray['second']) {

            $this->result->addError(
                new Error(
                    LocalizationUtility::translate(
                        'validator.passwordsNotIdentical',
                        'fe_register'
                    ), 1435068407
                )
            );

            $this->isValid = false;
        }
    }


    /**
     * checkLength
     *
     * @return void
     */
    protected function checkLength()
    {
        // min length
        $minLength = ($this->passwordSettings['minLength'] ?: 8);
        if (strlen($this->passwordArray['first']) < intval($minLength)) {

            $this->result->addError(
                new Error(
                    LocalizationUtility::translate(
                        'validator.passwordTooShort',
                        'fe_register',
                        [$minLength]
                    ), 1435066509
                )
            );

            $this->isValid = false;
        }

        // max length
        $maxLength = ($this->passwordSettings['maxLength'] ?: 100);

        if (strlen($this->passwordArray['first']) > intval($maxLength)) {
            $this->result->addError(
                new Error(
                    LocalizationUtility::translate(
                        'validator.passwordTooLong',
                        'fe_register',
                        [$maxLength]
                    ), 1649316598
                )
            );
            $this->isValid = false;
        }
    }


    /**
     * checkMandatorySigns
     *
     * @return void
     */
    protected function checkMandatorySigns()
    {
        $alphaNum = (bool) $this->passwordSettings['alphaNum'];
        if ($alphaNum) {

            if (
                (!preg_match('/[A-Za-z]/', $this->passwordArray['first']))
                || (!preg_match('/[0-9]/', $this->passwordArray['first']))
            ) {
                $this->result->addError(
                    new Error(
                        LocalizationUtility::translate(
                            'validator.passwordMissingSigns',
                            'fe_register'
                        ), 1435066509
                    )
                );

                $this->isValid = false;
            }
        }
    }
}

