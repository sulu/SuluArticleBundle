<?php

declare(strict_types=1);

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\ArticleBundle\Infrastructure\SuluHeadlessBundle\DataProviderResolver;

class ArticleDataProviderResolver extends AbstractArticleDataProviderResolver
{
    public static function getDataProvider(): string
    {
        return 'articles';
    }
}
