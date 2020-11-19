# Installation

### ElasticSearch

The SuluArticleBundle requires a running elasticsearch `^5.0`, `^6.0` or `^7.0`.

## Install dependencies

```bash
composer require "elasticsearch/elasticsearch:7.9.*" # should match version of your elasticsearch installation
composer require sulu/article-bundle
```

### Configure SuluArticleBundle and sulu core

```yml
# config/packages/sulu_article.yaml

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

sulu_article:
    index_name: su_articles
    hosts: ['127.0.0.1:9200']

# config/packages/ongr_elasticsearch.yaml
ongr_elasticsearch:
    analysis:
        tokenizer:
            pathTokenizer:
                type: path_hierarchy
        analyzer:
            pathAnalyzer:
                tokenizer: pathTokenizer
```

### Configure the routing

```yml
# config/routes/sulu_admin.yaml

sulu_article_api:
    resource: "@SuluArticleBundle/Resources/config/routing_api.yml"
    type: rest
    prefix: /admin/api
```

### Configure multi webspace setup

Simple configuration:

```yml
# config/packages/sulu_article.yaml

sulu_article:
    default_main_webspace: 'webspace1'
    default_additional_webspaces:
        - 'webspace2'
        - 'webspace3'
```

Localized configuration:

```yml
# config/packages/sulu_article.yaml

sulu_article:
    default_main_webspace: 
        de: 'webspaceA'
        en: 'webspaceX'
    default_additional_webspaces:
        de:
            - 'webspaceN'
            - 'webspaceM'
        en:
            - 'webspaceN'
```

Localized configuration with a defined default:

```yml
# config/packages/sulu_article.yaml

sulu_article:
    default_main_webspace: 
        default: 'webspaceA'
        en: 'webspaceX'
        fr: 'webspaceF'
    default_additional_webspaces:
        default:
            - 'webspaceB'
            - 'webspaceC'
        de:
            - 'webspaceN'
            - 'webspaceM'
        en:
            - 'webspaceN'
```

More information about this topic can be found in the section [multi-webspaces](multi-webspaces.md).

## Create Template

Add xml template for structure in configured folder:

```
%kernel.project_dir%/config/templates/articles/default.xml
```

Example is located in Bundle
[default.xml](default.xml).

Add template for article type in configured folder:

```
%kernel.project_dir%/templates/articles/default.html.twig
```

Example is located in Bundle
[default.html.twig](default.html.twig).

## Initialize bundle

Create required phpcr nodes:

```bash
php bin/console sulu:document:init
```

Create elasticsearch index:

```bash
php bin/console ongr:es:index:create
php bin/console ongr:es:index:create --manager=live
```

## Permissions:

Make sure you've set the correct permissions in the Sulu backend for this bundle!

`Settings > User Roles`

## Possible bundle configurations:

```yml
# config/packages/sulu_article.yaml

sulu_article:
    index_name: su_articles
    hosts: ['127.0.0.1:9200']
    default_main_webspace: null
    default_additional_webspaces: []
    smart_content:
        default_limit:        100
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

## Troubleshooting

### Add bundles to AbstractKernel

The bundle need to be registered after the `SuluCoreBundle` and `SuluDocumentManagerBundle`. This should be done
automatically by Symfony Flex, if that fails for some reason you have to do it manually:

```php		
/* config/bundles.php */
       	
Sulu\Bundle\ArticleBundle\SuluArticleBundle::class => ['all' => true],
ONGR\ElasticsearchBundle\ONGRElasticsearchBundle::class => ['all' => true],
```
