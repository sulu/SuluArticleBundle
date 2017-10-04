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

The `content` and `view` property is represented by a proxy to avoid resolving data where it is not needed.
