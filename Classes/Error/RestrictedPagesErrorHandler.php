<?php

declare(strict_types=1);

namespace Madj2k\FeRegister\Error;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Core\Error\PageErrorHandler\PageErrorHandlerInterface;
use TYPO3\CMS\Core\Http\HtmlResponse;

/**
 * https://docs.typo3.org/m/typo3/reference-coreapi/main/en-us/ApiOverview/SiteHandling/ErrorHandling/WriteCustomErrorHandler.html
 */
final class RestrictedPagesErrorHandler implements PageErrorHandlerInterface
{
    private int $statusCode;
    private array $errorHandlerConfiguration;

    public function __construct(int $statusCode, array $configuration)
    {
        $this->statusCode = $statusCode;
        // This contains the configuration of the error handler which is
        // set in site configuration - this example does not use it.
        $this->errorHandlerConfiguration = $configuration;
    }

    public function handlePageError(
        ServerRequestInterface $request,
        string $message,
        array $reasons = []
    ): ResponseInterface {
        return new HtmlResponse('<h1>Not found, sorry</h1>', $this->statusCode);

        // use reason and message to decide what to do
        if(
            array_key_exists('code', $reasons) &&
            $reasons['code'] === 'page' &&
            $message === 'The requested page does not exist'
        ) {
            return new RedirectResponse('/custom-page-does-not-exist', $this->statusCode);
        }
        // if no reason or message matches, use your selected default error handling
        return new RedirectResponse('/' . $this->statusCode, $this->statusCode);

    }
}
