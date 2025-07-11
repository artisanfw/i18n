<?php

namespace Artisan\Middlewares;

use Artisan\Services\Language;
use Symfony\Component\HttpFoundation\Request;
use Artisan\Routing\Interfaces\IApiResponse;
use Artisan\Routing\Interfaces\IMiddleware;

class Locale implements IMiddleware
{
    public function run(array $routeParams, Request $request, IApiResponse $response): void
    {
        $lang = $request->query->get('lang');

        if ($lang) {
            Language::i()->setLocale($lang);
        } else {
            $header = $request->headers->get('Accept-Language');
            if ($header) {
                $locale = $this->parseLocale($header);
                if ($locale) {
                    Language::i()->setLocale($locale);
                }
            }
        }
    }

    private function parseLocale(string $header): ?string
    {
        if (preg_match('/^([a-z]{2})(-[A-Z]{2})?/', $header, $matches)) {
            return strtolower($matches[1]);
        }
        return null;
    }
}
