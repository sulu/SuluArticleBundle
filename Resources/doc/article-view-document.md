# ArticleViewDocument

This object is used to index article data in Elasticsearch. This index is used to query data where more than one article
is requested (content-types, smart-content, ...).

## Properties

| Property | Type | Description |
| --- | --- | --- |
| uuid | string |  |
| locale | string |  |
| title | string |  |
| routePath | string |  |
| type | string |  |
| structureType | string | Key of the XML Template file |
| changerFullName | string | Fullname of the changer |
| creatorFullName | string | Fullname of the creator |
| authorFullName | string | Fullname of the author |
| changed | DateTime | Timestamp of last modification |
| created | DateTime | Timestamp of creation |
| authored | DateTime | Managed timestamp of publication |
| published | DateTime | Timestamp of publication |
| excerpt | ExcerptViewObject | Full resolved (incl. media formats) of excerpt data |
| seo | SeoViewObject | Data of excerpt data |
| pages | ArticlePageViewObject[] | Content of pages (incl. content and view) |
| content | array | Resolved content from raw-data |
| view | array | Resolved view from raw-data |
| targetWebspace | string | Recommended webspace key |
| mainWebspace | string | Configured main webspace |
| additionalWebspaces | string[] | Configured additional webspaces |

The `content` and `view` property is represented by a proxy to avoid resolving data where it is not needed.

## How to extend?

To extend the indexed data you can extend the `ArticleViewDocument`. This can be achieved by performing the following
steps. The same steps can also be used to extend the `ArticlePageViewObject`.

#### 1. Create custom class

```php
<?php

namespace AppBundle\Document;

use ONGR\ElasticsearchBundle\Annotation\Document;
use ONGR\ElasticsearchBundle\Annotation\Property;
use Sulu\Bundle\ArticleBundle\Document\ArticleViewDocument as SuluArticleViewDocument;

/**
 * @Document(type="article")
 */
class ArticleViewDocument extends SuluArticleViewDocument
{
    /**
     * @var string
     *
     * @Property(
     *     type="text",
     *     options={
     *        "fields"={
     *            "raw"={"type"="keyword"}
     *        }
     *    }
     * )
     */
    public $article;
}
```

See [here](http://docs.ongr.io/ElasticsearchBundle/mapping) for more information about the mapping of 
ongr_elasticsearch. 

#### 2. Configure view-class and ongr_elasticsearch

```yml
ongr_elasticsearch:
    ...
    managers:
        default:
            ...
            mappings:
                - AppBundle
                ...
        live:
            ...
            mappings:
                - AppBundle
                ...

sulu_article:
    documents:
        article:
            view: AppBundle\Document\ArticleViewDocument
```

#### 3. Add listener to set custom value

```php
<?php

namespace AppBundle\EventListener;

use Sulu\Bundle\ArticleBundle\Event\IndexEvent;

class ArticleIndexListener
{
    public function onIndex(IndexEvent $event)
    {
        $document = $event->getDocument();
        $viewDocument = $event->getViewDocument();
        $data = $document->getStructure()->toArray();

        if (!array_key_exists('article', $data)) {
            return;
        }

        $viewDocument->article = $data['article'];
    }
}
```

```xml
<service id="app.sulu_article.index_listener" class="AppBundle\EventListener\ArticleIndexListener">
    <tag name="kernel.event_listener" event="sulu_article.index" method="onIndex"/>
</service>
```
