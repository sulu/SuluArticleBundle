<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\ArticleBundle\Document\Repository;

use Elasticsearch\Common\Exceptions\NoNodesAvailableException;
use ONGR\ElasticsearchBundle\Result\DocumentIterator;
use ONGR\ElasticsearchBundle\Service\Manager;
use ONGR\ElasticsearchBundle\Service\Repository;
use ONGR\ElasticsearchDSL\Query\Compound\BoolQuery;
use ONGR\ElasticsearchDSL\Query\Specialized\MoreLikeThisQuery;
use ONGR\ElasticsearchDSL\Query\TermLevel\TermQuery;
use ONGR\ElasticsearchDSL\Search;
use ONGR\ElasticsearchDSL\Sort\FieldSort;
use Psr\Log\LoggerInterface;
use Sulu\Bundle\ArticleBundle\Metadata\ArticleViewDocumentIdTrait;
use Sulu\Component\DocumentManager\DocumentManagerInterface;

/**
 * Find article view documents in elasticsearch index.
 */
class ArticleViewDocumentRepository
{
    use ArticleViewDocumentIdTrait;

    const DEFAULT_LIMIT = 5;

    /**
     * @var Manager
     */
    protected $searchManager;

    /**
     * @var DocumentManagerInterface
     */
    protected $documentManager;

    /**
     * @var string
     */
    protected $articleDocumentClass;

    /**
     * @var Repository
     */
    protected $repository;

    /**
     * @var array
     */
    protected $searchFields;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @param Manager $searchManager
     * @param string $articleDocumentClass
     * @param array $searchFields
     * @param LoggerInterface|null $logger
     */
    public function __construct(
        Manager $searchManager,
        $articleDocumentClass,
        array $searchFields,
        LoggerInterface $logger = null
    ) {
        $this->searchManager = $searchManager;
        $this->articleDocumentClass = $articleDocumentClass;
        $this->searchFields = $searchFields;
        $this->logger = $logger;

        $this->repository = $this->searchManager->getRepository($this->articleDocumentClass);
    }

    /**
     * Finds recent articles for given parameters sorted by field `authored`.
     *
     * @param null|string $excludeUuid
     * @param int $limit
     * @param null|array $types
     * @param null|string $locale
     *
     * @return DocumentIterator
     */
    public function findRecent($excludeUuid = null, $limit = self::DEFAULT_LIMIT, array $types = null, $locale = null)
    {
        $search = $this->createSearch($limit, $types, $locale);

        if ($excludeUuid) {
            $search->addQuery(new TermQuery('uuid', $excludeUuid), BoolQuery::MUST_NOT);
        }

        $search->addSort(new FieldSort('authored', FieldSort::DESC));

        try {
            return $this->repository->findDocuments($search);
        } catch (NoNodesAvailableException $exception) {
            if ($this->logger) {
                $this->logger->error($exception->getMessage());
            }

            return new DocumentIterator([], $this->searchManager);
        }
    }

    /**
     * Finds similar articles for given `uuid` with given parameters.
     *
     * @param string $uuid
     * @param int $limit
     * @param null|array $types
     * @param null|string $locale
     *
     * @return DocumentIterator
     */
    public function findSimilar($uuid, $limit = self::DEFAULT_LIMIT, array $types = null, $locale = null)
    {
        $search = $this->createSearch($limit, $types, $locale);

        $search->addQuery(
            new MoreLikeThisQuery(
                null,
                [
                    'fields' => $this->searchFields,
                    'min_term_freq' => 1,
                    'min_doc_freq' => 2,
                    'ids' => [$this->getViewDocumentId($uuid, $locale)],
                ]
            )
        );

        try {
            return $this->repository->findDocuments($search);
        } catch (NoNodesAvailableException $exception) {
            if ($this->logger) {
                $this->logger->error($exception->getMessage());
            }

            return new DocumentIterator([], $this->searchManager);
        }
    }

    /**
     * Creates search with default queries (size, locale, types).
     *
     * @param int $limit
     * @param null|array $types
     * @param null|string $locale
     *
     * @return Search
     */
    private function createSearch($limit, array $types = null, $locale = null)
    {
        $search = $this->repository->createSearch();

        // set size
        $search->setSize($limit);

        // filter by locale if provided
        if ($locale) {
            $search->addQuery(new TermQuery('locale', $locale), BoolQuery::FILTER);
        }

        // filter by types if provided
        if ($types) {
            $typesQuery = new BoolQuery();
            foreach ($types as $type) {
                $typesQuery->add(new TermQuery('type', $type), BoolQuery::SHOULD);
            }
            $search->addQuery($typesQuery);
        }

        return $search;
    }
}
