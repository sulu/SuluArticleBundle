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
use Sulu\Bundle\ArticleBundle\Metadata\ArticleViewDocumentIdTrait;
use Sulu\Bundle\WebsiteBundle\ReferenceStore\ReferenceStoreInterface;
use Sulu\Component\Content\Compat\PropertyInterface;
use Sulu\Component\Content\PreResolvableContentTypeInterface;
use Sulu\Component\Content\SimpleContentType;

/**
 * Provides article_selection content-type.
 */
class SingleArticleSelectionContentType extends SimpleContentType implements PreResolvableContentTypeInterface
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
        parent::__construct('Article');

        $this->searchManager = $searchManager;
        $this->referenceStore = $referenceStore;
        $this->articleDocumentClass = $articleDocumentClass;
    }

    /**
     * {@inheritdoc}
     */
    public function getContentData(PropertyInterface $property)
    {
        $uuid = $property->getValue();

        if (null === $uuid) {
            return null;
        }

        $repository = $this->searchManager->getRepository($this->articleDocumentClass);
        $locale = $property->getStructure()->getLanguageCode();

        return $repository->find($this->getViewDocumentId($uuid, $locale)) ?? null;
    }

    /**
     * {@inheritdoc}
     */
    public function preResolve(PropertyInterface $property)
    {
        $uuid = $property->getValue();
        if (null === $uuid) {
            return;
        }

        $this->referenceStore->add($uuid);
    }
}
