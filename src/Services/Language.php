<?php

namespace Artisan\Services;

use Symfony\Component\Translation\Loader\YamlFileLoader;
use Symfony\Component\Translation\Loader\JsonFileLoader;
use Symfony\Component\Translation\Translator;
use RuntimeException;

class Language
{
    public const string YAML_FORMAT = 'yaml';
    public const string JSON_FORMAT = 'json';

    private static ?self $instance = null;
    private Translator $translator;
    private string $locale;
    private string $path;

    private function __construct() {}
    private function __clone() {}

    public static function load(array $config): void
    {
        if (!isset($config['locale'], $config['path'])) {
            throw new RuntimeException('Language::load requires both "locale" and "path"');
        }

        $format = $config['file_format'] ?? self::YAML_FORMAT;
        if (!in_array($format, [self::YAML_FORMAT, self::JSON_FORMAT])) {
            throw new RuntimeException('Unsupported file format: ' . $format);
        }

        $self = new self();
        $self->locale = $config['locale'];
        $self->path = rtrim($config['path'], '/');

        $translator = new Translator($self->locale);

        match ($format) {
            self::YAML_FORMAT => $translator->addLoader(self::YAML_FORMAT, new YamlFileLoader()),
            self::JSON_FORMAT => $translator->addLoader(self::JSON_FORMAT, new JsonFileLoader()),
        };

        foreach (scandir($self->path) as $file) {
            if (preg_match('/^([a-z]{2}(?:[-_][A-Z]{2})?)\.([a-z]+)(?:\.yaml)?$/i', $file, $matches)) {
                $lang = $matches[1];
                $translator->addResource($format, "{$self->path}/$file", $lang);
            }
        }

        $self->translator = $translator;
        self::$instance = $self;
    }

    public static function i(): self
    {
        if (!self::$instance) {
            throw new RuntimeException('Language service not initialized. Call Language::load() first.');
        }

        return self::$instance;
    }

    public static function getTwigFunction(): mixed
    {
        if (!class_exists(\Twig\TwigFunction::class)) {
            throw new RuntimeException('Twig is not installed. Cannot create twig translation function.');
        }

        return new \Twig\TwigFunction('t', function (string $key, array $params = []) {
            return self::i()->trans($key, $params);
        });
    }

    public function trans(string $key, array $params = [], ?string $domain = null, ?string $locale = null): string
    {
        return $this->translator->trans($key, $params, $domain, $locale);
    }

    public function setLocale(string $locale): void
    {
        $this->translator->setLocale($locale);
    }

    public function getLocale(): string
    {
        return $this->translator->getLocale() ?? $this->locale;
    }
}
