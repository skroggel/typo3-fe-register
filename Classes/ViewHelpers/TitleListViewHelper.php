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

use Madj2k\FeRegister\Domain\Model\Title;
use Madj2k\FeRegister\Domain\Repository\TitleRepository;
use TYPO3\CMS\Extbase\Object\ObjectManager;
use TYPO3Fluid\Fluid\Core\Rendering\RenderingContextInterface;

/**
 * Class TitleListViewHelper
 *
 * @author Maximilian Fäßler <maximilian@faesslerweb.de>
 * @copyright Fäßler Web UG
 * @date October 2018
 * @package Madj2k_FeRegister
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License, version 3 or later
 */
class TitleListViewHelper extends \TYPO3Fluid\Fluid\Core\ViewHelper\AbstractViewHelper
{

    /**
     * Initialize arguments.
     *
     * @return void
     * @throws \TYPO3Fluid\Fluid\Core\ViewHelper\Exception
     */
    public function initializeArguments(): void
    {
        parent::initializeArguments();
        $this->registerArgument('showTitleBefore', 'bool', 'Show title before name?', false, true);
        $this->registerArgument('showTitleAfter ', 'bool', 'Show title after name?', false, false);
        $this->registerArgument('returnArray ', 'bool', 'Return titles as array?', false, false);
        $this->registerArgument('returnJson', 'bool', 'Return titles as JSON?', false, false);
        $this->registerArgument('mapProperty', 'string', 'Property for mapping', false, '');
    }


    /**
     * Returns a list of user title options
     *
     * @param array $arguments
     * @param \Closure $renderChildrenClosure
     * @param RenderingContextInterface $renderingContext
     * @return mixed
     */
    static public function renderStatic(
        array $arguments,
        \Closure $renderChildrenClosure,
        RenderingContextInterface $renderingContext
    ) {

        /** @var bool $showTitleBefore */
        $showTitleBefore = (bool) $arguments['showTitleBefore'];

        /** @var bool $showTitleAfter */
        $showTitleAfter = (bool) $arguments['showTitleAfter'];

        /** @var bool $returnArray */
        $returnArray = (bool) $arguments['returnArray'];

        /** @var bool $returnJson */
        $returnJson = (bool) $arguments['returnJson'];

        /** @var string $mapProperty */
        $mapProperty = $arguments['mapProperty'];

        // a) This avoids possible empty results by calling <feRegister:titleList showTitleBefore='false' showTitleAfter='false' />
        // b) Makes a shorter invoke possible for showing up only "isTitleAfter"-Elements (see PHPdocs example above)
        if (!$showTitleBefore) {
            $showTitleAfter = true;
        }

        /** @var ObjectManager $objectManager */
        $objectManager = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(ObjectManager::class);
        /** @var TitleRepository $titleRepository */
        $titleRepository = $objectManager->get(TitleRepository::class);

        $titles = $titleRepository->findAllOfType($showTitleBefore, $showTitleAfter, $returnArray);

        if ($mapProperty) {
            $mappedTitles = array_map(function($item) use ($mapProperty) {
                return $item[$mapProperty];
            }, $titles);

            $titles = $mappedTitles;
        }

        return ($returnJson) ? json_encode($titles) : $titles;
    }
}
