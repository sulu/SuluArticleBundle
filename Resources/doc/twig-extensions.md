# Content-Types

Twig Extensions

* Load recent articles: `sulu_article_load_recent`
* Load similar articles: `sulu_article_load_recent` 

## Article-Selection

Allows to select articles and assign them to another page or article.

### Parameters

No parameters.

### Returns

The content-type returns a list of `ArticleViewDocumentInterface` instances.

### Example

```xml
<property name="articles" type="article_selection">
   <meta>
        <title lang="en">Articles</title>
        <title lang="de">Artikel</title>
    </meta>
</property>
```
