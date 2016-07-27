<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\ArticleBundle\Content;

use ONGR\ElasticsearchBundle\Result\DocumentIterator;
use ONGR\ElasticsearchBundle\Service\Manager;
use ONGR\ElasticsearchDSL\Query\BoolQuery;
use ONGR\ElasticsearchDSL\Query\MatchAllQuery;
use ONGR\ElasticsearchDSL\Query\TermQuery;
use ONGR\ElasticsearchDSL\Search;
use ProxyManager\Factory\LazyLoadingValueHolderFactory;
use ProxyManager\Proxy\LazyLoadingInterface;
use Sulu\Bundle\ArticleBundle\Document\ArticleDocument;
use Sulu\Bundle\ArticleBundle\Document\ArticleOngrDocument;
use Sulu\Component\DocumentManager\DocumentManagerInterface;
use Sulu\Component\SmartContent\Configuration\Builder;
use Sulu\Component\SmartContent\DataProviderInterface;
use Sulu\Component\SmartContent\DataProviderResult;

/**
 * Introduces articles in smart-content.
 */
class ArticleDataProvider implements DataProviderInterface
{
    /**
     * @var Manager
     */
    private $searchManager;

    /**
     * @var DocumentManagerInterface
     */
    private $documentManager;

    /**
     * @var LazyLoadingValueHolderFactory
     */
    private $proxyFactory;

    /**
     * @param Manager $searchManager
     * @param DocumentManagerInterface $documentManager
     * @param LazyLoadingValueHolderFactory $proxyFactory
     */
    public function __construct(
        Manager $searchManager,
        DocumentManagerInterface $documentManager,
        LazyLoadingValueHolderFactory $proxyFactory
    ) {
        $this->searchManager = $searchManager;
        $this->documentManager = $documentManager;
        $this->proxyFactory = $proxyFactory;
    }

    /**
     * {@inheritdoc}
     */
    public function getConfiguration()
    {
        return Builder::create()
            ->enableTags()
            ->enableCategories()
            ->enableLimit()
            ->enablePagination()
            ->enablePresentAs()
            ->setDeepLink('article/{locale}/edit:{id}/details')
            ->getConfiguration();
    }

    /**
     * {@inheritdoc}
     */
    public function getDefaultPropertyParameter()
    {
        return [];
    }

    /**
     * {@inheritdoc}
     */
    public function resolveDataItems(
        array $filters,
        array $propertyParameter,
        array $options = [],
        $limit = null,
        $page = 1,
        $pageSize = null
    ) {
        $result = [];
        $uuids = [];
        /** @var ArticleOngrDocument $document */
        foreach ($this->getSearchResult($filters, $limit, $page, $pageSize) as $document) {
            $uuids[] = $document->getUuid();
            $result[] = new ArticleDataItem($document->getUuid(), $document->getTitle(), $document);
        }

        return new DataProviderResult($result, false, $uuids);
    }

    /**
     * {@inheritdoc}
     */
    public function resolveResourceItems(
        array $filters,
        array $propertyParameter,
        array $options = [],
        $limit = null,
        $page = 1,
        $pageSize = null
    ) {
        $result = [];
        $uuids = [];
        /** @var ArticleOngrDocument $document */
        foreach ($this->getSearchResult($filters, $limit, $page, $pageSize) as $document) {
            $uuids[] = $document->getUuid();
            $result[] = new ArticleResourceItem(
                $document,
                $this->getResource($document->getUuid(), $document->getLocale())
            );
        }

        return new DataProviderResult($result, false, $uuids);
    }

    /**
     * {@inheritdoc}
     */
    public function resolveDatasource($datasource, array $propertyParameter, array $options)
    {
        return;
    }

