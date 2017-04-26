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

## Route Generation

For the route generation this bundle provides two different ways and two different approaches.

### Route Schema

This method is default for SuluArticleBundle. It generates the route by a custom schema which is
already configured in the installation process (see [installation](installation.md)).

To use this approach you have to do nothing (if you followed the installation description). For
the completeness you have to be aware that following steps are done:
 
* The content-type `route` for the property `routePath`
* The `route_schema` was configured for the appropriate template or type.

### Page tree integration

The other approach is based on the page-tree. You can choose a page which is used as the parent
route to generate the routes for the article (e.g. `/page/article`). Each article can have its
own parent-page and can so be coupled to the page.

To use this approach you have to change the content-type of the property `routePath` to
`page_tree_route`. This will change the UI for this property in the Sulu-Admin and behind the 
scenes the routes will be handled in a different way.  
