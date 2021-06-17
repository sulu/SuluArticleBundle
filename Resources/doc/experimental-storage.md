# Experimental Content Bundle Storage

For the **experimental** storage the articles are stored using the [SuluContentBundle](https://github.com/sulu/sulucontentbundle).

## Table of content

 - [Installation](#installation)
 - [Routes](#routes)
 - [Configuration](#configuration)
 - [Override Entities](#override-entities)
   - [Override Article Entity](#override-article-entity)
   - [Override Article Content Entity](#override-article-entity)

## Installation

To use the experimental storage you need to have the [SuluContentBundle](https://github.com/sulu/sulucontentbundle) installed.

```bash
composer require sulu/content-bundle
```

Then you can configure it:

```yaml
sulu_article:
    article:
        storage: experimental
```

## Routes

```yaml
# config/routes/sulu_article_admin.yaml

## coming soon ...
```

## Configuration

The following is showing the full configuration of the **experimental** article module:

```yaml
sulu_product:
    storage: experimental

    # optional
    objects:
        article:
            model: 'Sulu\Bundle\ArticleBundle\Domain\Model\Article'
        article_content:
            model: 'Sulu\Bundle\ArticleBundle\Domain\Model\ArticleDimensionContent'
```

## Override Entities

### Override Article Entity

```php
<?php
// src/Entity/Article.php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Sulu\Bundle\ArticleBundle\Domain\Model\Article as SuluArticle;

/**
 * @ORM\Table(name="ar_articles")
 * @ORM\Entity
 */
class Article extends SuluArticle
{
}
```

Configure your new model class:

```yaml
# config/packages/sulu_article.yaml

sulu_article:
    objects:
        article:
            model: 'App\Entity\Article'
```

To add new fields to your entity have a look at the [Doctrine Mapping Documentation](https://www.doctrine-project.org/projects/doctrine-orm/en/2.7/reference/basic-mapping.html);

### Override Article Content Entity

```php
<?php
// src/Entity/Article.php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Sulu\Bundle\ArticleBundle\Domain\Model\ArticleDimensionContent as SuluArticleDimensionContent;

/**
 * @ORM\Table(name="ar_articles_dimension_contents")
 * @ORM\Entity
 */
class ArticleDimensionContent extends SuluArticleDimensionContent
{
}
```

Configure your new model class:

```yaml
# config/packages/sulu_article.yaml

sulu_article:
    objects:
        article_content:
            model: 'App\Entity\ArticleDimensionContent'
```

To add new fields to your entity have a look at the [Doctrine Mapping Documentation](https://www.doctrine-project.org/projects/doctrine-orm/en/2.7/reference/basic-mapping.html);
