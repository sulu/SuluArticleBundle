<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\ArticleBundle\Teaser;

use ONGR\ElasticsearchBundle\Service\Manager;
use ONGR\ElasticsearchDSL\Query\IdsQuery;
use Sulu\Bundle\ArticleBundle\Document\ArticleOngrDocument;
use Sulu\Bundle\ContentBundle\Teaser\Configuration\TeaserConfiguration;
use Sulu\Bundle\ContentBundle\Teaser\Provider\TeaserProviderInterface;
use Sulu\Bundle\ContentBundle\Teaser\Teaser;

/**
 * Enables selection of articles in teaser content-type.
 */
class ArticleTeaserProvider implements TeaserProviderInterface
{
    /**
     * @var Manager
     */
    private $searchManager;

    /**
     * @param Manager $searchManager
     */
    public function __construct(Manager $searchManager)
    {
        $this->searchManager = $searchManager;
    }

    /**
     * {@inheritdoc}
     */
    public function getConfiguration()
    {
        return new TeaserConfiguration(
            'sulu_article.teaser',
            'teaser-selection/list@sulucontent',
            [
                'url' => '/admin/api/articles',
                'resultKey' => 'articles',
                'searchFields' => ['title', 'type'],
                'matchings' => [
                    [
                        'content' => 'public.title',
                        'name' => 'title',
                    ],
                    [
                        'content' => 'public.type',
                        'name' => 'typeTranslation',
                        'type' => 'translation',
                    ],
                ],
            ]
        );
    }

    /**
     * {@inheritdoc}
     */
    public function find(array $ids, $locale)
    {
        if (0 === count($ids)) {
            return [];
        }

        $repository = $this->searchManager->getRepository(ArticleOngrDocument::class);
        $search = $repository->createSearch();
        $search->addQuery(new IdsQuery($ids));

        $result = [];
        /** @var ArticleOngrDocument $item */
        foreach ($repository->execute($search) as $item) {
            $excerpt = $item->getExcerpt();
            $result[] = new Teaser(
                $item->getUuid(),
                $item->getType(),
                $item->getLocale(),
                ('' !== $excerpt->title ? $excerpt->title : $item->getTitle()),
                ('' !== $excerpt->description ? $excerpt->description : $item->getTeaserDescription()),
                $excerpt->more,
                $item->getRoutePath(),
                reset($excerpt->images->ids) ?: $item->getTeaserMediaId()
            );
        }

        return $result;
    }
}
