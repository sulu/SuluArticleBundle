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

use Elasticsearch\Common\Exceptions\NoNodesAvailableException;
use ONGR\ElasticsearchBundle\Service\Manager;
use ONGR\ElasticsearchDSL\Query\TermLevel\IdsQuery;
use Psr\Log\LoggerInterface;
use Sulu\Bundle\ArticleBundle\Document\ArticleViewDocumentInterface;
use Sulu\Bundle\ArticleBundle\Metadata\ArticleViewDocumentIdTrait;
use Sulu\Bundle\WebsiteBundle\ReferenceStore\ReferenceStoreInterface;
use Sulu\Component\Content\Compat\PropertyInterface;
use Sulu\Component\Content\PreResolvableContentTypeInterface;
use Sulu\Component\Content\SimpleContentType;

/**
 * Provides article_selection content-type.
 */
class ArticleSelectionContentType extends SimpleContentType implements PreResolvableContentTypeInterface
{
    use ArticleViewDocumentIdTrait;

    /**
     * @var Manager
     */
    private $searchManager;

    /**
     * @var ReferenceStoreInterface
     */
    private $referenceStore;

    /**
     * @var string
     */
    private $articleDocumentClass;

    /**
     * @var string
     */
    private $template;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @param Manager $searchManager
     * @param ReferenceStoreInterface $referenceStore
     * @param string $articleDocumentClass
     * @param string $template
     * @param LoggerInterface|null $logger
     */
    public function __construct(
        Manager $searchManager,
        ReferenceStoreInterface $referenceStore,
        $articleDocumentClass,
        $template,
        LoggerInterface $logger = null
    ) {
        parent::__construct('Article', []);

        $this->searchManager = $searchManager;
        $this->referenceStore = $referenceStore;
        $this->articleDocumentClass = $articleDocumentClass;
        $this->template = $template;
        $this->logger = $logger;
    }

    /**
     * {@inheritdoc}
     */
    public function getContentData(PropertyInterface $property)
    {
        $value = $property->getValue();
        if ($value === null || !is_array($value) || count($value) === 0) {
            return [];
        }

        $locale = $property->getStructure()->getLanguageCode();

        $repository = $this->searchManager->getRepository($this->articleDocumentClass);
        $search = $repository->createSearch();
        $search->addQuery(new IdsQuery($this->getViewDocumentIds($value, $locale)));

        try {
            $documents = $repository->findDocuments($search);
        } catch (NoNodesAvailableException $exception) {
            if ($this->logger) {
                $this->logger->error($exception->getMessage());
            }

            return [];
        }

        $result = [];
        /** @var ArticleViewDocumentInterface $articleDocument */
        foreach ($documents as $articleDocument) {
            $result[array_search($articleDocument->getUuid(), $value, false)] = $articleDocument;
        }

        ksort($result);

        return array_values($result);
    }

    /**
     * {@inheritdoc}
     */
    public function getTemplate()
    {
        return $this->template;
    }

    /**
     * {@inheritdoc}
     */
    public function preResolve(PropertyInterface $property)
    {
        $uuids = $property->getValue();
        if (!is_array($uuids)) {
            return;
        }

        foreach ($uuids as $uuid) {
            $this->referenceStore->add($uuid);
        }
    }
}
