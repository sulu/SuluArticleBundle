# Upgrade

## 0.8.0 (unreleased)

### Reindex command

The `sulu:article:index-rebuild` command was refactored and renamed to `sulu:article:reindex`. 
See [Commands in documentation](Resources/doc/commands.md).

### NewIndex mapping has changed

Recreate the index to update mapping (new `content_data` field) and reindex your articles:

```bash
bin/adminconsole sulu:article:reindex --drop --no-interactive
bin/websiteconsole sulu:article:reindex --drop --no-interactive
```

## 0.7.0

### Index mapping has changed

Update configuration for Elasticsearch [^2.2](Resources/doc/installation-es2.md) or 
[^5.0](Resources/doc/installation-es5.md) and add the new analyzer `pathAnalyzer`.

After that recreate the index and reindex your articles:

```bash
bin/adminconsole ongr:es:index:drop -m default --force
bin/websiteconsole ongr:es:index:drop -m live --force

bin/adminconsole ongr:es:index:create -m default
bin/websiteconsole ongr:es:index:create -m live

bin/adminconsole sulu:article:index-rebuild ###LOCALE###
bin/websiteconsole sulu:article:index-rebuild ###LOCALE### --live
```

## 0.6.1

### Resolve of excerpt data

Excerpt data is now resolved in the article template instead of an array of categories and images you
get directly the data.

__before__:

```twig
{{ extension.excerpt.categories[0] }}
{{ extension.excerpt.images.ids[0] }}
{{ extension.excerpt.icon.ids[0] }}
```

__after__:

```twig
{{ extension.excerpt.categories[0].id }}
{{ extension.excerpt.images[0].id }}
{{ extension.excerpt.icon[0].id }}
```

## 0.6.0

### Index mapping has changed

Recreate the index and reindex your articles:

```bash
bin/adminconsole ongr:es:index:drop -m default --force
bin/websiteconsole ongr:es:index:drop -m live --force

bin/adminconsole ongr:es:index:create -m default
bin/websiteconsole ongr:es:index:create -m live

bin/adminconsole sulu:article:index-rebuild ###LOCALE###
bin/websiteconsole sulu:article:index-rebuild ###LOCALE### --live
```

### DocumentManager

Removed persist option `route_path` use the method `setRoutePath` instead.

## 0.5.0

### Elasticsearch 5.0

Now also support for ElasticSearch 5. To still be compatible with ^2.2, make sure you run: 
* composer require ongr/elasticsearch-bundle:1.2.9

## 0.4.0

### WebsiteArticleController

The multi-page feature needs a refactoring of the ``WebsiteArticleController``.
If you have overwritten it you have to adapt it.

__Before:__

```php
class CustomArticleController extends Controller
{
    public function indexAction(Request $request, ArticleDocument $object, $view)
    {
        $content = $this->get('jms_serializer')->serialize(
            $object,
            'array',
            SerializationContext::create()
                ->setSerializeNull(true)
                ->setGroups(['website', 'content'])
                ->setAttribute('website', true)
        );
        
        return $this->render(
            $view . '.html.twig',
            $this->get('sulu_website.resolver.template_attribute')->resolve($content),
            $this->createResponse($request)
        );
    }
}
```

__After:__

```php
class CustomArticleController extends WebsiteArticleController
{
    public function indexAction(Request $request, ArticleInterface $object, $view, $pageNumber = 1)
    {
        return $this->renderArticle($request, $object, $view, $pageNumber, []);
    }
}
```

### Cachelifetime request attribute changed

The `_cacheLifetime` attribute available in the request parameter of a article
controller will return the seconds and don't need longer be resolved manually
with the cachelifetime resolver.

## 0.2.0

Reindex elastic search indexes:

```bash
bin/adminconsole sulu:article:index-rebuild ###LOCALE### --live
bin/adminconsole sulu:article:index-rebuild ###LOCALE###
```

