<?php

namespace Javaabu\JsLocalization\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Javaabu\Manifest\Manifest;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;

/**
 * Requires the Blade entry on full HTML pages, so manifest injects it. It
 * runs inside manifest's InjectAssets, after the page has rendered.
 */
class LoadTranslations
{
    public const string ASSET = 'js-localization::i18n';

    public function __construct(protected Manifest $manifest) {}

    public function handle(Request $request, Closure $next): SymfonyResponse
    {
        $response = $next($request);

        if ($this->shouldLoad($request, $response)) {
            $this->manifest->require(self::ASSET);
        }

        return $response;
    }

    protected function shouldLoad(Request $request, SymfonyResponse $response): bool
    {
        if (! config('js-localization.blade.auto', true)) {
            return false;
        }

        if (! $response instanceof Response) {
            return false;
        }

        if ($request->headers->has('X-Inertia')) {
            return false;
        }

        $content_type = $response->headers->get('Content-Type');

        if ($content_type && ! str_contains(strtolower($content_type), 'text/html')) {
            return false;
        }

        $content = $response->getContent();

        if (! is_string($content)) {
            return false;
        }

        if (! str_contains($content, '</body>')) {
            return false;
        }

        // Inertia's root view: the app's own bundle sets up @javaabu/i18n.
        return ! str_contains($content, 'data-page=');
    }
}
