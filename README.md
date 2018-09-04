[![CircleCI](https://circleci.com/gh/mundipagg/opencart.svg?style=svg)](https://circleci.com/gh/mundipagg/opencart)
[![Codacy Badge](https://api.codacy.com/project/badge/Grade/9d08ac419d81479696812fc4baa56c7d)](https://www.codacy.com/app/mundipagg/opencart?utm_source=github.com&amp;utm_medium=referral&amp;utm_content=mundipagg/opencart&amp;utm_campaign=Badge_Grade)
[![Maintainability](https://api.codeclimate.com/v1/badges/6b6afaf79c86cdf5487e/maintainability)](https://codeclimate.com/github/mundipagg/opencart/maintainability)

# Opencart/Mundipagg Integration module
This is the official Opencart module for Mundipagg integration

# Documentation
Refer to [module documentation](https://github.com/mundipagg/opencart/wiki)

# Compatibility
This module supports OpenCart version 3.0+

# Dependencies
* ```PHP``` Version 5.6+

# Install
There are two different ways to install our module

## Using modman
Modman is a project which helps developers to centralize extension code when
the environment forces you to mix your code with the core files. For more
information, refer to [modman](https://github.com/colinmollenhour/modman).

```bash
modman init
modman clone https://github.com/mundipagg/opencart
```

## OpenCart Installer
Download the Mundipagg.ocmod.zip file from last module [release](https://github.com/mundipagg/opencart/releases) and paste the upload folder content into your OpenCart root folder.

# Development

## Up workplace
```bash
git clone https://github.com/mundipagg/opencart
cd opencart
cp .env.example .env
composer install -vvv --prefer-dist --dev
vendor/bin/robo opencart:setup
```
## Run tests
First, up workplace.
```bash
composer test
```
# Generate package

## Bump version

```
vendor/bin/robo opencart:bump <new_version>
```

## Packing

Run the follow command replacing `version_of_package` by new version if you need
bump version. If you don't need bump version, don't pass the version to command

```
vendor/bin/robo opencart:pack [version_of_package]
```

# How can I contribute?
Please, refer to [CONTRIBUTING](.github/CONTRIBUTING.md)

# Found something strange or need a new feature?
Open a new Issue following our issue template [ISSUE_TEMPLATE](.github/ISSUE_TEMPLATE.md)

# Changelog
See in [releases](https://github.com/mundipagg/opencart/releases)

