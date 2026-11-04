<?php

namespace Javaabu\JsLocalization\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Javaabu\JsLocalization\JsLocalization;

class TranslationsController
{
    /**
     * One locale's lines. Browsers revalidate with the ETag, so a page gets
     * new lines as soon as a language file changes.
     */
    public function show(Request $request, JsLocalization $localization, string $locale): JsonResponse
    {
        if (! $localization->hasLocale($locale)) {
            abort(404);
        }

        $response = new JsonResponse(
            (object) $localization->forLocale($locale),
            options: JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES,
        );

        $response->setEtag($localization->fingerprint());
        $response->setPublic();
        $response->headers->addCacheControlDirective('no-cache');
        $response->isNotModified($request);

        return $response;
    }
}
