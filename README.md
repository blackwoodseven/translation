## Installation

To install the BlackwoodSeven translation library in your project using Composer:

```composer require blackwoodseven/translation```

## Configuration

```php

$app->register(new \Silex\Provider\TranslationServiceProvider(), [
    'locale_fallbacks' => ['en'],
    'locale' => 'en',
]);

$app->register(new \BlackwoodSeven\Translation\TranslationServiceProvider(), [
    'translation.path' => __DIR__ . '/locale',
    'translation.contexts' => ['mycontext1', 'mycontext2'],
    'translation.locales' => ['en', 'de'],
]);
```
## Usage

```php
$app['formatter.date']($date, 'my_date_format');
```

```
{% mydate|formatDate('my_date_format') %}
```
