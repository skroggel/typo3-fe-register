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

/**
 * Class ZipValidator
 *
 * @author Maximilian Fäßler <maximilian@faesslerweb.de>
 * @author Steffen Kroggel <developer@steffenkroggel.de>
 * @copyright Steffen Kroggel
 * @package Madj2k_FeRegister
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License, version 3 or later
 * @todo write a fucking test
 */
class ZipValidator extends \TYPO3\CMS\Extbase\Validation\Validator\AbstractValidator
{
    /**
     * This validator always needs to be executed even if the given value is empty.
     * See AbstractValidator::validate()
     *
     * @var bool
     */
    protected $acceptsEmptyValues = false;


    /**
     * Checks if the given property ($propertyValue) has 5 digits, is integer and not null.
     *
     * @param int $value
     * @return void
     */
    public function isValid($value): void
    {
        if (
            !$value
            || strlen(trim($value)) != 5
        ) {
            $this->addError(
                $this->translateErrorMessage(
                    'validator.zip.incorrect',
                    'fe_register'
                ),
                1462806656
            );
        }
    }
}

