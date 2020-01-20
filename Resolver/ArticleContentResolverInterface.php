<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\ArticleBundle\Resolver;

use Sulu\Bundle\ArticleBundle\Document\ArticleInterface;

interface ArticleContentResolverInterface
{
    /**
     * @return mixed
     */
    public function resolve(ArticleInterface $article, int $pageNumber = 1);
}
