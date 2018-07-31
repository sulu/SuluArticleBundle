# Twig Extensions

Twig Extensions

* Load recent articles: `sulu_article_load_recent`
* Load similar articles: `sulu_article_load_similar` 

## `sulu_article_load_recent`

Returns recent published articles, filter by given parameters.

### Arguments

- **limit**: *integer* - optional: set the limit - default: 5
- **types**: *array* - optional: filter for article types - default: type of the requested article or null
- **locale**: *string* - optional: filter for locale - default: locale of the request
- **ignoreWebspaces**: *bool* - ignore webspace settings - default: false

### Returns

The content-type returns a list of `ArticleResourceItem` instances.

### Example

```twig
{% set articles = sulu_article_load_recent(3, ['blog']) %}
```

## `sulu_article_load_similar`

Returns similar articles compared to the requested one.
Note: Which fields are included in this request can be configured with `sulu_article.search_fields` config parameter.

### Arguments

- **limit**: *integer* - optional: set the limit - default: 5
- **types**: *array* - optional: filter for article types - default: type of the requested article
- **locale**: *string* - optional: filter for locale - default: locale of the request
- **ignoreWebspaces**: *bool* - ignore webspace settings - default: false

### Returns

The content-type returns a list of `ArticleResourceItem` instances.

### Example

```twig
{% set articles = sulu_article_load_similar(5, ['blog']) %}
```
