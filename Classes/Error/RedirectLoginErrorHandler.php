<?php

declare(strict_types=1);

namespace Madj2k\FeRegister\Error;

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

use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Core\Context\Context;
use TYPO3\CMS\Core\Context\Exception\AspectNotFoundException;
use TYPO3\CMS\Core\Controller\ErrorPageController;
use TYPO3\CMS\Core\Error\PageErrorHandler\PageErrorHandlerInterface;
use TYPO3\CMS\Core\Http\HtmlResponse;
use TYPO3\CMS\Core\Http\RedirectResponse;
use TYPO3\CMS\Core\LinkHandling\LinkService;
use TYPO3\CMS\Core\Site\Entity\Site;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Utility\DebuggerUtility;
use TYPO3\CMS\Frontend\Page\PageAccessFailureReasons;

/**
 * TestUrl logged user: https://rkw-kompetenzzentrum.ddev.site:8443/mein-rkw/login/
 * TestUrl not logged user: https://rkw-kompetenzzentrum.ddev.site:8443/mein-rkw/willkommen/
 *
 * Basics:
 * https://docs.typo3.org/m/typo3/reference-coreapi/main/en-us/ApiOverview/SiteHandling/ErrorHandling/WriteCustomErrorHandler.html
 * Option 1:
 * https://www.in2code.de/aktuelles/php-modernes-beispiel-fuer-403-und-404-handling-in-typo3/
 * Option 2:
 * https://github.com/plan2net/sierrha/blob/master/Classes/Error/StatusForbiddenHandler.php
 * Option 3: (used)
 * https://forge.typo3.org/issues/101252
 * https://review.typo3.org/c/Packages/TYPO3.CMS/+/81945/12/typo3/sysext/core/Classes/Error/PageErrorHandler/RedirectLoginErrorHandler.php
 *
 * Example use in config.yaml
 * -
 *  errorCode: 403
 *  errorHandler: PHP
 *  errorPhpClassFQCN: Madj2k\FeRegister\Error\RedirectLoginErrorHandler
 *  redirectPidLoggedUser: 10512
 *  redirectPidNotLoggedUser: 10513
 *
 * @author Maximilian Fäßler <maximilian@faesslerweb.de>
 * @copyright Steffen Kroggel
 * @package Madj2k_FeRegister
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License, version 3 or later
 */
class RedirectLoginErrorHandler implements PageErrorHandlerInterface
{
    /**
     * @var int|mixed
     */
    protected int $redirectPid = 0;

    /**
     * @var int
     */
    protected int $statusCode = 0;

    /**
     * @var Context|object|(object&Context)|(object&Context&\Psr\Log\LoggerAwareInterface)|(object&Context&\TYPO3\CMS\Core\SingletonInterface)|\Psr\Log\LoggerAwareInterface|\TYPO3\CMS\Core\SingletonInterface
     */
    protected Context $context;

    /**
     * @param int   $statusCode
     * @param array $configuration
     * @throws AspectNotFoundException
     */
    public function __construct(int $statusCode, array $configuration)
    {
        $this->statusCode = $statusCode;
        $this->context = GeneralUtility::makeInstance(Context::class);

        // The magic: Define the redirect PID
        $this->redirectPid = (int) $this->isLoggedIn() ?
            $configuration['redirectPidLoggedUser'] : $configuration['redirectPidNotLoggedUser'];
    }


    /**
     * @param ServerRequestInterface $request
     * @param string                 $message
     * @param array                  $reasons
     * @return ResponseInterface
     * @throws AspectNotFoundException
     */
    public function handlePageError(
        ServerRequestInterface $request,
        string $message,
        array $reasons = []
    ): ResponseInterface
    {
        $this->checkHandlerConfiguration();
        if ($this->shouldHandleRequest($reasons)) {
            return $this->handleLoginRedirect($request);
        }
        // Show general error message with a 403 HTTP statuscode
        return $this->getGenericAccessDeniedResponse($message);
    }


    /**
     * @param string $reason
     * @return ResponseInterface
     */
    private function getGenericAccessDeniedResponse(string $reason): ResponseInterface
    {
        $content = GeneralUtility::makeInstance(ErrorPageController::class)->errorAction(
            'Page Not Found',
            'The page did not exist or was inaccessible.' . ($reason ? ' Reason: ' . $reason : ''),
            0,
            $this->statusCode,
        );
        return new HtmlResponse($content, $this->statusCode);
    }


    /**
     * @param ServerRequestInterface $request
     * @return ResponseInterface
     * @throws AspectNotFoundException
     */
    private function handleLoginRedirect(ServerRequestInterface $request): ResponseInterface
    {

        /** @var Site $site */
        $site = $request->getAttribute('site');
        $language = $request->getAttribute('language');
        $loginUrl = $site->getRouter()->generateUri(
            $this->redirectPid,
            [
                '_language' => $language,
            ]
        );

        return new RedirectResponse($loginUrl);
    }


    /**
     * @param array $reasons
     * @return bool
     * @throws AspectNotFoundException
     */
    private function shouldHandleRequest(array $reasons): bool
    {
        if (!isset($reasons['code'])) {
            return false;
        }
        $accessDeniedReasons = [
            PageAccessFailureReasons::ACCESS_DENIED_PAGE_NOT_RESOLVED,
            PageAccessFailureReasons::ACCESS_DENIED_SUBSECTION_NOT_RESOLVED,
        ];
        $isAccessDenied = in_array($reasons['code'], $accessDeniedReasons, true);
        return $isAccessDenied || $this->isSimulatedBackendGroup();
    }


    /**
     * @return bool
     * @throws \TYPO3\CMS\Core\Context\Exception\AspectNotFoundException
     */
    private function isLoggedIn(): bool
    {
        return $this->context->getPropertyFromAspect('frontend.user', 'isLoggedIn') || $this->isSimulatedBackendGroup();
    }



    /**
     * @return bool
     * @throws \TYPO3\CMS\Core\Context\Exception\AspectNotFoundException
     */
    protected function isSimulatedBackendGroup(): bool
    {
        // look for special "any group"
        return $this->context->getPropertyFromAspect('backend.user', 'isLoggedIn')
            && $this->context->getPropertyFromAspect('frontend.user', 'groupIds')[1] === -2;
    }



    /**
     * @return void
     */
    private function checkHandlerConfiguration(): void
    {
        if ($this->redirectPid === 0) {
            throw new \RuntimeException('No loginRedirectTarget configured for LoginRedirect errorhandler', 1723081764);
        }
        if ($this->statusCode !== 403) {
            throw new \RuntimeException('Invalid HTTP statuscode ' . $this->statusCode . ' for LoginRedirect errorhandler', 1723084043);
        }
    }
}
