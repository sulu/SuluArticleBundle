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

namespace Sulu\Bundle\ArticleBundle\Tests\Unit\Infrastructure\SuluHeadlessBundle\Content\DataProviderResolver;

use PHPUnit\Framework\TestCase;
use Prophecy\Prophecy\ObjectProphecy;
use Sulu\Bundle\ArticleBundle\Infrastructure\SuluHeadlessBundle\DataProviderResolver\ArticlePageTreeDataProviderResolver;
use Sulu\Bundle\HeadlessBundle\Content\StructureResolverInterface;
use Sulu\Component\Content\Compat\PropertyParameter;
use Sulu\Component\Content\Compat\StructureInterface;
use Sulu\Component\Content\Mapper\ContentMapperInterface;
use Sulu\Component\Content\Query\ContentQueryBuilderInterface;
use Sulu\Component\SmartContent\Configuration\ProviderConfigurationInterface;
use Sulu\Component\SmartContent\DataProviderInterface;
use Sulu\Component\SmartContent\DataProviderResult;
use Sulu\Component\SmartContent\ResourceItemInterface;

class ArticlePageTreeDataProviderResolverTest extends TestCase
{
    /**
     * @var DataProviderInterface|ObjectProphecy
     */
    private $articleDataProvider;

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
     * @var ArticlePageTreeDataProviderResolver
     */
    private $articleDataProviderResolver;

    protected function setUp(): void
    {
        $this->articleDataProvider = $this->prophesize(DataProviderInterface::class);
        $this->structureResolver = $this->prophesize(StructureResolverInterface::class);
        $this->contentQueryBuilder = $this->prophesize(ContentQueryBuilderInterface::class);
        $this->contentMapper = $this->prophesize(ContentMapperInterface::class);

        $this->articleDataProviderResolver = new ArticlePageTreeDataProviderResolver(
            $this->articleDataProvider->reveal(),
            $this->structureResolver->reveal(),
            $this->contentQueryBuilder->reveal(),
            $this->contentMapper->reveal(),
            true
        );
    }

    public function testGetDataProvider(): void
    {
        self::assertSame('articles_page_tree', $this->articleDataProviderResolver::getDataProvider());
    }

    public function testGetProviderConfiguration(): void
    {
        $configuration = $this->prophesize(ProviderConfigurationInterface::class);
        $this->articleDataProvider->getConfiguration()->willReturn($configuration->reveal());

        $this->assertSame($configuration->reveal(), $this->articleDataProviderResolver->getProviderConfiguration());
    }

    public function testGetProviderDefaultParams(): void
    {
        $propertyParameter = $this->prophesize(PropertyParameter::class);
        $this->articleDataProvider->getDefaultPropertyParameter()->willReturn(['test' => $propertyParameter->reveal()]);

        $this->assertSame(['test' => $propertyParameter->reveal()], $this->articleDataProviderResolver->getProviderDefaultParams());
    }

    public function testResolve(): void
    {
        $providerResultItem1 = $this->prophesize(ResourceItemInterface::class);
        $providerResultItem1->getId()->willReturn('article-id-1');

        $providerResultItem2 = $this->prophesize(ResourceItemInterface::class);
        $providerResultItem2->getId()->willReturn('article-id-2');

        $providerResult = $this->prophesize(DataProviderResult::class);
        $providerResult->getHasNextPage()->willReturn(true);
        $providerResult->getItems()->willReturn([$providerResultItem1->reveal(), $providerResultItem2->reveal()]);

        $propertyParameters = [
            'properties' => new PropertyParameter('properties', [
                new PropertyParameter('contentDescription', 'description'),
                new PropertyParameter('excerptTitle', 'excerpt.title'),
            ]),
        ];

        // expected and unexpected service calls
        $this->articleDataProvider->resolveResourceItems(
            ['filter-key' => 'filter-value'],
            $propertyParameters,
            ['webspaceKey' => 'webspace-key', 'locale' => 'en'],
            10,
            1,
            5
        )->willReturn($providerResult->reveal())->shouldBeCalledOnce();

        $this->contentQueryBuilder->init([
            'ids' => ['article-id-1', 'article-id-2'],
            'properties' => $propertyParameters['properties']->getValue(),
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
            ],
            'view' => [
                'title' => [],
                'routePath' => [],
                'contentDescription' => [],
                'excerptTitle' => [],
            ],
        ])->shouldBeCalledOnce();

        $this->structureResolver->resolveProperties(
            $articleStructure2->reveal(),
            [
                'title' => 'title',
                'routePath' => 'routePath',
                'contentDescription' => 'description',
                'excerptTitle' => 'excerpt.title',
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
            ],
            'view' => [
                'title' => [],
                'routePath' => [],
                'contentDescription' => [],
                'excerptTitle' => [],
            ],
        ])->shouldBeCalledOnce();

        // call test function
        $result = $this->articleDataProviderResolver->resolve(
            ['filter-key' => 'filter-value'],
            $propertyParameters,
            ['webspaceKey' => 'webspace-key', 'locale' => 'en'],
            10,
            1,
            5
        );

        $this->assertTrue($result->getHasNextPage());
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
                    ],
                    'view' => [
                        'title' => [],
                        'routePath' => [],
                        'contentDescription' => [],
                        'excerptTitle' => [],
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
                    ],
                    'view' => [
                        'title' => [],
                        'routePath' => [],
                        'contentDescription' => [],
                        'excerptTitle' => [],
                    ],
                ],
            ],
            $result->getItems()
        );
    }

    public function testResolveEmptyProviderResult(): void
    {
        $providerResult = $this->prophesize(DataProviderResult::class);
        $providerResult->getHasNextPage()->willReturn(false);
        $providerResult->getItems()->willReturn([]);

        $propertyParameters = [
            'properties' => new PropertyParameter('properties', [
                new PropertyParameter('contentDescription', 'description'),
                new PropertyParameter('excerptTitle', 'excerpt.title'),
            ]),
        ];

        // expected and unexpected service calls
        $this->articleDataProvider->resolveResourceItems(
            ['filter-key' => 'filter-value'],
            $propertyParameters,
            ['webspaceKey' => 'webspace-key', 'locale' => 'en'],
            10,
            1,
            5
        )->willReturn($providerResult->reveal())
            ->shouldBeCalledOnce();

        // call test function
        $result = $this->articleDataProviderResolver->resolve(
            ['filter-key' => 'filter-value'],
            $propertyParameters,
            ['webspaceKey' => 'webspace-key', 'locale' => 'en'],
            10,
            1,
            5
        );

        $this->assertFalse($result->getHasNextPage());
        $this->assertSame(
            [],
            $result->getItems()
        );
    }
}
