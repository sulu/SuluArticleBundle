<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\ArticleBundle\Document\Repository;

use ONGR\ElasticsearchBundle\Result\DocumentIterator;
use ONGR\ElasticsearchBundle\Service\Manager;
use ONGR\ElasticsearchBundle\Service\Repository;
use ONGR\ElasticsearchDSL\Query\Compound\BoolQuery;
use ONGR\ElasticsearchDSL\Query\Specialized\MoreLikeThisQuery;
use ONGR\ElasticsearchDSL\Query\TermLevel\TermQuery;
use ONGR\ElasticsearchDSL\Search;
use ONGR\ElasticsearchDSL\Sort\FieldSort;
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
     * @param string $articleDocumentClass
     */
    public function __construct(
        Manager $searchManager,
        $articleDocumentClass,
        array $searchFields
    ) {
        $this->searchManager = $searchManager;
        $this->articleDocumentClass = $articleDocumentClass;
        $this->repository = $this->searchManager->getRepository($this->articleDocumentClass);
        $this->searchFields = $searchFields;
    }

    /**
     * Finds recent articles for given parameters sorted by field `authored`.
     *
     * @param null|string $excludeUuid
     * @param int $limit
     * @param null|string $locale
     * @param null|string $webspaceKey
     *
     * @return DocumentIterator
     */
    public function findRecent(
        $excludeUuid = null,
        $limit = self::DEFAULT_LIMIT,
        array $types = null,
        $locale = null,
        $webspaceKey = null
    ) {
        $search = $this->createSearch($limit, $types, $locale, $webspaceKey);

        if ($excludeUuid) {
            $search->addQuery(new TermQuery('uuid', $excludeUuid), BoolQuery::MUST_NOT);
        }

        $search->addSort(new FieldSort('authored', FieldSort::DESC));

        return $this->repository->findDocuments($search);
    }

    /**
     * Finds similar articles for given `uuid` with given parameters.
     *
     * @param string $uuid
     * @param int $limit
     * @param null|string $locale
     * @param null|string $webspaceKey
     *
     * @return DocumentIterator
     */
    public function findSimilar(
        $uuid,
        $limit = self::DEFAULT_LIMIT,
        array $types = null,
        $locale = null,
        $webspaceKey = null
    ) {
        $search = $this->createSearch($limit, $types, $locale, $webspaceKey);

        $search->addQuery(
            new MoreLikeThisQuery(
                null,
                [
                    'fields' => $this->searchFields,
                    'min_term_freq' => 1,
                    'min_doc_freq' => 2,
                    'like' => [
                        [
                            '_id' => $this->getViewDocumentId($uuid, $locale),
                        ],
                    ],
                ]
            )
        );

        return $this->repository->findDocuments($search);
    }

    /**
     * Creates search with default queries (size, locale, types, webspace).
     *
     * @param int $limit
     * @param null|string $locale
     * @param null|string $webspaceKey
     *
     * @return Search
     */
    private function createSearch(
        $limit,
        array $types = null,
        $locale = null,
        $webspaceKey = null
    ) {
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

        // filter by webspace if provided
        if ($webspaceKey) {
            $webspaceQuery = new BoolQuery();

            // check for mainWebspace
            $webspaceQuery->add(new TermQuery('main_webspace', $webspaceKey), BoolQuery::SHOULD);

            // check for additionalWebspaces
            $webspaceQuery->add(new TermQuery('additional_webspaces', $webspaceKey), BoolQuery::SHOULD);

            $search->addQuery($webspaceQuery);
        }

        return $search;
    }
}
