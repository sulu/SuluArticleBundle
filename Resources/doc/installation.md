# Installation

## Install ElasticSearch

The sulu article bundle requires a running elasticsearch `^2.2` or `^5.0`.

There is an different installation and configuration depending on which version of ElasticSearch you are using.

If you use version `^2.2` read: [Installation for ElasticSearch 2.2](installation_es2.md)
else read: [Installation for ElasticSearch 5.0](installation_es5.md) 


## Configure the routing

```yml
# app/config/admin/routing.yml

sulu_arictle_api:
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
%kernel.root_dir%/Resources/templates/articles/article_default.xml
```

Example is located in Bundle
[article_default.xml](https://github.com/sulu/SuluArticleBundle/blob/master/Resources/doc/article_default.xml).

Add template for article type in configured folder:

```
%kernel.root_dir%/Resources/views/articles/article_default.html.twig
```

Example is located in Bundle
[article_default.html.twig](https://github.com/sulu/SuluArticleBundle/blob/master/Resources/doc/article_default.html.twig).

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


