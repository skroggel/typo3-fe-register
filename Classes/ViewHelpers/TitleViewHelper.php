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

use TYPO3Fluid\Fluid\Core\ViewHelper\AbstractViewHelper;
use TYPO3Fluid\Fluid\Core\Rendering\RenderingContextInterface;

/**
 * Class TitleViewHelper
 *
 * @author Steffen Kroggel <developer@steffenkroggel.de>
 * @copyright Steffen Kroggel
 * @package Madj2k_FeRegister
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License, version 3 or later
 */
class TitleViewHelper extends AbstractViewHelper
{

    /**
     * Return the title
     *
     * @param \Madj2k\FeRegister\Domain\Model\Title|null $title
     * @param bool $titleAfter
     * @return string $string
     */
    public function render(\Madj2k\FeRegister\Domain\Model\Title $title = null, bool $titleAfter = false): string
    {

        return static::renderStatic(
            [
                'title'      => $title,
                'titleAfter' => $titleAfter,
            ],
            $this->buildRenderChildrenClosure(),
            $this->renderingContext
        );
    }


    /**
     * Static rendering
     *
     * @param array $arguments
     * @param \Closure $renderChildrenClosure
     * @param RenderingContextInterface $renderingContext
     * @return string
     */
        static public function renderStatic(
            array $arguments,
            \Closure $renderChildrenClosure,
            RenderingContextInterface $renderingContext
        ): string {

        /** @var \Madj2k\FeRegister\Domain\Model\Title $title */
        $title = $arguments['title'];
        $titleAfter = $arguments['titleAfter'];

        if ($title instanceof \Madj2k\FeRegister\Domain\Model\Title) {
            if ($titleAfter == $title->getIsTitleAfter()) {
                return $title->getName();
            }
        }

        return '';
    }
}


