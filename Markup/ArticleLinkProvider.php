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
use ONGR\ElasticsearchDSL\Query\TermLevel\RangeQuery;
use ONGR\ElasticsearchDSL\Search;
use Sulu\Bundle\ArticleBundle\Document\ArticleViewDocumentInterface;
use Sulu\Bundle\ArticleBundle\Document\Index\DocumentFactory;
use Sulu\Bundle\ContentBundle\Markup\Link\LinkConfiguration;
use Sulu\Bundle\ContentBundle\Markup\Link\LinkItem;
use Sulu\Bundle\ContentBundle\Markup\Link\LinkProviderInterface;

/**
 * Integrates articles into link-system.
 */
class ArticleLinkProvider implements LinkProviderInterface
{
    /**
     * @var Manager
     */
    private $manager;

    /**
     * @var DocumentFactory
     */
    private $documentFactory;

    /**
     * @var array
     */
    private $types;

    /**
     * @param Manager         $manager
     * @param DocumentFactory $documentFactory
     * @param array           $types
     */
    public function __construct(Manager $manager, DocumentFactory $documentFactory, array $types)
    {
        $this->manager = $manager;
        $this->documentFactory = $documentFactory;
        $this->types = $types;
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
        $search->addQuery(new IdsQuery($hrefs));
        if ($published) {
            $search->addQuery(new RangeQuery('authored', ['lte' => 'now']));
        }

        $repository = $this->manager->getRepository($this->documentFactory->getClass('article'));
        $documents = $repository->findDocuments($search);

        $result = [];
        /** @var ArticleViewDocumentInterface $document */
        foreach ($documents as $document) {
            $result[] = new LinkItem(
                $document->getUuid(),
                $document->getTitle(),
                $document->getRoutePath(),
                $document->getAuthored() <= new \DateTime()
            );
        }

        return $result;
    }
}
