# i18n
A lightweight abstraction layer over Symfony Translation that enables multi-language support for APIs, email templates, or other applications.

## Install
```bash
composer require artisanfw/i18n
```

## Configuration and Usage
### Load the Service
```php
$config = [
    'locale' => 'en',                         // Default language
    'path' => PROJECT_DIR . '/locales',       // Path to translation files
    'file_format' => \Artisan\Services\Language::YAML_FORMAT,
    'default_domain' => 'messages',           // Default domain
];

Language::load($config);
```
Translation files must follow the naming pattern: `<domain>.<locale>.<format>`, where:
* `<domain>`: Identifier of the translation domain (e.g.., messages, forms, payments, etc)
* `<locale>`: Language code (e.g., `en`, `es`, `fr`, `es_ES`, `en_US`, etc).
* `<format>`: File format.

Supported file formats:
* `Language::YAML_FORMAT`
* `Language::JSON_FORMAT`

#### Examples
`locales/messages.en.yaml`
```yaml
welcome: "Welcome, %name%!"
```
`locales/messages.es.yaml`
```yaml
welcome: "¡Bienvenido, %name%!"
```

## Translate a Message
```php
echo Language::i()->trans('welcome', ['%name%' => 'Airam']);
// Output: "Welcome, Airam!"
```
### Overriding the Locale
```php
echo Language::i()->trans('welcome', ['%name%' => 'Airam'], 'es');
// Output: "¡Bienvenido, Airam!"
```
## Optional: Using Translations in Twig Templates
If you're using Twig (e.g., for rendering HTML emails), the translation function can be registered manually:
```php
$twig->addFunction(Language::getTwigFunction());
```
> **Note:** Twig must be installed separately. If not present, the translation function will not be available.

If you're using the `artisanfw/twig` package, simply include `Language::getTwigFunction()` in the Twig config under `functions`.

### Translation Usage in Twig
```twig
<p>{{ t('welcome', { '%name%': user.name }) }}</p>
```

## Optional: Automatic Language Detection
If you're using the `artisanfw/api` package, you can an add the middleware `Artisan\Middleware\Locale` to your Bootstrap settings.

```php
$apiManager->addMiddleware(new \Artisan\Middlewares\Locale());
```

This middleware attempts to determine the appropriate locale based on the following order:
1. `lang` query parameter
2. `Accepted-Language` header
3. default language configured in `Language::load()`

## Variable Wrapping Configuration
To simplify variable usage, you may omit the traditional wrapper characters in translation placeholders:
```yaml
welcome: "Welcome, {name}!"
```
```php
echo Language::i()->trans('welcome', ['name' => 'Airam']);
```
```twig
<p>{{ t('welcome', { name: 'Airam' }) }}</p>
```
You can configure the placeholder wrapper style using the `wrapper` option:
```php
$conf = [
    ...
    'wrapper' => \Artisan\Services\Language::WRAPPER_CURLY_BRACES,
    ...
];
```
### Options
* `WRAPPER_CURLY_BRACES` : ICU format
* `WRAPPER_PERCENT_SIGN` : Legacy format

## ICU Formatting Support
To use ICU formatting features (such as pluralization), ensure the `intl` PHP extension is installed:
```bash
sudo apt-get install php-intl
```

Translation domains must include the `+intl-icu` suffix to enable ICU support:

```
messages+intl-icu.en.yaml
messages+intl-icu.en.json
```
Also, define the default domain accordingly in the configuration:
```php
$config = [
    // ... previous configuration
    
    'default_domain' => 'messages+intl-icu'
]
```

> **Important:** ICU-formatted variables must be enclosed in curly braces:
```yaml
welcome: "Welcome, {name}!"
```

### Pluralization Example
```yaml
hour: >-
   {n, plural,
       =1    {hour}
       other {hours}
   }
```
```php
echo Language::i()->trans('hour', ['n' => 1]);
// Output: "hour"
echo Language::i()->trans('hour', ['n' => 2]);
// Output: "hours"
```
You may also use pluralization for other logic, such as selecting the name of a day:
```yaml
day_name: >-
   {n, plural,
      =1    {Monday}
      =2    {Tuesday}
      =3    {Wednesday}
      =4    {Thursday}
      =5    {Friday}
      =6    {Saturday}
      =7    {Sunday}
      other {unknown day}
   }
```

For detailed reference on ICU formatting and message patterns, please refer to:
* [ICU Documentation]( https://unicode-org.github.io/icu/ )
* [Symfony Translate package](https://symfony.com/doc/current/reference/formats/message_format.html)*

> **Note:** The Symfony documentation is primarily tailored to the Symfony Framework. Some concepts may need to be abstracted to integrate them properly within the Artisan Framework. However, it is generally more accessible than the official ICU documentation

