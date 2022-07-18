# Routing

The articles uses the routing-system of sulu. This enables entities (in this case the article)
to manage the routes centrally in the database. This routes will be used to determine which
Controller will be called during the request.

This controller can be configured in the template file of the article. This xml-file contains
an item which is called `<controller>` and it contains the controller name (also services are
possible) and the action (or function) name.

The arguments for the controller are:

* `ArticleInterface $object`: The article itself.
* `string $view`: Contains the value of the `<view>` node from the template.xml file.
* `int $pageNumber`: The page-number is used to pass tell the controller which page of the
  article should be rendered.
* `int _cacheLifetime`: Contains the resolved value of `<cacheLifetime>` node from the
  template.xml file.

Additionally the `Request $request` can also be used in the controller.

## Route Schema

The `route_schema` which will be used to generate a route for a newly created article can be
defined in the configuration.

```yml
sulu_route:
    mappings:
        Sulu\Bundle\ArticleBundle\Document\ArticleDocument:
            generator: schema
            options:
                route_schema: '/articles/{implode("-", object)}'
```

This schema will be used for all articles which will be created in the future. Older articles
will not be touched.

### Overwrite route schema in template

You can use a different `route_schema` for articles with a specific template by setting the 
`route_schema` param of the `routePath` property in the template:

```xml
<property name="routePath" type="route">
    <meta>
        <title lang="en">Resourcelocator</title>
        <title lang="de">Adresse</title>
    </meta>

    <params>
        <param name="route_schema" value="/template-specific-prefix/{implode('-', object)}"/>
    </params>

    <tag name="sulu_article.article_route"/>
</property>
```

## Route Generation

For the route generation this bundle provides two different ways and two different approaches.

### Route Schema

This method is default for SuluArticleBundle. It generates the route by a custom schema which is
already configured in the installation process (see [installation](installation.md)).

To use this approach you have to do nothing (if you followed the installation description). For
the completeness you have to be aware that following steps are done:
 
* The `routePath` property uses the `route` content type
* The `sulu_route` configuration includes a `route_schema` for the `ArticleDocument`

### Page tree integration

The other approach is based on the page-tree. You can choose a page which is used as the parent
route to generate the routes for the article (e.g. `/page/article`). Each article can have its
own parent-page and can so be coupled to the page.

To use this approach you have to change the content-type of the property `routePath` to
`page_tree_route`. This will change the UI for this property in the Sulu-Admin and behind the 
scenes the routes will be handled in a different way.  

```xml
<property name="routePath" type="page_tree_route">
    <meta>
        <title lang="en">Resourcelocator</title>
        <title lang="de">Adresse</title>
    </meta>
</property>
```

You still can define the rear part generation of the url like descripted above.

**Route update**

When changing a url of a page the system also update the routes of all the linked articles.
By default this behaviour will be called immediately on publishing the page. This could consume
a lot of time when lots of articles are linked to the saved page.

To omit this the bundle provides a way to asynchronously call this route-update via the 
SuluAutomationBundle. To enable it you have to install the
[SuluAutomationBundle](https://github.com/sulu/SuluAutomationBundle) and add following
configuration to `app/config/admin/config.yml`:

```yaml
sulu_article:
    content_types:
        page_tree_route:
            page_route_cascade: task # "request" or "off" 
```

**Route move**

When the parent-page of multiple articles should be changed (e.g. the page was removed) you can use following command to
update the parent-page of all articles which are related to the given page-url.

```bash
bin/console sulu:article:page-tree:move /page-1 /page-2 sulu_io de
```
