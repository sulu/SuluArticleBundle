<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\ArticleBundle\Tests\Unit\Article\Domain\Model;

use PHPUnit\Framework\TestCase;
use Prophecy\Prophecy\ObjectProphecy;
use Sulu\Bundle\ArticleBundle\Article\Domain\Model\ArticleDimensionContent;
use Sulu\Bundle\ArticleBundle\Article\Domain\Model\ArticleDimensionContentInterface;
use Sulu\Bundle\ArticleBundle\Article\Domain\Model\ArticleInterface;

class ArticleDimensionContentTest extends TestCase
{
    /**
     * @var ObjectProphecy<ArticleInterface>
     */
    private $article;

    protected function setUp(): void
    {
        $this->article = $this->prophesize(ArticleInterface::class);
    }

    public function testGetResource(): void
    {
        $articleDimensionContent = $this->createArticlDimensionContent();
        $this->assertSame($this->article->reveal(), $articleDimensionContent->getResource());
    }

    public function testTitle(): void
    {
        $articleDimensionContent = $this->createArticlDimensionContent();
        $articleDimensionContent->setTitle('My Title');
        $this->assertSame('My Title', $articleDimensionContent->getTitle());
    }

    public function testTitleNull(): void
    {
        $articleDimensionContent = $this->createArticlDimensionContent();
        $this->assertNull($articleDimensionContent->getTitle());
    }

    public function testTitleTemplateData(): void
    {
        $articleDimensionContent = $this->createArticlDimensionContent();
        $articleDimensionContent->setTemplateData(['title' => 'My Title']);
        $this->assertSame('My Title', $articleDimensionContent->getTitle());
    }

    public function testTemplateData(): void
    {
        $articleDimensionContent = $this->createArticlDimensionContent();
        $articleDimensionContent->setTemplateData(['title' => 'My Title', 'description' => 'My Description']);
        $this->assertSame(
            [
                'description' => 'My Description',
                'title' => 'My Title',
            ],
            $articleDimensionContent->getTemplateData()
        );
    }

    public function testGetTemplateType(): void
    {
        $this->assertSame(
            // use string instead of constant to test regression which maybe need migrations
            'article',
            ArticleDimensionContent::getTemplateType()
        );
    }

    public function testGetResourceKey(): void
    {
        $this->assertSame(
            // use string instead of constant to test regression which maybe need migrations
            'article',
            ArticleDimensionContent::getResourceKey()
        );
    }

    private function createArticlDimensionContent(): ArticleDimensionContentInterface
    {
        return new ArticleDimensionContent(
            $this->article->reveal()
        );
    }
}
