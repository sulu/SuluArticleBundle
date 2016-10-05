# SuluArticleBundle

## Installation

Install ElasticSearch

Install bundle over composer:

```bash
composer require sulu/article-bundle
```

Possible bundle configurations:

```yml
sulu_article:
    documents:
        article:
            view: Sulu\Bundle\ArticleBundle\Document\ArticleViewDocument
    types:

        # Prototype
        name:
            translation_key:      ~

    # Display tab 'all' in list view
    display_tab_all:      true
```

Configure the bundles:

```yml
sulu_route:
    mappings:
        Sulu\Bundle\ArticleBundle\Document\ArticleDocument:
            generator: schema
            options:
                route_schema: /articles/{object.getTitle()}

sulu_core:
    content:
        structure:
            default_type:
                article: "article_default"
            paths:
                article:
                    path: "%kernel.root_dir%/Resources/templates/articles"
                    type: "article"
                    
ongr_elasticsearch:
    connections:
        default:
            index_name: su_articles
        live:
            index_name: su_articles_live
    managers:
        default:
            connection: default
            mappings:
                - SuluArticleBundle
        live:
            connection: live
            mappings:
                - SuluArticleBundle
```

Add xml template for structure in configured folder:

```
%kernel.root_dir%/Resources/templates/articles/article_default.xml
```

Example is located in Bundle:

```
Resources/doc/article_default.xml
```

Add template for article type in configured folder:

```
%kernel.root_dir%/Resources/views/articles/article_default.html.twig
```

Example is located in Bundle:

```
Resources/doc/article_default.html.twig
```

Configure the routing

```yml
sulu_arictle_api:
    resource: "@SuluArticleBundle/Resources/config/routing_api.xml"
    type: rest
    prefix: /admin/api

sulu_article:
    resource: "@SuluArticleBundle/Resources/config/routing.xml"
    prefix: /admin/articles
```

Add bundle to AbstractKernel:

```php
new Sulu\Bundle\ArticleBundle\SuluArticleBundle(),
new ONGR\ElasticsearchBundle\ONGRElasticsearchBundle(),
```

Create required phpcr nodes:

```bash
bin/console sulu:document:init
```

Create elasticsearch index:

```bash
bin/console ongr:es:index:create
```
