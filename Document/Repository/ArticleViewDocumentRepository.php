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

class ArticleViewDocumentRepository
{
    use ArticleViewDocumentIdTrait;

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
     * @param Manager $searchManager
     * @param string $articleDocumentClass
     * @param array $searchFields
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
     * Finds recent articles for given parameters sorted by field `published`.
     *
     * @param null|string $excludeUuid
     * @param int $limit
     * @param null|array $types
     * @param string $locale
     *
     * @return DocumentIterator
     */
    public function findRecent($excludeUuid = null, $limit, array $types = null, $locale)
    {
        $search = $this->createSearch($limit, $types, $locale);

        if ($excludeUuid) {
            $search->addQuery(new TermQuery('uuid', $excludeUuid), BoolQuery::MUST_NOT);
        }

        $search->addSort(new FieldSort('published'));

        return $this->repository->findDocuments($search);
    }

    /**
     * Finds similar articles for given `uuid` with given parameters.
     *
     * @param string $uuid
     * @param int $limit
     * @param null|array $types
     * @param string $locale
     *
     * @return DocumentIterator
     */
    public function findSimilar($uuid, $limit, array $types = null, $locale)
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

        return $this->repository->findDocuments($search);
    }

    /**
     * @param int $limit
     * @param null|array $types
     * @param string $locale
     *
     * @return Search
     */
    private function createSearch($limit, array $types = null, $locale)
    {
        $search = $this->repository->createSearch();

        $search->addQuery(new TermQuery('locale', $locale), BoolQuery::FILTER);
        $search->setSize($limit);

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
