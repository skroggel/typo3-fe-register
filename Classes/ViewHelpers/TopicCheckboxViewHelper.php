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

use Madj2k\FeRegister\Domain\Model\FrontendUser;
use Madj2k\FeRegister\Utility\FrontendUserSessionUtility;
use TYPO3\CMS\Extbase\Configuration\ConfigurationManagerInterface;
use Madj2k\CoreExtended\Utility\GeneralUtility;
use TYPO3Fluid\Fluid\Core\ViewHelper\AbstractViewHelper;

/**
 * Class TopicCheckboxViewHelper
 *
 * @author Maximilian Fäßler <maximilian@faesslerweb.de>
 * @author Steffen Kroggel <developer@steffenkroggel.de>
 * @copyright Steffen Kroggel
 * @package Madj2k_FeRegister
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License, version 3 or later
 */
class TopicCheckboxViewHelper extends AbstractViewHelper
{

    /**
     * @const string
     */
    const NAMESPACE = 'tx_feregister';

    /**
     * @const string
     */
    const IDENTIFIER = 'topic';


    /**
     * As this ViewHelper renders HTML, the output must not be escaped.
     *
     * @var bool
     */
    protected $escapeOutput = false;


    /**
     * Initialize arguments.
     *
     * @return void
     * @throws \TYPO3Fluid\Fluid\Core\ViewHelper\Exception
     */
    public function initializeArguments(): void
    {
        parent::initializeArguments();

        $this->registerArgument('category', \Madj2k\FeRegister\Domain\Model\Category::class, 'The category', true);
        $this->registerArgument('categoryTree', 'array', 'The category tree array', false, []);

    }


    /**
     * Returns a standard checkbox with text
     * (not a partial because this is more complicated to use it universally in several extensions)
     *
     * @return string
     * @throws \TYPO3\CMS\Extbase\Mvc\Exception\InvalidExtensionNameException
     * @throws \TYPO3Fluid\Fluid\View\Exception\InvalidTemplateResourceException
     * @throws \TYPO3\CMS\Extbase\Configuration\Exception\InvalidConfigurationTypeException
     * @throws \Exception
     */
    public function render(): string
    {
        $settings = $this->getSettings(ConfigurationManagerInterface::CONFIGURATION_TYPE_FRAMEWORK);
        $category = $this->arguments['category'];

        /** @var \TYPO3\CMS\Fluid\View\StandaloneView $standaloneView */
        $standaloneView = GeneralUtility::makeInstance(\TYPO3\CMS\Fluid\View\StandaloneView::class);
        $standaloneView->setLayoutRootPaths($settings['view']['layoutRootPaths']);
        $standaloneView->setPartialRootPaths($settings['view']['partialRootPaths']);
        $standaloneView->setTemplateRootPaths($settings['view']['templateRootPaths']);
        $standaloneView->setTemplate('ViewHelpers/Topic/Checkbox.html');

        $frontendUser = FrontendUserSessionUtility::getLoggedInUser();

        // is there a form request?
        $args = \TYPO3\CMS\Core\Utility\GeneralUtility::_GP(self::NAMESPACE);
        $checked = false;
        if (
            is_array($args)
            && key_exists(self::IDENTIFIER, $args)
        ) {

            if (key_exists($category->getUid(), array_filter($args[self::IDENTIFIER]))) {
                $checked = true;
            }

        // else check topics of logged in user
        } elseif ($frontendUser instanceof FrontendUser) {
            foreach ($frontendUser->getTxFeregisterConsentTopics() as $userCategory) {
                if ($userCategory->getUid() == $category->getUid()) {
                    $checked = true;
                    break;
                }
            }
        }

        $standaloneView->assignMultiple(
            [
                'namespace' => self::NAMESPACE,
                'identifier' => self::IDENTIFIER,
                'category' => $category,
                'categoryTree' => $this->arguments['categoryTree'],
                'checked' => $checked,
                'settings' => $settings['settings'] ?? []
            ]
        );

        return $standaloneView->render();
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
