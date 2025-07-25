# BaksDev Product Viewed

[![Version](https://img.shields.io/badge/version-7.2.17-blue)](https://github.com/baks-dev/products-viewed/releases)
![php 8.4+](https://img.shields.io/badge/php-min%208.4-red.svg)
[![packagist](https://img.shields.io/badge/packagist-green)](https://packagist.org/packages/baks-dev/products-viewed)

Модуль просмотров продукции

## Установка

``` bash
composer require baks-dev/products-viewed
```

## Применение

``` twig
{{ render_products_viewed(invariable_id || null) }}
```

## Дополнительно

Установка конфигурации и файловых ресурсов:

``` bash
php bin/console baks:assets:install
```

Изменения в схеме базы данных с помощью миграции

``` bash
php bin/console doctrine:migrations:diff
php bin/console doctrine:migrations:migrate
```

## Тестирование

``` bash
php bin/phpunit --group=products-viewed
```

## Лицензия ![License](https://img.shields.io/badge/MIT-green)

The MIT License (MIT). Обратитесь к [Файлу лицензии](LICENSE.md) за дополнительной информацией.
