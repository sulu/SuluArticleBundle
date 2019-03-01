# Multi Webspaces

When you have more than one webspace in your Sulu installation you should provide a default main webspace and optional additional webspaces for which the article is valid.

## Functionality

Normally an article will be delivered only for the main webspace. Further an article can be delivered for
the additional webspaces which are configured. To prevent duplicate content issues a canonical tag with the
url for the main webspace is inclued in the HTML head.

More information about this topic:
* Duplicate content: https://moz.com/learn/seo/duplicate-content
* Canonicalization: https://moz.com/learn/seo/canonicalization

## Content Manager

For each article the default webspace configuration can be overwritten by the content manager.

## URL Generation

The twig method [`sulu_page_path`](http://docs.sulu.io/en/latest/reference/twig-extensions/functions/sulu_page_path.html) can generate url with another webspace. 
Just give this method the `targetWebspace` of the article as second argument.

For example: `sulu_page_path(article.routePath, article.targetWebspace)`.
