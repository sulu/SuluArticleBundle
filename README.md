# SuluArticleBundle

## Installation

Install bundle over composer:

```bash
composer require sulu/article-bundle
```

Create required phpcr nodes:

```bash
app/console sulu:document:init
```

Configure the bundle:

```
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

```
bin/console ongr:es:index:create
```
