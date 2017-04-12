<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\ArticleBundle\Document\Index;

use ONGR\ElasticsearchBundle\Service\Manager;
use Sulu\Bundle\ArticleBundle\Document\ArticleDocument;
use Sulu\Bundle\ArticleBundle\Document\Index\Factory\ExcerptFactory;
use Sulu\Bundle\ArticleBundle\Document\Index\Factory\SeoFactory;
use Sulu\Bundle\ContactBundle\Entity\ContactRepository;
use Sulu\Bundle\SecurityBundle\UserManager\UserManager;
use Sulu\Component\Content\Document\LocalizationState;
use Sulu\Component\Content\Metadata\Factory\StructureMetadataFactoryInterface;
use Sulu\Component\DocumentManager\DocumentManagerInterface;
use Sulu\Component\Localization\Localization;
use Sulu\Component\Webspace\Manager\WebspaceManagerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Translation\TranslatorInterface;

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

    /**
     * @param StructureMetadataFactoryInterface $structureMetadataFactory
     * @param UserManager $userManager
     * @param ContactRepository $contactRepository
     * @param DocumentFactoryInterface $documentFactory
     * @param Manager $manager
     * @param ExcerptFactory $excerptFactory
     * @param SeoFactory $seoFactory
     * @param EventDispatcherInterface $eventDispatcher
     * @param TranslatorInterface $translator
     * @param array $typeConfiguration
     * @param WebspaceManagerInterface $webspaceManager
     * @param DocumentManagerInterface $documentManager
     */
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
        array $typeConfiguration,
        WebspaceManagerInterface $webspaceManager,
        DocumentManagerInterface $documentManager
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
        $article = $this->createOrUpdateArticle($document, $document->getLocale());
        $this->createOrUpdateGhosts($document);
        $this->dispatchIndexEvent($document, $article);
        $this->manager->persist($article);
    }

    /**
     * @param ArticleDocument $document
     */
    private function createOrUpdateGhosts(ArticleDocument $document)
    {
        $documentLocale = $document->getLocale();
        /** @var Localization $localization */
        foreach ($this->webspaceManager->getAllLocalizations() as $localization) {
            $locale = $localization->getLocale();
            if ($documentLocale === $locale) {
                continue;
            }

            // Try index the article ghosts.
            $article = $this->createOrUpdateArticle(
                $this->documentManager->find(
                    $document->getUuid(),
                    $locale,
                    [
                        'load_ghost_content' => true,
                    ]
                ),
                $localization->getLocale(),
                LocalizationState::GHOST
            );

            if ($article) {
                $this->manager->persist($article);
            }
        }
    }
}