    /**
     * Creates search for filters and returns search-result.
     *
     * @param array $filters
     * @param int $limit
     * @param int $page
     * @param int $pageSize
     *
     * @return DocumentIterator
     */
    private function getSearchResult(array $filters, $limit, $page, $pageSize)
    {
        $repository = $this->searchManager->getRepository(ArticleOngrDocument::class);
        $search = $repository->createSearch();

        $query = new BoolQuery();

        $queriesCount = 0;
        $operator = $this->getFilter($filters, 'tagOperator', 'or');
        $this->addBoolQuery('tags', $filters, 'excerpt.tags', $operator, $query, $queriesCount);
        $operator = $this->getFilter($filters, 'websiteTagsOperator', 'or');
        $this->addBoolQuery('websiteTags', $filters, 'excerpt.tags', $operator, $query, $queriesCount);

        $operator = $this->getFilter($filters, 'categoryOperator', 'or');
        $this->addBoolQuery('categories', $filters, 'excerpt.categories', $operator, $query, $queriesCount);
        $operator = $this->getFilter($filters, 'websiteCategoriesOperator', 'or');
        $this->addBoolQuery('websiteCategories', $filters, 'excerpt.categories', $operator, $query, $queriesCount);

        if (0 === $queriesCount) {
            $search->addQuery(new MatchAllQuery());
        } else {
            $search->addQuery($query);
        }

        if (null !== $pageSize) {
            $this->addPagination($search, $pageSize, $page, $limit);
        } elseif (null !== $limit) {
            $search->setSize($limit);
        }

        return $repository->execute($search);
    }

    /**
     * Add the pagination to given query.
     *
     * @param Search $search
     * @param int $pageSize
     * @param int $page
     * @param int $limit
     */
    private function addPagination(Search $search, $pageSize, $page, $limit)
    {
        $pageSize = intval($pageSize);
        $offset = ($page - 1) * $pageSize;

        $position = $pageSize * $page;
        if ($limit !== null && $position >= $limit) {
            $pageSize = $limit - $offset;
            $loadLimit = $pageSize;
        } else {
            $loadLimit = $pageSize;
        }

        $search->setSize($loadLimit);
        $search->setFrom($offset);
    }

    /**
     * Add a boolean-query if filter exists.
     *
     * @param string $filterName
     * @param array $filters
     * @param string $field
     * @param string $operator
     * @param BoolQuery $query
     * @param int $queriesCount
     */
    private function addBoolQuery($filterName, array $filters, $field, $operator, BoolQuery $query, &$queriesCount)
    {
        if (0 !== count($tags = $this->getFilter($filters, $filterName))) {
            ++$queriesCount;
            $query->add($this->getBoolQuery($field, $tags, $operator));
        }
    }

    /**
     * Returns boolean query for given fields and values.
     *
     * @param string $field
     * @param array $values
     * @param string $operator
     *
     * @return BoolQuery
     */
    private function getBoolQuery($field, array $values, $operator)
    {
        $type = ('or' === strtolower($operator) ? BoolQuery::SHOULD : BoolQuery::MUST);

        $query = new BoolQuery();
        foreach ($values as $value) {
            $query->add(new TermQuery($field, $value), $type);
        }

        return $query;
    }

    /**
     * Returns filter value.
     *
     * @param array $filters
     * @param string $name
     * @param mixed $default
     *
     * @return mixed
     */
    private function getFilter(array $filters, $name, $default = null)
    {
        if ($this->hasFilter($filters, $name)) {
            return $filters[$name];
        }

        return $default;
    }

    /**
     * Returns true if filter-value exists.
     *
     * @param array $filters
     * @param string $name
     *
     * @return bool
     */
    private function hasFilter(array $filters, $name)
    {
        return array_key_exists($name, $filters) && null !== $filters[$name];
    }

    /**
     * Returns Proxy document for uuid.
     *
     * @param string $uuid
     * @param string $locale
     *
     * @return object
     */
    private function getResource($uuid, $locale)
    {
        return $this->proxyFactory->createProxy(
            ArticleDocument::class,
            function (
                &$wrappedObject,
                LazyLoadingInterface $proxy,
                $method,
                array $parameters,
                &$initializer
            ) use ($uuid, $locale) {
                $initializer = null;
                $wrappedObject = $this->documentManager->find($uuid, $locale);

                return true;
            }
        );
    }
}
