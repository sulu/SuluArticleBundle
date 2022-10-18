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

namespace Sulu\Bundle\ArticleBundle\Tests\Unit\Infrastructure\Sulu\Headless\Content\ContentTypeResolver;

use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\Prophecy\ObjectProphecy;
use Sulu\Bundle\ArticleBundle\Infrastructure\Sulu\Headless\ContentTypeResolver\ArticleSelectionResolver;
use Sulu\Bundle\HeadlessBundle\Content\ContentView;
use Sulu\Bundle\HeadlessBundle\Content\StructureResolverInterface;
use Sulu\Component\Content\Compat\PropertyInterface;
use Sulu\Component\Content\Compat\PropertyParameter;
use Sulu\Component\Content\Compat\StructureInterface;
use Sulu\Component\Content\Mapper\ContentMapperInterface;
use Sulu\Component\Content\Query\ContentQueryBuilderInterface;

class ArticleSelectionResolverTest extends TestCase
{
    /**
     * @var StructureResolverInterface|ObjectProphecy
     */
    private $structureResolver;

    /**
     * @var ContentQueryBuilderInterface|ObjectProphecy
     */
    private $contentQueryBuilder;

    /**
     * @var ContentMapperInterface|ObjectProphecy
     */
    private $contentMapper;

    /**
     * @var ArticleSelectionResolver
     */
    private $articleSelectionResolver;

    protected function setUp(): void
    {
        $this->structureResolver = $this->prophesize(StructureResolverInterface::class);
        $this->contentQueryBuilder = $this->prophesize(ContentQueryBuilderInterface::class);
        $this->contentMapper = $this->prophesize(ContentMapperInterface::class);

        $this->articleSelectionResolver = new ArticleSelectionResolver(
            $this->structureResolver->reveal(),
            $this->contentQueryBuilder->reveal(),
            $this->contentMapper->reveal(),
            true
        );
    }

    public function testGetContentType(): void
    {
        self::assertSame('article_selection', $this->articleSelectionResolver::getContentType());
    }

