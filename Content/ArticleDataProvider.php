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
use ONGR\ElasticsearchDSL\Sort\FieldSort;
use ProxyManager\Factory\LazyLoadingValueHolderFactory;
use ProxyManager\Proxy\LazyLoadingInterface;
use Sulu\Bundle\ArticleBundle\Document\ArticleDocument;
use Sulu\Bundle\ArticleBundle\Document\ArticleViewDocumentInterface;
use Sulu\Component\Content\Compat\PropertyParameter;
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
     * @var string
     */
    private $articleDocumentClass;

    /**
     * @param Manager $searchManager
     * @param DocumentManagerInterface $documentManager
     * @param LazyLoadingValueHolderFactory $proxyFactory
     * @param $articleDocumentClass
     */
    public function __construct(
        Manager $searchManager,
        DocumentManagerInterface $documentManager,
        LazyLoadingValueHolderFactory $proxyFactory,
        $articleDocumentClass
    ) {
        $this->searchManager = $searchManager;
        $this->documentManager = $documentManager;
        $this->proxyFactory = $proxyFactory;
        $this->articleDocumentClass = $articleDocumentClass;
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
            ->enableSorting(
                [
                    ['column' => 'published', 'title' => 'sulu_article.smart-content.published'],
                    ['column' => 'authored', 'title' => 'sulu_article.smart-content.authored'],
                    ['column' => 'created', 'title' => 'sulu_article.smart-content.created'],
                    ['column' => 'title', 'title' => 'sulu_article.smart-content.title'],
                    ['column' => 'author_full_name', 'title' => 'sulu_article.smart-content.author-full-name'],
                ]
            )
            ->getConfiguration();
    }

    /**
     * {@inheritdoc}
     */
    public function getDefaultPropertyParameter()
    {
        return ['type' => new PropertyParameter('type', null)];
    }

    private function getTypesProperty($propertyParameter)
    {
        $filterTypes = [];
        if (array_key_exists('types', $propertyParameter) && null !== ($types = $propertyParameter['types']->getValue())) {
            foreach ($types as $type) {
                $filterTypes[] = $type->getName();
            }
        }

        return $filterTypes;
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
        $filters['types'] = $this->getTypesProperty($propertyParameter);

        $queryResult = $this->getSearchResult($filters, $limit, $page, $pageSize, $options['locale']);

        $result = [];
        $uuids = [];
        /** @var ArticleViewDocumentInterface $document */
        foreach ($queryResult as $document) {
            $uuids[] = $document->getUuid();
            $result[] = new ArticleDataItem($document->getUuid(), $document->getTitle(), $document);
        }

        return new DataProviderResult($result, $this->hasNextPage($queryResult, $limit, $page, $pageSize), $uuids);
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
        $filters['types'] = $this->getTypesProperty($propertyParameter);

        $queryResult = $this->getSearchResult($filters, $limit, $page, $pageSize, $options['locale']);

        $result = [];
        $uuids = [];
        /** @var ArticleViewDocumentInterface $document */
        foreach ($queryResult as $document) {
            $uuids[] = $document->getUuid();
            $result[] = new ArticleResourceItem(
                $document,
                $this->getResource($document->getUuid(), $document->getLocale())
            );
        }

        return new DataProviderResult($result, $this->hasNextPage($queryResult, $limit, $page, $pageSize), $uuids);
    }

    /**
     * {@inheritdoc}
     */
    public function resolveDatasource($datasource, array $propertyParameter, array $options)
    {
        return;
    }

    /**
     * Returns flag "hasNextPage".
     * It combines the limit/query-count with the page and page-size.
     *
     * @param DocumentIterator $queryResult
     * @param int $limit
     * @param int $page
     * @param int $pageSize
     *
     * @return bool
     */
    private function hasNextPage(DocumentIterator $queryResult, $limit, $page, $pageSize)
    {
        $count = $queryResult->count();
        if ($limit && $limit < $count) {
            $count = $limit;
        }

        return $count > ($page * $pageSize);
    }

    /**
     * Creates search for filters and returns search-result.
     *
     * @param array $filters
     * @param int $limit
     * @param int $page
     * @param int $pageSize
     * @param string $locale
     *
     * @return DocumentIterator
     */
    private function getSearchResult(array $filters, $limit, $page, $pageSize, $locale)
    {
        $repository = $this->searchManager->getRepository($this->articleDocumentClass);
        /** @var Search $search */
        $search = $repository->createSearch();

        $query = new BoolQuery();

        $queriesCount = 0;
        $operator = $this->getFilter($filters, 'tagOperator', 'or');
        $this->addBoolQuery('tags', $filters, 'excerpt.tags.id', $operator, $query, $queriesCount);
        $operator = $this->getFilter($filters, 'websiteTagsOperator', 'or');
        $this->addBoolQuery('websiteTags', $filters, 'excerpt.tags.id', $operator, $query, $queriesCount);

        $operator = $this->getFilter($filters, 'categoryOperator', 'or');
        $this->addBoolQuery('categories', $filters, 'excerpt.categories.id', $operator, $query, $queriesCount);
        $operator = $this->getFilter($filters, 'websiteCategoriesOperator', 'or');
        $this->addBoolQuery('websiteCategories', $filters, 'excerpt.categories.id', $operator, $query, $queriesCount);

        if (null !== $locale) {
            $search->addQuery(new TermQuery('locale', $locale));
        }

        if (array_key_exists('types', $filters) && $filters['types']) {
            $typesQuery = new BoolQuery();
            foreach ($filters['types'] as $typeFilter) {
                $typesQuery->add(new TermQuery('type', $typeFilter), BoolQuery::SHOULD);
            }
            $search->addQuery($typesQuery);
        }

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

        if (array_key_exists('sortBy', $filters) && is_array($filters['sortBy'])) {
            $sortMethod = array_key_exists('sortMethod', $filters) ? $filters['sortMethod'] : 'asc';
            $this->appendSortBy($filters['sortBy'], $sortMethod, $search);
        }

        return $repository->execute($search);
    }

    /**
     * Extension point to append order.
     *
     * @param array $sortBy
     * @param string $sortMethod
     * @param Search $search
     *
     * @return array parameters for query
     */
    private function appendSortBy($sortBy, $sortMethod, $search)
    {
        foreach ($sortBy as $column) {
            $search->addSort(new FieldSort($column, $sortMethod));
        }
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
