# SuluArticleBundle

![Test application](https://github.com/sulu/SuluArticleBundle/workflows/Test%20application/badge.svg?branch=1.2)
[![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/sulu/SuluArticleBundle/badges/quality-score.png?b=1.2)](https://scrutinizer-ci.com/g/sulu/SuluArticleBundle/?branch=1.2)
[![Code Coverage](https://scrutinizer-ci.com/g/sulu/SuluArticleBundle/badges/coverage.png?b=1.2)](https://scrutinizer-ci.com/g/sulu/SuluArticleBundle/?branch=1.2)
[![CircleCI](https://circleci.com/gh/sulu/SuluArticleBundle/tree/1.2.svg?style=svg)](https://circleci.com/gh/circleci/circleci-docs/tree/1.2)

The SuluArticleBundle adds support for managing articles in Sulu. Articles can be used in a lot of different ways to
manage unstructured data with an own URL in an admin-list. Most of the features, which can be used in pages, can also
be used on articles - like templates, versioning, drafting, publishing and automation.

Additional features included:

* Build in view-layer with elasticsearch
* Segmentation of article-templates (called article-types)
* Define URL schemas per type

## Requirements

* Composer
* PHP `^5.5 || ^7.0`
* Sulu `^1.6`
* Elasticsearch `^5.0 || ^6.0 || ^7.0`

For detailed requirements see [composer.json](https://github.com/sulu/SuluArticleBundle/blob/master/composer.json).

## Documentation

The the Documentation is stored in the
[Resources/doc/](https://github.com/sulu/SuluArticleBundle/blob/master/Resources/doc) folder.

## Installation

All the installation instructions are located in the 
[Documentation](https://github.com/sulu/SuluArticleBundle/blob/master/Resources/doc/installation.md).

## License

This bundle is under the MIT license. See the complete license [in the bundle](LICENSE)

## Reporting an issue or a feature request

Issues and feature requests are tracked in the [Github issue tracker](https://github.com/Sulu/SuluArticleBundle/issues).

When reporting a bug, it may be a good idea to reproduce it in a basic project built using the
[Sulu Minimal Edition](https://github.com/sulu/sulu-minimal) to allow developers of the bundle to reproduce the issue
by simply cloning it and following some steps.