    public function testResolve(): void
    {
        $structure = $this->prophesize(StructureInterface::class);
        $structure->getWebspaceKey()->willReturn('webspace-key');

        /** @var PropertyInterface|ObjectProphecy $property */
        $property = $this->prophesize(PropertyInterface::class);
        $params = [
            'properties' => new PropertyParameter('properties', [
                new PropertyParameter('contentDescription', 'description'),
                new PropertyParameter('excerptTitle', 'excerpt.title'),
                new PropertyParameter('categories', 'excerpt.categories'),
            ]),
        ];

        $property->getParams()->willReturn($params);
        $property->getStructure()->willReturn($structure->reveal());

        // expected and unexpected service calls
        $this->contentQueryBuilder->init([
            'ids' => ['article-id-1', 'article-id-2'],
            'properties' => $params['properties']->getValue(),
            'published' => false,
        ])->shouldBeCalled();
        $this->contentQueryBuilder->build('webspace-key', ['en'])->willReturn(['article-query-string']);

        $articleStructure1 = $this->prophesize(StructureInterface::class);
        $articleStructure1->getUuid()->willReturn('article-id-1');
        $articleStructure2 = $this->prophesize(StructureInterface::class);
        $articleStructure2->getUuid()->willReturn('article-id-2');

        $this->contentMapper->loadBySql2(
            'article-query-string',
            'en',
            'webspace-key'
        )->willReturn([
            $articleStructure2->reveal(),
            $articleStructure1->reveal(),
        ])->shouldBeCalledOnce();
        $this->structureResolver->resolveProperties(
            $articleStructure1->reveal(),
            [
                'title' => 'title',
                'routePath' => 'routePath',
                'contentDescription' => 'description',
                'excerptTitle' => 'excerpt.title',
                'categories' => 'excerpt.categories',
            ],
            'en'
        )->willReturn([
            'id' => 'article-id-1',
            'template' => 'default',
            'content' => [
                'title' => 'Article Title 1',
                'routePath' => '/article-url-1',
                'contentDescription' => 'Article Content Description',
                'excerptTitle' => 'Article Excerpt Title 1',
                'categories' => [],
            ],
            'view' => [
                'title' => [],
                'routePath' => [],
                'contentDescription' => [],
                'excerptTitle' => [],
                'categories' => [],
            ],
        ])->shouldBeCalledOnce();

        $this->structureResolver->resolveProperties(
            $articleStructure2->reveal(),
            [
                'title' => 'title',
                'routePath' => 'routePath',
                'contentDescription' => 'description',
                'excerptTitle' => 'excerpt.title',
                'categories' => 'excerpt.categories',
            ],
            'en'
        )->willReturn([
            'id' => 'article-id-2',
            'template' => 'default',
            'content' => [
                'title' => 'Article Title 2',
                'routePath' => '/article-url-2',
                'contentDescription' => 'Article Content Description',
                'excerptTitle' => 'Article Excerpt Title 2',
                'categories' => [],
            ],
            'view' => [
                'title' => [],
                'routePath' => [],
                'contentDescription' => [],
                'excerptTitle' => [],
                'categories' => [],
            ],
        ])->shouldBeCalledOnce();

        // call test function
        $result = $this->articleSelectionResolver->resolve(
            ['article-id-1', 'article-id-2'],
            $property->reveal(),
            'en'
        );

        $this->assertInstanceOf(ContentView::class, $result);
        $this->assertSame(
            [
                [
                    'id' => 'article-id-1',
                    'template' => 'default',
                    'content' => [
                        'title' => 'Article Title 1',
                        'routePath' => '/article-url-1',
                        'contentDescription' => 'Article Content Description',
                        'excerptTitle' => 'Article Excerpt Title 1',
                        'categories' => [],
                    ],
                    'view' => [
                        'title' => [],
                        'routePath' => [],
                        'contentDescription' => [],
                        'excerptTitle' => [],
                        'categories' => [],
                    ],
                ],
                [
                    'id' => 'article-id-2',
                    'template' => 'default',
                    'content' => [
                        'title' => 'Article Title 2',
                        'routePath' => '/article-url-2',
                        'contentDescription' => 'Article Content Description',
                        'excerptTitle' => 'Article Excerpt Title 2',
                        'categories' => [],
                    ],
                    'view' => [
                        'title' => [],
                        'routePath' => [],
                        'contentDescription' => [],
                        'excerptTitle' => [],
                        'categories' => [],
                    ],
                ],
            ],
            $result->getContent()
        );

        $this->assertSame(
            ['ids' => ['article-id-1', 'article-id-2']],
            $result->getView()
        );
    }

    public function testResolveDataIsNull(): void
    {
        $locale = 'en';
        $property = $this->prophesize(PropertyInterface::class);

        // expected and unexpected service calls
        $this->contentQueryBuilder->init(Argument::cetera())
            ->shouldNotBeCalled();

        $this->structureResolver->resolve(Argument::cetera())
            ->shouldNotBeCalled();

        // call test function
        $result = $this->articleSelectionResolver->resolve(null, $property->reveal(), $locale);

        $this->assertSame([], $result->getContent());

        $this->assertSame(['ids' => []], $result->getView());
    }

    public function testResolveDataIsEmptyArray(): void
    {
        $locale = 'en';
        $property = $this->prophesize(PropertyInterface::class);

        // expected and unexpected service calls
        $this->contentQueryBuilder->init(Argument::any())
            ->shouldNotBeCalled();

        $this->structureResolver->resolve(Argument::any())
            ->shouldNotBeCalled();

        // call test function
        $result = $this->articleSelectionResolver->resolve([], $property->reveal(), $locale);

        $this->assertSame([], $result->getContent());

        $this->assertSame(['ids' => []], $result->getView());
    }
}
