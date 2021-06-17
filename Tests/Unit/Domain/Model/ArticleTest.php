<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\ArticleBundle\Tests\Unit\Domain\Model;

use PHPUnit\Framework\TestCase;
use Sulu\Bundle\ArticleBundle\Domain\Model\Article;
use Sulu\Bundle\ArticleBundle\Domain\Model\ArticleInterface;

class ArticleTest extends TestCase
{
    public function testGetId()
    {
        $article = $this->createArticle();

        $this->assertNotNull($article->getId());
    }

    public function testGetIdCustom()
    {
        $article = $this->createArticle(['id' => '9dd3f8c6-f000-4a37-a780-fe8c3128526d']);

        $this->assertSame('9dd3f8c6-f000-4a37-a780-fe8c3128526d', $article->getId());
    }

    private function createArticle(array $data = []): ArticleInterface
    {
        return new Article(
            $data['id'] ?? null
        );
    }
}
