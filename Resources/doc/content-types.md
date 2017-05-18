# Content-Types

The SuluArticleBundle provides 3 ways to include articles into another page (or article).

1. Direct assignment: `article_selection`
2. Smart-Content: `articles` or `articles_page_tree` 
2. Teaser-Selection: `article` 

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

## Smart-Content

This bundle provides also a way to include articles in the 
[Smart-Content](http://docs.sulu.io/en/latest/reference/content-types/smart_content.html).
This allows to create dynamic lists of articles for example on overview-pages.

There exists 2 different providers which does almost the same. The only difference is that
the `articles_page_tree` allows to select a page as data-source which will be used to filter
the articles.

**Alias:** `articles` or `articles_page_tree`

### Parameters

| Name  | Type    | Description                                         |
|-------|---------|-----------------------------------------------------|
| types | string  | Comma separated list of types which can be selected |

### Returns

The content-type returns a list of `ArticleViewDocumentInterface` instances.

## Teaser-Selection

Also for the [teaser-selection](http://docs.sulu.io/en/latest/reference/content-types/teaser_selection.html)
an `article` provider exists.

It allows to include there also articles. Same as for the pages, the data for the `Teaser`
will be extracted from the excerpt or defined properties (`title`, `sulu.teaser.description` or
`sulu.teaser.media`)

Additionally this Teaser contains following attributes:

| Name          | Type    | Description                       |
|---------------|---------|-----------------------------------|
| structureType | string  | Key of selected template          |
| type          | string  | Article-Type of selected template |

To use this attributes use `{{ teaser.attributes.structureType }}` but wrap it with a if-statement
to ensure that this is only used for article teasers.
