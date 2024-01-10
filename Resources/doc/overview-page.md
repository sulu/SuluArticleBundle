# Overview Page

This bundle provides multiple ways to create a overview page.

## Content Types

The first possibility to create a overview page is to use the build in functionality of Sulu.

* Manually: Article-Selection (see [Content-Types](content-types.md#article-selection))
* Automatic: Smart-Content with article provider (see [Content-Types](content-types.md#smart-content))

Both can be influenced by the content-manager (selection of articles or configuring the filter).

## Custom Controller

Another way is to create a custom controller.

```xml
<?xml version="1.0" ?>
<template xmlns="http://schemas.sulu.io/template/template"
          xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
          xsi:schemaLocation="http://schemas.sulu.io/template/template http://schemas.sulu.io/template/template-1.0.xsd">

    <key>article-overview</key>

    <view>pages/article-overview</view>
    <controller>App\Controller\Website\ArticleOverviewController::indexAction</controller>
    <cacheLifetime>86400</cacheLifetime>

    <properties>
        <property name="title" type="text_line" mandatory="true">
            <meta>
                <title lang="en">Title</title>
                <title lang="de">Titel</title>
            </meta>
            <params>
                <param name="headline" value="true"/>
            </params>

            <tag name="sulu.rlp.part"/>
        </property>

        <property name="url" type="resource_locator" mandatory="true">
            <meta>
                <title lang="en">Resourcelocator</title>
                <title lang="de">Adresse</title>
            </meta>

            <tag name="sulu.rlp"/>
        </property>
    </properties>
</template>
```

In this controller the articles can be loaded by using a custom elastic-search query.
See [here](http://docs.ongr.io/ElasticsearchDSL/HowTo/HowToSearch) for more information.

```php
<?php

namespace App\Controller\Website;

use ONGR\ElasticsearchBundle\Service\Manager;
use ONGR\ElasticsearchBundle\Service\Repository;
use ONGR\ElasticsearchDSL\Sort\FieldSort;
use Sulu\Bundle\ArticleBundle\Document\ArticleViewDocument;
use Sulu\Bundle\WebsiteBundle\Controller\WebsiteController;
use Sulu\Component\Content\Compat\StructureInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class ArticleOverviewController extends WebsiteController
{
    const PAGE_SIZE = 12;

    /**
     * @var Manager
     */
    private $esManagerLive;

    public function __construct(Manager $esManagerLive)
    {
        $this->esManagerLive = $esManagerLive;
    }

    public function indexAction(Request $request, StructureInterface $structure, $preview = false, $partial = false)
    {
        $page = $request->query->getInt('page', 1);
        if ($page < 1) {
            throw new NotFoundHttpException();
        }

        $articles = $this->loadArticles($page, self::PAGE_SIZE, $request->getLocale());

        $pages = (int) ceil($articles->count() / self::PAGE_SIZE) ?: 1;

        return $this->renderStructure(
            $structure,
            [
                'page' => $page,
                'pages' => $pages,
                'articles' => $articles
            ],
            $preview,
            $partial
        );
    }

    private function loadArticles($page, $pageSize, $locale)
    {
        $repository = $this->getRepository();
        $search = $repository->createSearch()
            ->addSort(new FieldSort('authored', FieldSort::DESC))
            ->setFrom(($page - 1) * $pageSize)
            ->setSize($pageSize)
            ->addQuery(new TermQuery('locale', $locale));

        return $repository->findDocuments($search);
    }

    /**
     * @return Repository
     */
    private function getRepository()
    {
        return $this->esManagerLive->getRepository(ArticleViewDocument::class);
    }
}
```

That the `$esManagerLive` is correctly set you need to add the following to your `config/services.yaml`

```yaml
services:
    # default configuration for services in *this* file
    _defaults:
        autowire: true      # Automatically injects dependencies in your services.
        autoconfigure: true # Automatically registers your services as commands, event subscribers, etc.
        bind:
            $esManagerLive: '@es.manager.live'
```

In the twig template you can use the [ArticleViewDocument](article-view-document.md) to render for example a list of
articles.

```twig
{% extends "base.html.twig" %}

{% block content %}
    <h1>{{ content.title }}</h1>

    <ul>
        {% for article in articles %}
            <li><a href="{{ sulu_content_path(article.routePath) }}">{{ article.title }}</a></li>
        {% endfor %}
    </ul>

    <nav aria-label="pagination">
        {% if page > 1 %}
            <a href="?page={{ page - 1 }}">Previous</a>
        {% endif %}

        {% for i in 1..pages %}
            <a href="?page={{ i }}">{{ i }}</a>
        {% endfor %}

        {% if page < pages %}
            <a href="?page={{ page + 1 }}">Next</a>
        {% endif %}
    </nav>
{% endblock %}
```

### Page-Tree-Route

As an addition to the example above you can use the [page_tree_route](routing.md#page-tree-integration) to link articles
to a specific page.

This can be handled in th controller by changing the `loadArticles($page, $pageSize, $structure->getUuid()`.

```php
private function loadArticles($page, $pageSize, $uuid)
{
    $repository = $this->getRepository();
    $search = $repository->createSearch()
        ->addSort(new FieldSort('authored', FieldSort::DESC))
        ->setFrom(($page - 1) * $pageSize)
        ->setSize($pageSize)
        ->addQuery(new TermQuery('parent_page_uuid', $uuid));

    return $repository->findDocuments($search);
}
```

The rest of the code can be reused.
