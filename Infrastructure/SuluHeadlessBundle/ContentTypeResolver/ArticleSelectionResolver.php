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

namespace Sulu\Bundle\ArticleBundle\Infrastructure\SuluHeadlessBundle\ContentTypeResolver;

use Sulu\Bundle\HeadlessBundle\Content\ContentTypeResolver\ContentTypeResolverInterface;
use Sulu\Bundle\HeadlessBundle\Content\ContentView;
use Sulu\Bundle\HeadlessBundle\Content\StructureResolverInterface;
use Sulu\Component\Content\Compat\PropertyInterface;
use Sulu\Component\Content\Compat\PropertyParameter;
use Sulu\Component\Content\Mapper\ContentMapperInterface;
use Sulu\Component\Content\Query\ContentQueryBuilderInterface;

class ArticleSelectionResolver implements ContentTypeResolverInterface
{
    public static function getContentType(): string
    {
        return 'article_selection';
    }

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
        StructureResolverInterface $structureResolver,
        ContentQueryBuilderInterface $contentQueryBuilder,
        ContentMapperInterface $contentMapper,
        bool $showDrafts,
    ) {
        $this->structureResolver = $structureResolver;
        $this->contentQueryBuilder = $contentQueryBuilder;
        $this->contentMapper = $contentMapper;
        $this->showDrafts = $showDrafts;
    }

    public function resolve($data, PropertyInterface $property, string $locale, array $attributes = []): ContentView
    {
        /** @var string[]|null $ids */
        $ids = $data;

        if (empty($ids)) {
            return new ContentView([], ['ids' => []]);
        }

        /** @var PropertyParameter[] $params */
        $params = $property->getParams();
        /** @var PropertyParameter[] $propertiesParamValue */
        $propertiesParamValue = isset($params['properties']) ? $params['properties']->getValue() : [];

        $this->contentQueryBuilder->init([
            'ids' => $ids,
            'properties' => $propertiesParamValue,
            'published' => !$this->showDrafts,
        ]);

        /** @var array{string, mixed[]} $queryBuilderResult */
        $queryBuilderResult = $this->contentQueryBuilder->build($property->getStructure()->getWebspaceKey(), [$locale]);
        list($articlesQuery) = $queryBuilderResult;

        $articleStructures = $this->contentMapper->loadBySql2(
            $articlesQuery,
            $locale,
            $property->getStructure()->getWebspaceKey()
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

        $articles = \array_fill_keys($ids, null);

        foreach ($articleStructures as $articleStructure) {
            $articles[$articleStructure->getUuid()] = $this->structureResolver->resolveProperties($articleStructure, $propertyMap, $locale);
        }

        return new ContentView(\array_values(\array_filter($articles)), ['ids' => $ids]);
    }
}
