<?php

declare(strict_types=1);

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\ArticleBundle\Teaser;

use ONGR\ElasticsearchBundle\Service\Manager;
use ONGR\ElasticsearchDSL\Query\TermLevel\IdsQuery;
use Sulu\Bundle\ArticleBundle\Document\ArticleViewDocument;
use Sulu\Bundle\ArticleBundle\Metadata\ArticleViewDocumentIdTrait;
use Sulu\Bundle\PageBundle\Teaser\Configuration\TeaserConfiguration;
use Sulu\Bundle\PageBundle\Teaser\Provider\TeaserProviderInterface;
use Sulu\Bundle\PageBundle\Teaser\Teaser;
use Symfony\Component\Translation\TranslatorInterface;

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
     * @var TranslatorInterface
     */
    private $translator;

    /**
     * @var string
     */
    private $articleDocumentClass;

    public function __construct(Manager $searchManager, TranslatorInterface $translator, string $articleDocumentClass)
    {
        $this->searchManager = $searchManager;
        $this->translator = $translator;
        $this->articleDocumentClass = $articleDocumentClass;
    }

    /**
     * {@inheritdoc}
     */
    public function getConfiguration()
    {
        return new TeaserConfiguration(
            $this->translator->trans('sulu_article.article', [], 'admin'),
            'articles',
            'table',
            ['title'],
            $this->translator->trans('sulu_article.single_selection_overlay_title', [], 'admin')
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
                'articles',
                $item->getLocale(),
                ('' !== $excerpt->title ? $excerpt->title : $item->getTitle()),
                ('' !== $excerpt->description ? $excerpt->description : $item->getTeaserDescription()),
                $excerpt->more,
                $item->getRoutePath(),
                count($excerpt->images) ? $excerpt->images[0]->id : $item->getTeaserMediaId(),
                $this->getAttributes($item)
            );
        }

        return $result;
    }

    /**
     * Returns attributes for teaser.
     */
    protected function getAttributes(ArticleViewDocument $viewDocument): array
    {
        return [
            'structureType' => $viewDocument->getStructureType(),
            'type' => $viewDocument->getType(),
        ];
    }
}
