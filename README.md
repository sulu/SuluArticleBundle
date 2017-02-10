# SuluArticleBundle

[![Build Status](https://travis-ci.org/sulu/SuluArticleBundle.svg?branch=master)](https://travis-ci.org/sulu/SuluArticleBundle)
[![StyleCI](https://styleci.io/repos/61883398/shield?branch=develop)](https://styleci.io/repos/61883398)

The SuluArticleBundle adds support for managing articles in Sulu. Articles can be used in a lot of different ways to
manage unstructured data with an own URL in an admin-list. Most of the features, which can be used in pages, can also
be used on articles - like templates, versioning, drafting, publishing and automation.

Additional features included:

* Build in view-layer with elasticsearch
* Segmentation of article-templates (called article-types)
* Define URL schemas per type

## Status

This repository will become version 1.0 of SuluArticleBundle. It is under **heavy development** and currently its APIs
and code are not stable yet (pre 1.0).

## Requirements

* Composer
* PHP `^5.5 || ^7.0`
* Sulu `^1.4`
* Elasticsearch `^2.2`

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
