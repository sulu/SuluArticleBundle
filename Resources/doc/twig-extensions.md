# Twig Extensions

Twig Extensions

* Load recent articles: `sulu_article_load_recent`
* Load similar articles: `sulu_article_load_similar` 

## `sulu_article_load_recent`

Returns 

### Arguments

- **limit**: *integer* - optional: set the limit - default: 5
- **types**: *array* - optional: filter for article types - default: type of the requested article
- **locale**: *string* - optional: load data from excerpt tab

### Returns

The content-type returns a list of `ArticleResourceItem` instances.

### Example

```twig
<property name="articles" type="article_selection">
   <meta>
        <title lang="en">Articles</title>
        <title lang="de">Artikel</title>
    </meta>
</property>
```
