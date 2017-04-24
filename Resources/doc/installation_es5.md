# Installation

## Install ElasticSearch

The sulu article bundle requires a running elasticsearch `^2.2` or `^5.0`.

## Install bundle over composer:

```bash
composer require sulu/article-bundle
```

**Add bundle to AbstractKernel:**

```php
/* app/AbstractKernel.php */

new Sulu\Bundle\ArticleBundle\SuluArticleBundle(),
new ONGR\ElasticsearchBundle\ONGRElasticsearchBundle(),
```

## Configure the bundles:

```yml
# app/config/config.yml

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
    managers:
        default:
            index: 
                index_name: su_articles
            mappings:
                - SuluArticleBundle
        live:
            index:
                index_name: su_articles_live
            mappings:
                - SuluArticleBundle
```
