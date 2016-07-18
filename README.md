# SuluArticleBundle

## Installation

Install bundle over composer:

```bash
composer require sulu/article-bundle
```

Add bundle to AbstractKernel:

```php
new Sulu\Bundle\ArticleBundle\SuluArticleBundle(),
new ONGR\ElasticsearchBundle\ONGRElasticsearchBundle(),
```

Create required phpcr nodes:

```bash
app/console sulu:document:init
```

Configure the bundle:

```yml
sulu_route:
    mappings:
        Sulu\Bundle\ArticleBundle\Document\ArticleDocument:
            route_schema: /articles/{object.getTitle()}

ongr_elasticsearch:
    connections:
        default:
            index_name: su_articles
    managers:
        default:
            connection: default
            mappings:
                - SuluArticleBundle
```

Create elasticsearch index:

```bash
bin/console ongr:es:index:create
```
