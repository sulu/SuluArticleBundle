# Experimental Content Bundle Storage

For the **experimental** storage the articles are stored using the [SuluContentBundle](https://github.com/sulu/sulucontentbundle).

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

## Configuration

The following is showing the full configuration of the **experimental** article module:

```yaml
sulu_product:
    article:
        storage: experimental

        # optional
        objects:
            article:
                model: 'Sulu\Bundle\ArticleBundle\Article\Domain\Model\Article'
```

## Override Entities

### Override Article Entity

```php
<?php
// src/Entity/Article.php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Sulu\Bundle\ArticleBundle\Product\Domain\Model\Article as SuluArticle;

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
    article:
        objects:
            article:
                model: 'App\Entity\Article'
```

To add new fields to your entity have a look at the [Doctrine Mapping Documentation](https://www.doctrine-project.org/projects/doctrine-orm/en/2.7/reference/basic-mapping.html);
