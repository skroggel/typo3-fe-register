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

use Madj2k\FeRegister\Domain\Model\FrontendUser;
use Madj2k\FeRegister\Utility\FrontendUserSessionUtility;
use Madj2k\FeRegister\Utility\FrontendUserUtility;
use TYPO3\CMS\Extbase\Error\Error;
use TYPO3\CMS\Extbase\Utility\LocalizationUtility;

/**
 * Class FrontendUserValidator
 *
 * @author Maximilian Fäßler <maximilian@faesslerweb.de>
 * @author Steffen Kroggel <developer@steffenkroggel.de>
 * @copyright Steffen Kroggel
 * @package Madj2k_FeRegister
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License, version 3 or later
 */
class FrontendUserValidator extends \TYPO3\CMS\Extbase\Validation\Validator\AbstractValidator
{

    /**
     * @var \Madj2k\FeRegister\Domain\Model\FrontendUser|null
     */
    protected ?FrontendUser $frontendUser = null;


    /**
     * @var bool
     */
    protected bool $isValid = true;


    /**
     * @var array
     */
    protected array$requiredFields = [];


    /**
     * validation
     *
     * @return boolean
     * @throws \TYPO3\CMS\Extbase\Configuration\Exception\InvalidConfigurationTypeException
     * @throws \TYPO3\CMS\Core\Context\Exception\AspectNotFoundException
     * @throws \TYPO3\CMS\Core\Context\Exception\AspectNotFoundException
     * @var \Madj2k\FeRegister\Domain\Model\FrontendUser $value
     */
    public function isValid($value): bool
    {
        $this->frontendUser = $value;

        // get required fields of user
        $this->requiredFields = FrontendUserUtility::getMandatoryFields($this->frontendUser);

        // is username unique?
        $this->checkUsername();

        // is email valid?
        $this->checkEmail();

        // is zip valid?
        $this->checkZip();

        // check mandatory fields
        $this->checkMandatoryFields();

        return $this->isValid;
    }


    /**
     * checkUsername
     *
     * @return void
     * @throws \TYPO3\CMS\Core\Context\Exception\AspectNotFoundException
     */
    protected function checkUsername()
    {
        // we have to take the email from the form because the username is never asked for in forms
        if (! FrontendUserUtility::isUsernameUnique($this->frontendUser->getEmail(), FrontendUserSessionUtility::getLoggedInUser())) {
            $this->result->forProperty('email')->addError(
                new Error(
                    LocalizationUtility::translate(
                        'frontendUserValidator.error.usernameExists',
                        'fe_register'
                    ), 1628688993
                )
            );
            $this->isValid = false;
        }
    }


    /**
     * checkEmail
     *
     * @return void
     */
    protected function checkEmail()
    {
        if ($this->frontendUser->getEmail()) {
            if (! FrontendUserUtility::isEmailValid($this->frontendUser->getEmail())) {
                $this->result->forProperty('email')->addError(
                    new Error(
                        LocalizationUtility::translate(
                            'validator.emailInvalid',
                            'fe_register'
                        ), 1414589184
                    )
                );
                $this->isValid = false;
            }
        }
    }


    /**
     * checkZip
     *
     * @return void
     */
    protected function checkZip()
    {
        // check valid zip
        if ($this->frontendUser->getZip()) {

            if ((strlen(trim($this->frontendUser->getZip())) != 5)
                || !is_numeric($this->frontendUser->getZip())
            ) {

                $this->result->forProperty('zip')->addError(
                    new Error(
                        $this->translateErrorMessage(
                            'validator.zip.incorrect',
                            'feRegister'
                        ), 1462806656
                    )
                );
                $this->isValid = false;
            }
        }
    }


    /**
     * checkMandatoryFields
     *
     * @return void
     */
    protected function checkMandatoryFields()
    {
        foreach ($this->requiredFields as $property) {

            $getter = 'get' . ucfirst($property);
            if (
                (empty($this->frontendUser->$getter()))
                ||(
                    ($property == 'txFeregisterGender')
                    && ($this->frontendUser->$getter()== 99)
                )
            ){
                $this->result->forProperty($property)->addError(
                    new Error(
                        LocalizationUtility::translate(
                            'validator.fieldNotFilled',
                            'fe_register'
                        ), 1414595322
                    )
                );
                $this->isValid = false;
            }
        }
    }
}

