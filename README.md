# Doctrine Database Migrations

## Status

[![Build Status](https://travis-ci.org/doctrine/migrations.svg)](https://travis-ci.org/doctrine/migrations)
[![Dependency Status](https://www.versioneye.com/php/doctrine:migrations/badge.svg)](https://www.versioneye.com/php/doctrine:migrations/)
[![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/doctrine/migrations/badges/quality-score.png?b=master)](https://scrutinizer-ci.com/g/doctrine/migrations/?branch=master)
[![Code Coverage](https://scrutinizer-ci.com/g/doctrine/migrations/badges/coverage.png?b=master)](https://scrutinizer-ci.com/g/doctrine/migrations/?branch=master)


## Official Documentation

All available documentation can be found [here](http://docs.doctrine-project.org/projects/doctrine-migrations/en/latest/).

The repository containing the documentation is [there](https://github.com/doctrine/migrations-documentation).

## Working with Doctrine Migrations

### Using the integration of your framework

  * [Symfony](https://packagist.org/packages/doctrine/doctrine-migrations-bundle)
  * [ZF2](https://packagist.org/packages/doctrine/doctrine-orm-module)
  * [laravel](https://packagist.org/packages/laravel-doctrine/migrations)
  * [Silex](https://packagist.org/packages/kurl/silex-doctrine-migrations-provider)
  * [Silex](https://packagist.org/packages/dbtlr/silex-doctrine-migrations)
  * [nette](https://packagist.org/packages/zenify/doctrine-migrations)
  * others...

### Using composer

```composer require doctrine/migrations```

### Downloading the latest phar release

You can download the [doctrine migrations phar](https://github.com/doctrine/migrations/releases) directly on the release page

### Building Your own Phar

Make sure Composer and all necessary dependencies are installed:

```bash
curl -s https://getcomposer.org/installer | php
php composer.phar install
```

Make sure that the Box project is installed:

```bash
curl -LSs http://box-project.github.io/box2/installer.php | php
```

Build the PHAR archive:

```bash
php box.phar build
```

The `doctrine-migrations.phar` archive is built in the `build` directory.

#### Creating archive disabled by INI setting

If you receive an error that looks like:

    creating archive "build/doctrine-migrations.phar" disabled by INI setting

This can be fixed by setting the following in your php.ini:

```ini
; http://php.net/phar.readonly
phar.readonly = Off
```

## Installing Dependencies

To install dependencies run a composer update:

```composer update```

## symfony 2.3 users

Doctrine migration need the doctrine/orm 2.4, you need to [update your composer.json](https://github.com/symfony/symfony-standard/blob/v2.3.28/composer.json#L12) to the last version of it for symfony 2.3.

That version is compatible with the doctrine/orm 2.4 and there are [very little upgrade needed](https://github.com/doctrine/doctrine2/blob/master/UPGRADE.md#upgrade-to-24).

## Running the unit tests

To run the tests, you need the sqlite extension for php.
On Unix-like systems, install:
- php5-sqlite

On Windows, enable the extension by uncommenting the following lines in php.ini
```
extension = php_pdo_sqlite.dll
extension = php_sqlite3.dll
extension_dir = ext
```

Running the tests from the project root:
```
./vendor/bin/phpunit
```

On Windows run phpunit from the full path
```
php vendor/phpunit/phpunit/phpunit
```
This appears to be some bug.

Happy testing :-)

## Prestashop Specific Methods

You can generate a migration with specifics methods for Prestashop by running `./bin/cli migrations:generate --prestashop`

### Methods with Prestashop Migration

All tab's and accesses relative method, except `getTabId` and `getParentTabId`, can chained.

```php
$this->addTab(1, 'TabClass', 'Tab name')->setTabAfter('TabClass', 'OtherTabClass');
```

#### Access

* `self` addAccess( `interger` $id_tab )
* `self` deleteAccess( `interger` $id_tab )

#### Config

* `void` addConfig( `string` $name [ , `string`|`array` $value = null [ , `integer` $id_shop_group = null [ , `integer` $id_shop = null ] ] ] )
* `void` deleteConfig( `string` $name )
* `integer` getConfigId( `string` $name )
* `string` getConfigValue( `string` $name , [ , `integer` $id_shop = null ] )
* `void` setConfig( `string` $name, `string` $value [ , `integer` $id_shop_group = null [ , `integer` $id_shop = null ] ] )
* `void` updateConfig( `string` $name, `string` $value [ , `integer` $id_shop_group = null [ , `integer` $id_shop = null ] ] )
* `void` updateConfigLang( `string` $name, `string` $value , `string` $iso [ , `integer` $id_shop_group = null [ , `integer` $id_shop = null ] ] )

#### DbTools

* `self` alterTable( `string` $table, `array` $rules )
* `self` dropTable( `string` $table )
* `self` updateTable( `string` $controller, `array` $rules [ , `array` $conditions = array() ] )

#### ImageFormat

* `self` addImageFormat( `string` $family, [ , `array` $formats = array() [ , `integer` $id_shop_group = false ] ] )
* `self` deleteImageFormat( `string` $family, [ , `string` $pattern = false [ , `integer` $id_shop_group = false ] ] )

#### Meta

* `void` addMeta( `string` $name [ , `array` $langs = array() ] )
* `void` deleteMeta( `string` $name )

#### Module

* `self` activateModule( `string` $name)
* `self` addModule( `string` $name, `string` $version [ , `bool` $active = true [ , `array` $access = array() ] ] )
* `self` addModuleAccess ( `interger` $id_module [ , `array` $access = array() ] )
* `self` deactivateModule( `string` $name)
* `self` deleteModule( `string` $name)
* `self` deleteModuleAccess ( `interger` $id_module )
* `integer` getModuleId( `string` $name)

#### Tab

* `self` addTab( `interger` $id_tab_parent, `string` $controller, `string` $name )
* `self` changeParent( `string` $controller, `interger` $id_tab_parent )
* `self` deleteTab( `string` $controller )
* `integer` getTabId( `string` $controller )
* `integer` getParentTabId( `string` $controller )
* `self` setTabAfter( `string` $controller, `string` $target )
* `self` setTabBefore( `string` $controller, `string` $target )
* `self` reorderTabs( `interger` $id_tab_parent )

`deleteTab`, `setTabAfter` and `setTabBefore` automaticaly trigger `reorderTabs` method.

#### Shop

* `array` getAllShops( `bool` $active)
* `array` getShopById( `interger` $id_shop)
