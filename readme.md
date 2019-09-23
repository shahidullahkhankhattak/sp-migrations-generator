# Sps & Functions migrations generator for laravel

[![N|Solid](https://shahidullahkhan.com/images/powered.png)](https://shahidullahkhan.com)

[![Build Status](https://shahidullahkhan.com/images/passing.svg)](https://travis-ci.org/joemccann/dillinger)

##### This is a laravel package that generates migrations for sps and functions from your database through artisan commands.

  - Generates migrations for SP
  - Generates migrations for Functions

## Installation

This migration generator requires [Laravel](https://laravel.com/) to work.

require this package through composer using following command

```sh
$ composer require shahid/sp-migrations-generator
```
Next, add the following service providers:
```
Shahid\SpMigrationsGenerator\SpMigrationsGeneratorServiceProvider::class,
```
## Usage
Run below command to generate migrations.
```sh
$ php artisan spmigration:generate
```
Or Run below command to ignore some table migrations.
```sh
$ php artisan spmigration:inserttables
```
