# Article Types

The SuluArticleBundle provides an additional way to divide different types of templates and group articles in the 
Sulu-Admin.

This has following impacts:

* The article list in the Sulu-Admin will be extended by a tab-navigation to filter for article-types.
* When creating a new article you have to decide which type of article you want to create.
* You can only choose templates with the same article-type.
* Article-Types cannot be changed.

The article-type can also be used for querying the elastic-search index, it is stored in the field `type`.

## Sulu-Admin translations

To translate the name of the type in the Sulu-Admin UI you can use the following configuration snippet:

```yml
sulu_article:
    types:
        my_article_type:
            translation_key: "app.article_types.my_article_type"
```

This key can the be used to translate the key with the 
[Sulu translation system](http://docs.sulu.io/en/latest/developer/contributing/adding-translations.html).

## Usage in Content-Types

### Smart-Content

The type can also be used to predefine a filter for the smart-content provider for articles. You can simply add the following param to the template definition:

```xml
<property name="articles" type="smart_content">
    <params>
        <param name="types" value="my_article_type,my_custom_article_type"/>
    </params>
</property>
```

This will filter the smart-content for the given two types of articles.

## Route Schema

The bundle provides also a way to define a `route_schema` foreach article-type.

```yml
sulu_route:
    mappings:
        Sulu\Bundle\ArticleBundle\Document\ArticleDocument:
            generator: type
            options:
                my_custom_type: "/custom/{object.getTitle()}"
                my_second_custom_type: "/custom2/{object.getTitle()}"
```

See more information in [Routing](routing.md#route-schema).
