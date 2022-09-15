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

namespace Sulu\Bundle\HeadlessBundle\Tests\Unit\Content\ContentTypeResolver;

use PHPUnit\Framework\TestCase;
use Prophecy\Prophecy\ObjectProphecy;
use Sulu\Bundle\ArticleBundle\Infrastructure\SuluHeadlessBundle\ContentTypeResolver\ArticleSelectionResolver;
use Sulu\Bundle\ArticleBundle\Infrastructure\SuluHeadlessBundle\ContentTypeResolver\SingleArticleSelectionResolver;
use Sulu\Bundle\HeadlessBundle\Content\ContentView;
use Sulu\Component\Content\Compat\PropertyInterface;

class SingleArticleSelectionResolverTest extends TestCase
{
    /**
     * @var SingleArticleSelectionResolver
     */
    private $singleArticleSelectionResolver;

    /**
     * @var ArticleSelectionResolver|ObjectProphecy
     */
    private $articleSelectionResolver;

    protected function setUp(): void
    {
        $this->articleSelectionResolver = $this->prophesize(ArticleSelectionResolver::class);

        $this->singleArticleSelectionResolver = new SingleArticleSelectionResolver(
            $this->articleSelectionResolver->reveal()
        );
    }

    public function testGetContentType(): void
    {
        self::assertSame('single_article_selection', $this->singleArticleSelectionResolver::getContentType());
    }

    public function testResolve(): void
    {
        $property = $this->prophesize(PropertyInterface::class);
        $this->articleSelectionResolver->resolve([1], $property, 'en', [])->willReturn(
            new ContentView(
                [
                    [
                        'id' => '1',
                        'uuid' => '1',
                        'nodeType' => 1,
                        'path' => '/testarticle',
                        'changer' => 1,
                        'publishedState' => true,
                        'creator' => 1,
                        'title' => 'TestArticle',
                        'locale' => 'en',
                        'webspaceKey' => 'sulu',
                        'template' => 'headless',
                        'parent' => '1',
                        'author' => '2',
                    ],
                ],
                [1]
            )
        );

        $result = $this->singleArticleSelectionResolver->resolve(1, $property->reveal(), 'en');

        $this->assertInstanceOf(ContentView::class, $result);
        $this->assertSame(
            [
                'id' => '1',
                'uuid' => '1',
                'nodeType' => 1,
                'path' => '/testarticle',
                'changer' => 1,
                'publishedState' => true,
                'creator' => 1,
                'title' => 'TestArticle',
                'locale' => 'en',
                'webspaceKey' => 'sulu',
                'template' => 'headless',
                'parent' => '1',
                'author' => '2',
            ],
            $result->getContent()
        );
        $this->assertSame(
            [
                'id' => 1,
            ],
            $result->getView()
        );
    }

    public function testResolveDataIsNull(): void
    {
        $locale = 'en';
        $property = $this->prophesize(PropertyInterface::class);

        $result = $this->singleArticleSelectionResolver->resolve(null, $property->reveal(), $locale);

        $this->assertNull($result->getContent());

        $this->assertSame(['id' => null], $result->getView());
    }
}
