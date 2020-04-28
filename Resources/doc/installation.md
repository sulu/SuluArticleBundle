# Installation

### ElasticSearch

The SuluArticleBundle requires a running elasticsearch `^2.2` or `^5.0`.

There is an different installation and configuration depending on which version of ElasticSearch you are using.

If you use version `^2.2` read: [Installation for ElasticSearch 2.2](installation-es2.md)
else read: [Installation for ElasticSearch 5.0](installation-es5.md) 

## Install the bundle
 
```bash
composer require sulu/article-bundle
```

### Add bundles to AbstractKernel

The bundle need to be registered after the `SuluCoreBundle` and `SuluDocumentManagerBundle`.

```php
/* app/AbstractKernel.php */

new Sulu\Bundle\ArticleBundle\SuluArticleBundle(),
new ONGR\ElasticsearchBundle\ONGRElasticsearchBundle(),
```

### Configure SuluArticleBundle and sulu core

```yml
# app/config/config.yml

sulu_route:
    mappings:
        Sulu\Bundle\ArticleBundle\Document\ArticleDocument:
            generator: "schema"
            options:
                route_schema: "/articles/{object.getTitle()}"
        Sulu\Bundle\ArticleBundle\Document\ArticlePageDocument:
            generator: "article_page"
            options:
                route_schema: "/{translator.trans(\"page\")}-{object.getPageNumber()}"
                parent: "{object.getParent().getRoutePath()}"

sulu_core:
    content:
        structure:
            default_type:
                article: "article_default"
            paths:
                article:
                    path: "%kernel.root_dir%/Resources/templates/articles"
                    type: "article"
```

### Configure the routing

```yml
# app/config/admin/routing.yml

sulu_article_api:
    resource: "@SuluArticleBundle/Resources/config/routing_api.xml"
    type: rest
    prefix: /admin/api

sulu_article:
    resource: "@SuluArticleBundle/Resources/config/routing.xml"
    prefix: /admin/articles
```

## Create Template

Add xml template for structure in configured folder:

```
%kernel.root_dir%/Resources/templates/articles/default.xml
```

Example is located in Bundle
[default.xml](https://github.com/sulu/SuluArticleBundle/blob/master/Resources/doc/default.xml).

Add template for article type in configured folder:

```
%kernel.root_dir%/Resources/views/articles/default.html.twig
```

Example is located in Bundle
[default.html.twig](https://github.com/sulu/SuluArticleBundle/blob/master/Resources/doc/default.html.twig).

## Initialize bundle

Create assets:

```bash
php bin/console assets:install
```

Create translations:

```bash
php bin/console sulu:translate:export
```

Create required phpcr nodes:

```bash
php bin/console sulu:document:init
```

Create elasticsearch index:

```bash
php bin/console ongr:es:index:create
php bin/console ongr:es:index:create --manager=live
```

## Possible bundle configurations:

```yml
# app/config/config.yml

sulu_article:
    smart_content:
        default_limit:        100
    content_types:
        article:
            template:             'SuluArticleBundle:Template:content-types/article-selection.html.twig'
        page_tree_route:
            template:             'SuluArticleBundle:Template:content-types/page-tree-route.html.twig'
            page_route_cascade:   request # One of "request"; "task"; "off"
    documents:
        article:
            view:                 Sulu\Bundle\ArticleBundle\Document\ArticleViewDocument
        article_page:
            view:                 Sulu\Bundle\ArticleBundle\Document\ArticlePageViewObject
    types:

        # Prototype
        name:
            translation_key:      ~

    # Display tab 'all' in list view
    display_tab_all:      true

    # Set default author if none isset
    default_author:       true
    search_fields:

        # Defaults:
        - title
        - excerpt.title
        - excerpt.description
        - excerpt.seo.title
        - excerpt.seo.description
        - excerpt.seo.keywords
        - teaser_description
```
