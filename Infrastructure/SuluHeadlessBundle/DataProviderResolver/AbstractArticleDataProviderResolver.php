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

use Sulu\Bundle\HeadlessBundle\Content\DataProviderResolver\DataProviderResolverInterface;
use Sulu\Bundle\HeadlessBundle\Content\DataProviderResolver\DataProviderResult;
use Sulu\Bundle\HeadlessBundle\Content\StructureResolverInterface;
use Sulu\Component\Content\Compat\PropertyParameter;
use Sulu\Component\Content\Compat\StructureInterface;
use Sulu\Component\Content\Mapper\ContentMapperInterface;
use Sulu\Component\Content\Query\ContentQueryBuilderInterface;
use Sulu\Component\SmartContent\Configuration\ProviderConfigurationInterface;
use Sulu\Component\SmartContent\DataProviderInterface;

abstract class AbstractArticleDataProviderResolver implements DataProviderResolverInterface
{
    /**
     * @var DataProviderInterface
     */
    private $articleDataProvider;

    /**
     * @var StructureResolverInterface
     */
    private $structureResolver;

    /**
     * @var ContentQueryBuilderInterface
     */
    private $contentQueryBuilder;

    /**
     * @var ContentMapperInterface
     */
    private $contentMapper;

    /**
     * @var bool
     */
    private $showDrafts;

    public function __construct(
        DataProviderInterface $articleDataProvider,
        StructureResolverInterface $structureResolver,
        ContentQueryBuilderInterface $contentQueryBuilder,
        ContentMapperInterface $contentMapper,
        bool $showDrafts
    ) {
        $this->articleDataProvider = $articleDataProvider;
        $this->structureResolver = $structureResolver;
        $this->contentQueryBuilder = $contentQueryBuilder;
        $this->contentMapper = $contentMapper;
        $this->showDrafts = $showDrafts;
    }

    public function getProviderConfiguration(): ProviderConfigurationInterface
    {
        return $this->articleDataProvider->getConfiguration();
    }

    /**
     * @return PropertyParameter[]
     */
    public function getProviderDefaultParams(): array
    {
        return $this->articleDataProvider->getDefaultPropertyParameter();
    }

    /**
     * @var PropertyParameter[]
     */
    public function resolve(
        array $filters,
        array $propertyParameters,
        array $options = [],
        ?int $limit = null,
        int $article = 1,
        ?int $pageSize = null
    ): DataProviderResult {
        $providerResult = $this->articleDataProvider->resolveResourceItems(
            $filters,
            $propertyParameters,
            $options,
            $limit,
            $article,
            $pageSize
        );

        $articleIds = [];
        foreach ($providerResult->getItems() as $resultItem) {
            $articleIds[] = $resultItem->getId();
        }

        /** @var PropertyParameter[] $propertiesParamValue */
        $propertiesParamValue = isset($propertyParameters['properties']) ? $propertyParameters['properties']->getValue() : [];

        // the ArticleDataProvider resolves the data defined in the $propertiesParamValue using the default content types
        // for example, this means that the result contains an array of media api entities instead of a raw array of ids
        // to resolve the data with the resolvers of this bundle, we need to load the structures with the ContentMapper
        $articleStructures = $this->loadArticleStructures(
            $articleIds,
            $propertiesParamValue,
            $options['webspaceKey'],
            $options['locale']
        );

        $propertyMap = [
            'title' => 'title',
            'routePath' => 'routePath',
        ];

        foreach ($propertiesParamValue as $propertiesParamEntry) {
            $paramName = $propertiesParamEntry->getName();
            $paramValue = $propertiesParamEntry->getValue();
            $propertyMap[$paramName] = \is_string($paramValue) ? $paramValue : $paramName;
        }

        $resolvedArticles = \array_fill_keys($articleIds, null);

        foreach ($articleStructures as $articleStructure) {
            $resolvedArticles[$articleStructure->getUuid()] = $this->structureResolver->resolveProperties($articleStructure, $propertyMap, $options['locale']);
        }

        return new DataProviderResult(\array_values(\array_filter($resolvedArticles)), $providerResult->getHasNextPage());
    }

    /**
     * @param string[] $articleIds
     * @param PropertyParameter[] $propertiesParamValue
     *
     * @return StructureInterface[]
     */
    private function loadArticleStructures(array $articleIds, array $propertiesParamValue, string $webspaceKey, string $locale): array
    {
        if (0 === \count($articleIds)) {
            return [];
        }

        $this->contentQueryBuilder->init([
            'ids' => $articleIds,
            'properties' => $propertiesParamValue,
            'published' => !$this->showDrafts,
        ]);
        [$articlesQuery] = $this->contentQueryBuilder->build($webspaceKey, [$locale]);

        return $this->contentMapper->loadBySql2(
            $articlesQuery,
            $locale,
            $webspaceKey
        );
    }
}
