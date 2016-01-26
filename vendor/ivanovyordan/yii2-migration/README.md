# Yii 2 Dependencies Migration #

This is a small [Composer](https://getcomposer.org/) module that helps you reuse your [Yii 2](http://www.yiiframework.com/) modules.

## Installation ##

Either run the following command in your project root:

```sh
php composer.phar require --prefer-dist ivanovyordan/yii2-migration "*"
```
or add this to the require section of your `composer.json` file:

```sh
"ivanov-yordan/yii2-migration": "*"
```

## Setup ##

Add the following to two rows to the `scripts` sections in your `composer.json` file:

```
"post-install-cmd": "ivanovyordan\\migration\\Migration::migrate",
"post-update-cmd": "ivanovyordan\\migration\\Migration::migrate"
```

## Usage ##

1. Create packages for all your modules you want to reuse.
2. Create a `migrations` folder for modules that requires migrations.
3. Add your migrations in the `migration` directory.

Following these three steps will give you the chance to reuse and redistribute and update your modules without the need to manually do your migrations.
