# SuluArticleBundle

[![Build Status](https://travis-ci.org/sulu/SuluArticleBundle.svg)](https://travis-ci.org/sulu/SuluArticleBundle)
[![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/sulu/SuluArticleBundle/badges/quality-score.png)](https://scrutinizer-ci.com/g/sulu/SuluArticleBundle/)
[![Code Coverage](https://scrutinizer-ci.com/g/sulu/SuluArticleBundle/badges/coverage.png)](https://scrutinizer-ci.com/g/sulu/SuluArticleBundle/)
[![StyleCI](https://styleci.io/repos/61883398/shield)](https://styleci.io/repos/61883398)

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
* Elasticsearch `^2.2 || ^5.0`

For detailed requirements see [composer.json](https://github.com/sulu/SuluArticleBundle/blob/master/composer.json).

## Conflicts

Our dependency `ongr/elasticsearch-bundle`
(see [ongr-io/ElasticsearchBundle#832](https://github.com/ongr-io/ElasticsearchBundle/issues/832) and 
[ongr-io/ElasticsearchBundle#828](https://github.com/ongr-io/ElasticsearchBundle/issues/828)) isn't supporting
Elasticsearch `^6.0`.

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
