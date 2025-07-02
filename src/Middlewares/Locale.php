<?php

namespace Artisan\Middlewares;

use Artisan\Services\Language;
use Symfony\Component\HttpFoundation\Request;
use Artisan\Routing\Interfaces\IMiddleware;

class Locale implements IMiddleware
{
    public function before(Request $request): void
    {
        // 1. Primero intenta obtener el idioma desde el parámetro 'lang' en la URL
        $lang = $request->query->get('lang');

        if ($lang) {
            // Si se encuentra el parámetro, usa ese idioma
            Language::setLocale($lang);
        } else {
            // 2. Si no se encuentra 'lang', intenta obtenerlo desde el header 'Accept-Language'
            $header = $request->headers->get('Accept-Language');

            if ($header) {
                $locale = $this->parseLocale($header);
                if ($locale) {
                    Language::setLocale($locale);
                }
            }
        }

        // 3. Si no se encuentra ninguno, se usa el idioma por defecto configurado
        if (!Language::i()->getLocale()) {
            $defaultLocale = Language::i()->getDefaultLocale();
            Language::setLocale($defaultLocale);
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
