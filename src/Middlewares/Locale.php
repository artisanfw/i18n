<?php

namespace Artisan\Middlewares;

use Artisan\Services\Language;
use Symfony\Component\HttpFoundation\Request;
use Artisan\Routing\Interfaces\IMiddleware;

class Locale implements IMiddleware
{
    public function before(Request $request): void
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

    public function after(Request $request, mixed $response): void { }

    private function parseLocale(string $header): ?string
    {
        // Extrae el idioma principal del header Accept-Language (p.ej., "es-ES,es;q=0.9,en;q=0.8")
        if (preg_match('/^([a-z]{2})(-[A-Z]{2})?/', $header, $matches)) {
            return strtolower($matches[1]);  // Devuelve el código de idioma en minúsculas, como "es"
        }
        return null;
    }
}
