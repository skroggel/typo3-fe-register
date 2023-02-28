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

use TYPO3\CMS\Extbase\Error\Error;
use TYPO3\CMS\Extbase\Utility\LocalizationUtility;
use TYPO3\CMS\Extbase\Validation\Validator\AbstractValidator;

/**
 * Class LoginValidator
 *
 * @author Maximilian Fäßler <maximilian@faesslerweb.de>
 * @copyright Steffen Kroggel
 * @package Madj2k_FeRegister
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License, version 3 or later
 * @todo write a fucking test
 */
class LoginValidator extends AbstractValidator
{
    /**
     * Validation of terms checkbox
     *
     * @param array $login
     * @return boolean
     */
    public function isValid($login): bool
    {
        $isValid = true;

        if (!$login['username']) {

            $this->result->forProperty('username')->addError(
                new Error(
                    LocalizationUtility::translate(
                        'loginValidator.error.login_noUsername',
                        'fe_register'
                    ), 1649340637
                )
            );
            $isValid = false;
        }

        if (!$login['password']) {
            $this->result->forProperty('password')->addError(
                new Error(
                    LocalizationUtility::translate(
                        'loginValidator.error.login_no_password',
                        'fe_register'
                    ), 1649340691
                )
            );
            $isValid = false;
        }

        return $isValid;
    }
}
