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

use Madj2k\FeRegister\Domain\Model\Category;
use Madj2k\FeRegister\Domain\Repository\CategoryRepository;
use TYPO3\CMS\Extbase\Configuration\ConfigurationManagerInterface;
use Madj2k\CoreExtended\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Object\ObjectManager;
use TYPO3\CMS\Extbase\Utility\DebuggerUtility;
use TYPO3Fluid\Fluid\Core\ViewHelper\AbstractViewHelper;

/**
 * Class TopicViewHelper
 *
 * @author Maximilian Fäßler <maximilian@faesslerweb.de>
 * @author Steffen Kroggel <developer@steffenkroggel.de>
 * @copyright Steffen Kroggel
 * @package Madj2k_FeRegister
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License, version 3 or later
 */
class TopicListViewHelper extends AbstractViewHelper
{

    /**
     * As this ViewHelper renders HTML, the output must not be escaped.
     *
     * @var bool
     */
    protected $escapeOutput = false;


    /**
     * @var \Madj2k\FeRegister\Domain\Repository\CategoryRepository|null
     */
    protected ?CategoryRepository $categoryRepository = null;


    /**
     * @param \Madj2k\FeRegister\Domain\Repository\CategoryRepository $categoryRepository
     * @return void
     */
    public function injectCategoryRepository(CategoryRepository $categoryRepository)
    {
        $this->categoryRepository = $categoryRepository;
    }


    /**
     * Initialize arguments.
     *
     * @return void
     * @throws \TYPO3Fluid\Fluid\Core\ViewHelper\Exception
     */
    public function initializeArguments(): void
    {
        parent::initializeArguments();
    }


    /**
     * Returns a standard checkbox with text
     * (not a partial because this is more complicated to use it universally in several extensions)
     *
     * @return string
     * @throws \TYPO3Fluid\Fluid\View\Exception\InvalidTemplateResourceException
     * @throws \TYPO3\CMS\Extbase\Configuration\Exception\InvalidConfigurationTypeException
     * @throws \Exception
     */
    public function render(): string
    {
        $settings = $this->getSettings(ConfigurationManagerInterface::CONFIGURATION_TYPE_FRAMEWORK);
        $categoryTree = [];
        $categoryParent = null;

        /** @deprecated injection method above should suffice from TYPO3 v10 on */
        $repositoryName = CategoryRepository::class;
        $objectManager = GeneralUtility::makeInstance(ObjectManager::class);
        /** @var \Madj2k\FeRegister\Domain\Repository\CategoryRepository $repository */
        $this->categoryRepository = $objectManager->get($repositoryName);

        /** @var \TYPO3\CMS\Fluid\View\StandaloneView $standaloneView */
        $standaloneView = GeneralUtility::makeInstance(\TYPO3\CMS\Fluid\View\StandaloneView::class);
        $standaloneView->setLayoutRootPaths($settings['view']['layoutRootPaths']);
        $standaloneView->setPartialRootPaths($settings['view']['partialRootPaths']);
        $standaloneView->setTemplateRootPaths($settings['view']['templateRootPaths']);
        $standaloneView->setTemplate('ViewHelpers/Topic/List.html');

        if (isset($settings['settings']['consent']['topics']['categoryParentUid'])) {

            /** @var \Madj2k\FeRegister\Domain\Model\Category $$categoryParent*/
            $categoryParent= $this->categoryRepository->findByUid($settings['settings']['consent']['topics']['categoryParentUid']);
            $categoryTree = $this->buildCategoryTreeRecursive($categoryParent, intval($settings['settings']['consent']['topics']['categoryMaxDepth']));
        }

        $standaloneView->assignMultiple(
            [
                'categoryTree' => $categoryTree,
                'categoryParent' => $categoryParent,
                'settings' => $settings['settings']['consent']['topics'] ?? [],
            ]
        );

        return $standaloneView->render();
    }


    /**
     * Build category tree recursively
     *
     * @param \Madj2k\FeRegister\Domain\Model\Category $category
     * @param int $depth
     * @return array
     * @throws \TYPO3\CMS\Extbase\Persistence\Exception\InvalidQueryException
     */
    protected function buildCategoryTreeRecursive(Category $category, int $maxDepth = -1, int $currentDepth = 0): array
    {
        $categoryArray  = [];

        if (
            ($maxDepth == -1)
            || (
                ($maxDepth > -1)
                && ($currentDepth <= $maxDepth)
            )
        ) {
            $categoryResults = $this->categoryRepository->findChildrenByParent($category->getUid());
            if (count($categoryResults)) {
                foreach ($categoryResults as $categoryResult) {
                    $categoryArray[$categoryResult->getUid()]['category'] = $categoryResult;

                    $currentDepth++;
                    $categoryArray[$categoryResult->getUid()]['children'] = $this->buildCategoryTreeRecursive($categoryResult, $maxDepth, $currentDepth);
                    $currentDepth--;
                }
            }
        }

        return $categoryArray;
    }


    /**
     * Returns TYPO3 settings
     *
     * @param string $which Which type of settings will be loaded
     * @return array
     * @throws \TYPO3\CMS\Extbase\Configuration\Exception\InvalidConfigurationTypeException
     */
    public function getSettings(string $which = ConfigurationManagerInterface::CONFIGURATION_TYPE_SETTINGS): array
    {
        return GeneralUtility::getTypoScriptConfiguration('Feregister', $which);
    }

}
