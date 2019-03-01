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

    <view>templates/pages/article-overview</view>
    <controller>AppBundle:ArticleOverview:index</controller>
    <cacheLifetime>3600</cacheLifetime>

    <properties>
    </properties>
</template>
```

In this controller the articles can be loaded by using a custom elastic-search query.
See [here](http://docs.ongr.io/ElasticsearchDSL/HowTo/HowToSearch) for more information.

```php
<?php

namespace AppBundle\Controller;

use AppBundle\Document\ArticleViewDocument;
use ONGR\ElasticsearchDSL\Sort\FieldSort;
use Sulu\Bundle\WebsiteBundle\Controller\WebsiteController;
use Sulu\Component\Content\Compat\StructureInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class ArticleOverviewController extends WebsiteController
{
    const PAGE_SIZE = 12;

    public function indexAction(Request $request, StructureInterface $structure, $preview = false, $partial = false)
    {
        $page = $request->get('page', 1);
        if ($page < 1) {
            throw new NotFoundHttpException();
        }

        $articles = $this->loadArticles($structure->getUuid(), $page, self::PAGE_SIZE, $request->getLocale());
        $pages = ceil($articles->count() / self::PAGE_SIZE);

        return $this->renderStructure(
            $structure,
            ['page' => $page + 1, 'pages' => $pages + 1, 'articles' => $articles],
            $preview,
            $partial
        );
    }

    private function loadArticles($page, $pageSize)
    {
        $repository = $this->getRepository();
        $search = $repository->createSearch()
            ->addSort(new FieldSort('authored', FieldSort::DESC))
            ->setFrom(($page - 1) * $pageSize)
            ->setSize($pageSize);

        return $repository->findDocuments($search);
    }

    private function getRepository()
    {
        return $this->get('es.manager.live')->getRepository(ArticleViewDocument::class);
    }
}
```

In the twig template you can use the [ArticleViewDocument](article-view-document.md) to render for example a list of
articles.

```twig
{% extends "master.html.twig" %}

{% block content %}
    <h1 property="title">{{ content.title }}</h1>

    <ul>
        {% for article in articles %}
            <li><a href="{{ sulu_page_path(article.routePath) }}">{{ article.title }}</a></li>
        {% endfor %}
    </ul>

    <nav>
        {% if page > 1 %}
            <a href="?page={{ page - 1 }}">Previous</a>
        {% endif %}

        {% for i in [1..pages] %}
            <a href="?page={{ i }}">{{ i }}</a>
        {% endfor %}

        {% if page < pages %}
            <a href="?page={{ page + 1 }}">Previous</a>
        {% endif %}
    </nav>
{% endblock %}
```

### Page-Tree-Route

As an addition to the example above you can use the [page_tree_route](routing.md#page-tree-integration) to link articles
to a specific page.

This can be handled in th controller by changing the `loadArticles($uuid, $page, $pageSize)`.

```php
private function loadArticles($uuid, $page, $pageSize)
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
