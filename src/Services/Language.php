<?php

namespace Artisan\Services;

use Symfony\Component\Translation\Loader\YamlFileLoader;
use Symfony\Component\Translation\Loader\JsonFileLoader;
use Symfony\Component\Translation\Formatter\MessageFormatter;
use Symfony\Component\Translation\Translator;
use RuntimeException;

class Language
{
    public const string YAML_FORMAT = 'yaml';
    public const string JSON_FORMAT = 'json';

    public const string WRAPPER_CURLY_BRACES = '{}';
    public const string WRAPPER_PERCENT_SIGN = '%';

    private static ?self $instance = null;
    private Translator $translator;
    private string $locale;
    private string $path;
    private string $defaultVarWrapper = self::WRAPPER_PERCENT_SIGN;

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

        $wrapper = $config['wrapper'] ?? self::WRAPPER_PERCENT_SIGN;
        if (!in_array($config['wrapper'], [self::WRAPPER_CURLY_BRACES, self::WRAPPER_PERCENT_SIGN])) {
            throw new RuntimeException('Unsupported wrapper: ' . $wrapper);
        }

        $self = new self();
        $self->locale = $config['locale'];
        $self->path = rtrim($config['path'], '/');
        $self->defaultVarWrapper = $wrapper;

        $formatter = new MessageFormatter();
        $translator = new Translator($self->locale, $formatter);
        $translator->addLoader(self::YAML_FORMAT, new YamlFileLoader());
        $translator->addLoader(self::JSON_FORMAT, new JsonFileLoader());

        foreach (scandir($self->path) as $file) {
            $fullPath = "{$self->path}/$file";

            if (str_ends_with($file, '.'.self::YAML_FORMAT)) {
                $translator->addResource(self::YAML_FORMAT, $fullPath, $self->locale);
            } elseif (str_ends_with($file, '.'.self::JSON_FORMAT)) {
                $translator->addResource(self::JSON_FORMAT, $fullPath, $self->locale);
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

        return new \Twig\TwigFunction('t', function (string $key, array $params = [], ?string $domain = null, ?string $locale = null) {
            return self::i()->trans(key: $key, params: $params, domain: $domain, locale: $locale);
        });
    }

    public function trans(string $key, array $params = [], ?string $domain = null, ?string $locale = null): string
    {
        $processedParams = [];

        foreach ($params as $paramKey => $value) {
            if (
                (str_starts_with($paramKey, '{') && str_ends_with($paramKey, '}')) ||
                (str_starts_with($paramKey, '%') && str_ends_with($paramKey, '%'))
            ) {
                $processedParams[$paramKey] = $value;
            } else {
                if ($this->defaultVarWrapper == self::WRAPPER_PERCENT_SIGN) {
                    $sw = $ew = '%';
                } else {
                    $sw = '{';
                    $ew = '}';
                }
                $processedParams[$sw . $paramKey . $ew] = $value;
            }
        }

        return $this->translator->trans($key, $processedParams, $domain, $locale);
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
