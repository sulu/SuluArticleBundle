<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\ArticleBundle\Document\Index;

use ONGR\ElasticsearchBundle\Service\Manager;
use Sulu\Bundle\ArticleBundle\Document\ArticleDocument;
use Sulu\Bundle\ArticleBundle\Document\Index\Factory\ExcerptFactory;
use Sulu\Bundle\ArticleBundle\Document\Index\Factory\SeoFactory;
use Sulu\Bundle\ArticleBundle\Document\Resolver\WebspaceResolver;
use Sulu\Bundle\ContactBundle\Entity\ContactRepository;
use Sulu\Bundle\DocumentManagerBundle\Bridge\DocumentInspector;
use Sulu\Bundle\SecurityBundle\UserManager\UserManager;
use Sulu\Component\Content\Document\LocalizationState;
use Sulu\Component\Content\Metadata\Factory\StructureMetadataFactoryInterface;
use Sulu\Component\DocumentManager\DocumentManagerInterface;
use Sulu\Component\Localization\Localization;
use Sulu\Component\Webspace\Manager\WebspaceManagerInterface;
use Symfony\Component\Translation\TranslatorInterface;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

/**
 * Provides methods to index articles.
 */
class ArticleGhostIndexer extends ArticleIndexer
{
    /**
     * @var WebspaceManagerInterface
     */
    protected $webspaceManager;

    /**
     * @var DocumentManagerInterface
     */
    protected $documentManager;

    public function __construct(
        StructureMetadataFactoryInterface $structureMetadataFactory,
        UserManager $userManager,
        ContactRepository $contactRepository,
        DocumentFactoryInterface $documentFactory,
        Manager $manager,
        ExcerptFactory $excerptFactory,
        SeoFactory $seoFactory,
        EventDispatcherInterface $eventDispatcher,
        TranslatorInterface $translator,
        DocumentManagerInterface $documentManager,
        DocumentInspector $inspector,
        WebspaceResolver $webspaceResolver,
        array $typeConfiguration,
        WebspaceManagerInterface $webspaceManager
    ) {
        parent::__construct(
            $structureMetadataFactory,
            $userManager,
            $contactRepository,
            $documentFactory,
            $manager,
            $excerptFactory,
            $seoFactory,
            $eventDispatcher,
            $translator,
            $documentManager,
            $inspector,
            $webspaceResolver,
            $typeConfiguration
        );

        $this->webspaceManager = $webspaceManager;
        $this->documentManager = $documentManager;
    }

    /**
     * {@inheritdoc}
     */
    public function index(ArticleDocument $document)
    {
        if ($document->isShadowLocaleEnabled()) {
            $this->indexShadow($document);

            return;
        }

        $article = $this->createOrUpdateArticle($document, $document->getLocale());
        $this->createOrUpdateShadows($document);
        $this->createOrUpdateGhosts($document);
        $this->dispatchIndexEvent($document, $article);
        $this->manager->persist($article);
    }

    private function createOrUpdateGhosts(ArticleDocument $document)
    {
        $documentLocale = $document->getLocale();
        /** @var Localization $localization */
        foreach ($this->webspaceManager->getAllLocalizations() as $localization) {
            $locale = $localization->getLocale();
            if ($documentLocale === $locale) {
                continue;
            }

            /** @var ArticleDocument $ghostDocument */
            $ghostDocument = $this->documentManager->find(
                $document->getUuid(),
                $locale
            );

            $localizationState = $this->inspector->getLocalizationState($ghostDocument);

            // Only index ghosts
            if (LocalizationState::GHOST !== $localizationState) {
                continue;
            }

            // Try index the article ghosts.
            $article = $this->createOrUpdateArticle(
                $ghostDocument,
                $localization->getLocale(),
                LocalizationState::GHOST
            );

            if ($article) {
                $this->dispatchIndexEvent($ghostDocument, $article);
                $this->manager->persist($article);
            }
        }
    }
}
