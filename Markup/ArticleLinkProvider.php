<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\ArticleBundle\Markup;

use ONGR\ElasticsearchBundle\Service\Manager;
use ONGR\ElasticsearchDSL\Query\TermLevel\IdsQuery;
use ONGR\ElasticsearchDSL\Search;
use Sulu\Bundle\ArticleBundle\Document\ArticleViewDocumentInterface;
use Sulu\Bundle\ArticleBundle\Metadata\ArticleViewDocumentIdTrait;
use Sulu\Bundle\ContentBundle\Markup\Link\LinkConfiguration;
use Sulu\Bundle\ContentBundle\Markup\Link\LinkItem;
use Sulu\Bundle\ContentBundle\Markup\Link\LinkProviderInterface;

/**
 * Integrates articles into link-system.
 */
class ArticleLinkProvider implements LinkProviderInterface
{
    use ArticleViewDocumentIdTrait;

    /**
     * @var Manager
     */
    private $liveManager;

    /**
     * @var Manager
     */
    private $defaultManager;

    /**
     * @var array
     */
    private $types;

    /**
     * @var string
     */
    private $articleViewClass;

    /**
     * @param Manager $liveManager
     * @param Manager $defaultManager
     * @param array $types
     * @param string $articleViewClass
     */
    public function __construct(
        Manager $liveManager,
        Manager $defaultManager,
        array $types,
        $articleViewClass
    ) {
        $this->liveManager = $liveManager;
        $this->defaultManager = $defaultManager;
        $this->types = $types;
        $this->articleViewClass = $articleViewClass;
    }

    /**
     * {@inheritdoc}
     */
    public function getConfiguration()
    {
        $tabs = null;
        if (1 < count($this->types)) {
            $tabs = array_map(
                function ($type) {
                    return ['title' => $type['translation_key']];
                },
                $this->types
            );
        }

        return new LinkConfiguration(
            'sulu_article.ckeditor.link',
            'ckeditor/link/article@suluarticle',
            [],
            ['tabs' => $tabs]
        );
    }

    /**
     * {@inheritdoc}
     */
    public function preload(array $hrefs, $locale, $published = true)
    {
        $search = new Search();
        $search->addQuery(new IdsQuery($this->getViewDocumentIds($hrefs, $locale)));

        $repository = $this->liveManager->getRepository($this->articleViewClass);
        if (!$published) {
            $repository = $this->defaultManager->getRepository($this->articleViewClass);
        }

        $documents = $repository->findDocuments($search);

        $result = [];
        /** @var ArticleViewDocumentInterface $document */
        foreach ($documents as $document) {
            $result[] = new LinkItem(
                $document->getUuid(),
                $document->getTitle(),
                $document->getRoutePath(),
                $document->getPublishedState()
            );
        }

        return $result;
    }
}
