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
use ONGR\ElasticsearchDSL\Query\MatchAllQuery;
use Sulu\Bundle\ArticleBundle\Document\ArticleDocument;
use Sulu\Bundle\ArticleBundle\Document\Index\Factory\ExcerptFactory;
use Sulu\Bundle\ArticleBundle\Document\Index\Factory\SeoFactory;
use Sulu\Bundle\ArticleBundle\Event\Events;
use Sulu\Bundle\ArticleBundle\Event\IndexEvent;
use Sulu\Bundle\ArticleBundle\Metadata\ArticleTypeTrait;
use Sulu\Bundle\SecurityBundle\UserManager\UserManager;
use Sulu\Component\Content\Document\WorkflowStage;
use Sulu\Component\Content\Metadata\Factory\StructureMetadataFactoryInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Translation\TranslatorInterface;

/**
 * Provides methods to index articles.
 */
class ArticleIndexer implements IndexerInterface
{
    use ArticleTypeTrait;

    /**
     * @var StructureMetadataFactoryInterface
     */
    private $structureMetadataFactory;

    /**
     * @var UserManager
     */
    private $userManager;

    /**
     * @var DocumentFactoryInterface
     */
    private $documentFactory;

    /**
     * @var Manager
     */
    private $manager;

    /**
     * @var ExcerptFactory
     */
    private $excerptFactory;

    /**
     * @var SeoFactory
     */
    private $seoFactory;

    /**
     * @var EventDispatcherInterface
     */
    private $eventDispatcher;

    /**
     * @var TranslatorInterface
     */
    private $translator;

    /**
     * @var array
     */
    private $typeConfiguration;

    /**
     * ArticleIndexer constructor.
     *
     * @param StructureMetadataFactoryInterface $structureMetadataFactory
     * @param UserManager $userManager
     * @param Manager $manager
     * @param ExcerptFactory $excerptFactory
     * @param SeoFactory $seoFactory
     * @param DocumentFactoryInterface $documentFactory
     * @param EventDispatcherInterface $eventDispatcher
     * @param TranslatorInterface $translator
     * @param array $typeConfiguration
     */
    public function __construct(
        StructureMetadataFactoryInterface $structureMetadataFactory,
        UserManager $userManager,
        DocumentFactoryInterface $documentFactory,
        Manager $manager,
        ExcerptFactory $excerptFactory,
        SeoFactory $seoFactory,
        EventDispatcherInterface $eventDispatcher,
        TranslatorInterface $translator,
        array $typeConfiguration
    ) {
        $this->structureMetadataFactory = $structureMetadataFactory;
        $this->userManager = $userManager;
        $this->documentFactory = $documentFactory;
        $this->manager = $manager;
        $this->excerptFactory = $excerptFactory;
        $this->seoFactory = $seoFactory;
        $this->eventDispatcher = $eventDispatcher;
        $this->translator = $translator;
        $this->typeConfiguration = $typeConfiguration;
    }

    /**
     * {@inheritdoc}
     */
    public function clear()
    {
        $pageSize = 500;
        $repository = $this->manager->getRepository($this->documentFactory->getClass('article'));
        $search = $repository->createSearch()
            ->addQuery(new MatchAllQuery())
            ->setSize($pageSize);

        $count = $repository->count($repository->createSearch()->addQuery(new MatchAllQuery()));
        $maxPage = ceil($count / $pageSize);
        for ($page = 1; $page <= $maxPage; ++$page) {
            $search->setFrom(($page - 1) * $pageSize);
            foreach ($repository->execute($search) as $document) {
                $this->manager->remove($document);
            }

            $this->manager->commit();
        }

        $this->manager->clearCache();
        $this->manager->flush();
    }

    /**
     * {@inheritdoc}
     */
    public function setUnpublished($uuid)
    {
        $article = $this->manager->find($this->documentFactory->getClass('article'), $uuid);

        if (!$article) {
            return;
        }

        $article->setPublished(null);
        $article->setPublishedState(false);

        $this->manager->persist($article);
    }

    /**
     * {@inheritdoc}
     */
    public function index(ArticleDocument $document)
    {
        $article = $this->manager->find($this->documentFactory->getClass('article'), $document->getUuid());
        if (!$article) {
            $article = $this->documentFactory->create('article');
            $article->setUuid($document->getUuid());
        }

        $structureMetadata = $this->structureMetadataFactory->getStructureMetadata(
            'article',
            $document->getStructureType()
        );

        $article->setTitle($document->getTitle());
        $article->setRoutePath($document->getRoutePath());
        $article->setChanged($document->getChanged());
        $article->setCreated($document->getCreated());
        $article->setAuthored($document->getAuthored());
        $article->setAuthors($document->getAuthors());
        $article->setChangerFullName($this->userManager->getFullNameByUserId($document->getChanger()));
        $article->setCreatorFullName($this->userManager->getFullNameByUserId($document->getCreator()));
        $article->setType($this->getType($structureMetadata));
        $article->setStructureType($document->getStructureType());
        $article->setPublished($document->getPublished());
        $article->setPublishedState($document->getWorkflowStage() === WorkflowStage::PUBLISHED);
        $article->setTypeTranslation($this->getTypeTranslation($this->getType($structureMetadata)));

        $extensions = $document->getExtensionsData()->toArray();
        $article->setExcerpt($this->excerptFactory->create($extensions['excerpt'], $document->getLocale()));
        $article->setSeo($this->seoFactory->create($extensions['seo']));

        if ($structureMetadata->hasPropertyWithTagName('sulu.teaser.description')) {
            $descriptionProperty = $structureMetadata->getPropertyByTagName('sulu.teaser.description');
            $article->setTeaserDescription(
                $document->getStructure()->getProperty($descriptionProperty->getName())->getValue()
            );
        }
        if ($structureMetadata->hasPropertyWithTagName('sulu.teaser.media')) {
            $mediaProperty = $structureMetadata->getPropertyByTagName('sulu.teaser.media');
            $mediaData = $document->getStructure()->getProperty($mediaProperty->getName())->getValue();
            if (null !== $mediaData && array_key_exists('ids', $mediaData)) {
                $article->setTeaserMediaId(reset($mediaData['ids']) ?: null);
            }
        }

        $this->eventDispatcher->dispatch(Events::INDEX_EVENT, new IndexEvent($document, $article));

        $this->manager->persist($article);
    }

    /**
     * {@inheritdoc}
     */
    public function remove($document)
    {
        $article = $this->manager->find($this->documentFactory->getClass('article'), $document->getUuid());
        if (null === $article) {
            return;
        }

        $this->manager->remove($article);
    }

    /**
     * {@inheritdoc}
     */
    public function flush()
    {
        $this->manager->commit();
    }

    /**
     * Returns translation for given article type.
     *
     * @param string $type
     *
     * @return string
     */
    private function getTypeTranslation($type)
    {
        if (!array_key_exists($type, $this->typeConfiguration)) {
            return ucfirst($type);
        }

        $typeTranslationKey = $this->typeConfiguration[$type]['translation_key'];

        return $this->translator->trans(
            $typeTranslationKey,
            [],
            'backend'
        );
    }
}
