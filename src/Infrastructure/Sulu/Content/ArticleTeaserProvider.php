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

namespace Sulu\Article\Infrastructure\Sulu\Content;

use Doctrine\ORM\EntityManagerInterface;
use Sulu\Article\Domain\Model\ArticleDimensionContentInterface;
use Sulu\Article\Domain\Model\ArticleInterface;
use Sulu\Bundle\ContentBundle\Content\Application\ContentManager\ContentManagerInterface;
use Sulu\Bundle\ContentBundle\Content\Application\ContentMetadataInspector\ContentMetadataInspectorInterface;
use Sulu\Bundle\ContentBundle\Content\Domain\Model\DimensionContentInterface;
use Sulu\Bundle\ContentBundle\Content\Infrastructure\Sulu\Teaser\ContentTeaserProvider;
use Sulu\Bundle\PageBundle\Teaser\Configuration\TeaserConfiguration;
use Sulu\Component\Content\Metadata\Factory\StructureMetadataFactoryInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * @extends ContentTeaserProvider<ArticleDimensionContentInterface, ArticleInterface>
 */
class ArticleTeaserProvider extends ContentTeaserProvider
{
    /**
     * @var TranslatorInterface
     */
    protected $translator;

    public function __construct(
        ContentManagerInterface $contentManager,
        EntityManagerInterface $entityManager,
        ContentMetadataInspectorInterface $contentMetadataInspector,
        StructureMetadataFactoryInterface $metadataFactory,
        TranslatorInterface $translator,
        bool $showDrafts,
    ) {
        parent::__construct($contentManager, $entityManager, $contentMetadataInspector, $metadataFactory, ArticleInterface::class, $showDrafts);

        $this->translator = $translator;
    }

    public function getConfiguration(): TeaserConfiguration
    {
        return new TeaserConfiguration(
            $this->translator->trans('sulu_article.article', [], 'admin'),
            $this->getResourceKey(),
            'table',
            ['title'],
            $this->translator->trans('sulu_article.single_selection_overlay_title', [], 'admin'),
        );
    }

    /**
     * @param array{
     *     article?: string|null,
     *     description?: string|null,
     * } $data
     */
    protected function getDescription(DimensionContentInterface $dimensionContent, array $data): ?string
    {
        $article = \strip_tags($data['article'] ?? '');

        return $article ?: parent::getDescription($dimensionContent, $data);
    }
}
