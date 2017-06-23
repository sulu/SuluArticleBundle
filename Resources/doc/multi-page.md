# Multi-Page

This bundle provides the optional feature "multi-page". It enables the content-manager to split the content of an
article into smaller pieces (here called pages). Each page has the same xml-template, twig-template and
so the same content-structure.

## Configuration

Enable the feature in your template.

```xml
<?xml version="1.0" ?>
<template xmlns="http://schemas.sulu.io/template/template"
          xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
          xmlns:xi="http://www.w3.org/2001/XInclude"
          xsi:schemaLocation="http://schemas.sulu.io/template/template http://schemas.sulu.io/template/template-1.0.xsd">

    <key>article</key>
    
    ...

    <tag name="sulu_article.multi_page" enabled="true"/>

    <properties>
        ...
    </properties>
</template>
```

## Routing

The route of the first page will be generated like other article-urls (see [Routing](routing.md)). For the following
pages the route of the first page will be used and a postfix will be added (example `/page/2`).

To change the default postfix use following configuration in your `app/config/config.yml`:

```yml
sulu_route:
    mappings:
        Sulu\Bundle\ArticleBundle\Document\ArticlePageDocument:
            generator: article_page
            options:
                route_schema: '/{translator.trans("page")}-{object.getPageNumber()}'
                parent: '{object.getParent().getRoutePath()}'
```

## Pagination

In the twig template you can use the array-variable `pages` to render a pagination:

```twig
<ul class="pagination">
    {% for page in pages %}
        <li>
            <a href="sulu_content_path(page.routePath)">{{ page.pageNumber }}</a>
        </li>
    {% endfor %}
</ul>
```
