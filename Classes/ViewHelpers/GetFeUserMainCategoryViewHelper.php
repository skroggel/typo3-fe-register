<?php

namespace Madj2k\FeRegister\ViewHelpers;

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

use DmitryDulepov\Realurl\Domain\Repository\AbstractRepository;
use Madj2k\CoreExtended\Utility\GeneralUtility;
use Madj2k\FeRegister\Domain\Model\Category;
use Madj2k\FeRegister\Domain\Repository\CategoryRepository;
use Madj2k\FeRegister\Registration\FrontendUserRegistration;
use TYPO3\CMS\Extbase\Configuration\ConfigurationManagerInterface;
use TYPO3\CMS\Extbase\Object\ObjectManager;
use TYPO3\CMS\Extbase\Persistence\Exception\InvalidQueryException;
use TYPO3\CMS\Extbase\Utility\DebuggerUtility;
use TYPO3Fluid\Fluid\Core\ViewHelper\AbstractViewHelper;

/**
 * Class GetFeUserParentCategoryViewHelper
 *
 * @author Maximilian Fäßler <maximilian@faesslerweb.de>
 * @copyright Steffen Kroggel
 * @package Madj2k_FeRegister
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License, version 3 or later
 */
class GetFeUserMainCategoryViewHelper extends AbstractViewHelper
{

    protected array $categoryList = [];

    /**
     * returns categories tree related to a given pid
     *
     * @return array
     * @throws \TYPO3\CMS\Extbase\Configuration\Exception\InvalidConfigurationTypeException
     */
    public function render(): array
    {
        $settings = $this->getSettings();

        $categoryParentId = $settings['users']['categories']['topicParentId'];

        if (!$categoryParentId) {
            return $this->categoryList;
        }

        $repositoryName = CategoryRepository::class;
        $objectManager = GeneralUtility::makeInstance(ObjectManager::class);
        /** @var CategoryRepository $repository */
        $repository = $objectManager->get($repositoryName);

        /** @var Category $initialCategory */
        $initialCategory = $repository->findByUid($categoryParentId);
        $this->categoryList[] = $initialCategory;

        $this->getCategoryChildrenRecursive($initialCategory, $repository);

        return $this->categoryList;
    }


    /**
     * @param Category           $category
     * @param CategoryRepository $repository
     * @return void
     * @throws InvalidQueryException
     */
    protected function getCategoryChildrenRecursive(Category $category, CategoryRepository $repository)
    {
        $childElements = $repository->findChildrenByParent($category->getUid());
        if (count($childElements)) {
            $this->categoryList['subCategoryList'][$category->getUid()] = $childElements;

            foreach ($childElements as $childElement) {
                $this->getCategoryChildrenRecursive($childElement, $repository);
            }
        }

    }


    /**
     * Returns TYPO3 settings
     *
     * @param string $which Which type of settings will be loaded
     * @return array
     * @throws \TYPO3\CMS\Extbase\Configuration\Exception\InvalidConfigurationTypeException
     */
    protected function getSettings(string $which = ConfigurationManagerInterface::CONFIGURATION_TYPE_SETTINGS): array
    {
        return GeneralUtility::getTypoScriptConfiguration('Feregister', $which);
    }


}
