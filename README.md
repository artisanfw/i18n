# i18n
A simple wrapper around Symfony Translation that supports multi-language output for APIs or email templates.

## Install
```bash
composer require artisanfw/i18n
```

## Configuration and Usage
### Load the Service
```php
Language::load([
    'locale' => 'en',                       // Default language
    'path' => PROJECT_DIR . '/locales',       // Path to translation files
    'file_format' => \Artisan\Services\Language::YAML_FORMAT,
]);
```
Translation files must follow the pattern: `en.yaml`, `es.yaml`, etc.

**Recommended:** ICU file pattern `en.intl.yaml`, etc. "intl" enables ICU message formatting with pluralization. But it uses `{single mustache}` instead `%percent%` variables


Supported formats:
* `Language::YAML_FORMAT`
* `Language::JSON_FORMAT`

#### Examples
`locales/en.yaml`
```yaml
welcome: "Welcome, %name%!"
```
`locales/es.yaml`
```yaml
welcome: "¡Bienvenido, %name%!"
```

## Translate a Message
```php
echo Language::i()->trans('welcome', ['%name%' => 'Airam']);
// Output: "Welcome, Airam!"
```
### Override the Locale
```php
echo Language::i()->trans('welcome', ['%name%' => 'Airam'], null, 'es');
// Output: "¡Bienvenido, Airam!"
```
## Optional: Allow translation in Twig templates
If you're using Twig (e.g., for rendering emails), you can register the translation function:
```php
$twig->addFunction(Language::getTwigFunction());
```
> **Note:** This requires `Twig` to be installed. If it's not installed, the function won't be available.

If you're using `artisanfw/twig`, simply include `Language::getTwigFunction()` in the Twig config under functions.

## Translation in a Twig template:
```twig
<p>{{ t('welcome', { '%name%': user.name }) }}</p>
```

## Optional: Language detection
If you're using `artisanfw/api`, you can an add the middleware `Artisan\Middleware\Locale` to your Bootstrap settings.

```php
$apiManager->addPreprocessor(new \Artisan\Middlewares\Locale());
```

The middleware will try to set the current language looking for the following:
* lang query parameter
* Accepted-Language header
* default language configured in `Language::load()`

## ICU formatting
For use pluralization and other ICU features, you must use the `intl` file format:

```
en.intl.yaml
```
**Remember:** The variables in the ICU format must be wrapped in curly braces:
```yaml
welcome: "Welcome, {name}!"
```

### How to pluralize
A quickly example:
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
You can also use the `n` variable in other ways, ie: getting the name of the day:
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

## Variable management
With the Language service you can avoid the wrapper characters in the params:
```yaml
welcome: "Welcome, {name}!"
```
```php
echo Language::i()->trans('welcome', ['name' => 'Airam']);
```
```twig
<p>{{ t('welcome', { name: 'Airam' }) }}</p>
```

