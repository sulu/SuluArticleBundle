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
use ONGR\ElasticsearchDSL\Query\TermLevel\IdsQuery;
use Sulu\Bundle\ArticleBundle\Metadata\ArticleViewDocumentIdTrait;
use Sulu\Bundle\ContentBundle\Teaser\Configuration\TeaserConfiguration;
use Sulu\Bundle\ContentBundle\Teaser\Provider\TeaserProviderInterface;
use Sulu\Bundle\ContentBundle\Teaser\Teaser;

/**
 * Enables selection of articles in teaser content-type.
 */
class ArticleTeaserProvider implements TeaserProviderInterface
{
    use ArticleViewDocumentIdTrait;

    /**
     * @var Manager
     */
    private $searchManager;

    /**
     * @var string
     */
    private $articleDocumentClass;

    /**
     * @param Manager $searchManager
     * @param $articleDocumentClass
     */
    public function __construct(Manager $searchManager, $articleDocumentClass)
    {
        $this->searchManager = $searchManager;
        $this->articleDocumentClass = $articleDocumentClass;
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
                'url' => '/admin/api/articles?locale={locale}',
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

        $articleIds = $this->getViewDocumentIds($ids, $locale);

        $repository = $this->searchManager->getRepository($this->articleDocumentClass);
        $search = $repository->createSearch();
        $search->addQuery(new IdsQuery($articleIds));

        $result = [];
        foreach ($repository->findDocuments($search) as $item) {
            $excerpt = $item->getExcerpt();
            $result[] = new Teaser(
                $item->getUuid(),
                'article',
                $item->getLocale(),
                ('' !== $excerpt->title ? $excerpt->title : $item->getTitle()),
                ('' !== $excerpt->description ? $excerpt->description : $item->getTeaserDescription()),
                $excerpt->more,
                $item->getRoutePath(),
                count($excerpt->images) ? $excerpt->images[0]->id : $item->getTeaserMediaId(),
                [
                    'structureType' => $item->getStructureType(),
                    'type' => $item->getType(),
                ]
            );
        }

        return $result;
    }
}
