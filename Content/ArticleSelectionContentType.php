<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\ArticleBundle\Content;

use ONGR\ElasticsearchBundle\Service\Manager;
use ONGR\ElasticsearchDSL\Query\TermLevel\IdsQuery;
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

    public function __construct(
        Manager $searchManager,
        ReferenceStoreInterface $referenceStore,
        string $articleDocumentClass
    ) {
        parent::__construct('Article', []);

        $this->searchManager = $searchManager;
        $this->referenceStore = $referenceStore;
        $this->articleDocumentClass = $articleDocumentClass;
    }

    public function getContentData(PropertyInterface $property)
    {
        $value = $property->getValue();
        if (null === $value || !is_array($value) || 0 === count($value)) {
            return [];
        }

        $locale = $property->getStructure()->getLanguageCode();

        $repository = $this->searchManager->getRepository($this->articleDocumentClass);
        $search = $repository->createSearch();
        $search->addQuery(new IdsQuery($this->getViewDocumentIds($value, $locale)));
        $search->setSize(count($value));

        $result = [];
        /** @var ArticleViewDocumentInterface $articleDocument */
        foreach ($repository->findDocuments($search) as $articleDocument) {
            $result[array_search($articleDocument->getUuid(), $value, false)] = $articleDocument;
        }

        ksort($result);

        return array_values($result);
    }

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
