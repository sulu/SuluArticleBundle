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
| contentFields | string[] | Contains content properties tagged with `sulu.search.field` |

The `content` and `view` property is represented by a proxy to avoid resolving data where it is not needed.

### ContentFields

The content of the property `contentFields` can be customized. All properties which are tagged with
`sulu.search.field` in the xml configuration, are automatically added to this field. The main purpose of this field
is to give the developer enough data and flexibility to implement search functionality via e.g. a `SearchController`.

Example:
```xml
    <property name="text" type="text_editor" mandatory="true">
        <meta>
            <title lang="en">Text</title>
            <title lang="de">Text</title>
        </meta>
    
        <tag name="sulu.search.field"/>
    </property>
```

## How to extend?

To extend the indexed data you can extend the `ArticleViewDocument`. This can be achieved by performing the following
steps. The same steps can also be used to extend the `ArticlePageViewObject`.

### 0. Create a Bundle Class

Unfortunately, the ElasticsearchBundle allows to overwrite document classes only if an `AppBundle` is registered in the 
application. If you do not have registered such a bundle yet, you need to add it to your `src` directory and enable it
in the `config/bundles.php` file.

```php
<?php

// src/AppBundle.php

namespace App;

use Symfony\Component\HttpKernel\Bundle\Bundle;

class AppBundle extends Bundle
{
}
```

```php
<?php

// config/bundles.php

return [
    // ...
    \App\AppBundle::class => ['all' => true],
]
```

#### 1. Create custom class

```php
<?php

// src/Document/ArticleViewDocument.php

namespace App\Document;

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
    public $myCustomProperty;
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

// src/EventListener/ArticleIndexListener.php

namespace App\EventListener;

use Sulu\Bundle\ArticleBundle\Event\IndexEvent;

class ArticleIndexListener
{
    public function onIndex(IndexEvent $event)
    {
        $document = $event->getDocument();
        $viewDocument = $event->getViewDocument();
        $data = $document->getStructure()->toArray();

        if (!array_key_exists('myCustomProperty', $data)) {
            return;
        }

        $viewDocument->myCustomProperty = $data['myCustomProperty'];
    }
}
```

```xml
<service id="app.sulu_article.index_listener" class="App\EventListener\ArticleIndexListener">
    <tag name="kernel.event_listener" event="sulu_article.index" method="onIndex"/>
</service>
```
