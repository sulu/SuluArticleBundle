# Upgrade

## dev-develop

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
* bin/adminconsole sulu:article:index-rebuild ###LOCALE### -live
* bin/adminconsole sulu:article:index-rebuild ###LOCALE###
