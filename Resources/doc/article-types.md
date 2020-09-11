# Article Types

The SuluArticleBundle provides an additional way to divide different types of templates and group articles in the 
Sulu-Admin. The article type of a template is set with the `sulu_article.type` tag.

This has following impacts:

* The article list in the Sulu-Admin will be extended by a tab-navigation to filter for article-types.
* Permissions in the settings area of the Sulu-Admin are managed separately for each article type.
* When changing the template of an article, you can only choose templates with the same article-type.

> To allow users to see a newly added article type, you need to add the permissions for the article to the respective user roles

The article-type can also be used for querying the elastic-search index, it is stored in the field `type`.

## Sulu-Admin translations

To translate the name of the type in the Sulu-Admin UI you can use the following configuration snippet:

```yml
sulu_article:
    types:
        my_article_type:
            translation_key: "app.article_types.my_article_type"
```

This key can the be used to translate the key by adding a Translation File `translations/admin.en.json`.

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
