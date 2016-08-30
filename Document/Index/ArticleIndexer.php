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
use Sulu\Bundle\ArticleBundle\Document\ExcerptViewObject;
use Sulu\Bundle\ArticleBundle\Document\MediaCollectionViewObject;
use Sulu\Bundle\ArticleBundle\Document\SeoViewObject;
use Sulu\Bundle\ArticleBundle\Event\Events;
use Sulu\Bundle\ArticleBundle\Event\IndexEvent;
use Sulu\Bundle\ArticleBundle\Metadata\ArticleTypeTrait;
use Sulu\Bundle\MediaBundle\Media\Manager\MediaManagerInterface;
use Sulu\Bundle\SecurityBundle\UserManager\UserManager;
use Sulu\Bundle\TagBundle\Tag\TagManagerInterface;
use Sulu\Component\Content\Metadata\Factory\StructureMetadataFactoryInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

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
     * @var Manager
     */
    private $manager;

    /**
     * @var TagManagerInterface
     */
    private $tagManager;

    /**
     * @var MediaManagerInterface
     */
    private $mediaManager;

    /**
     * @var DocumentFactoryInterface
     */
    private $documentFactory;

    /**
     * @var EventDispatcherInterface
     */
    private $eventDispatcher;

    /**
     * @param StructureMetadataFactoryInterface $structureMetadataFactory
     * @param UserManager $userManager
     * @param DocumentFactoryInterface $documentFactory
     * @param Manager $manager
     * @param TagManagerInterface $tagManager
     * @param MediaManagerInterface $mediaManager
     * @param EventDispatcherInterface $eventDispatcher
     */
    public function __construct(
        StructureMetadataFactoryInterface $structureMetadataFactory,
        UserManager $userManager,
        DocumentFactoryInterface $documentFactory,
        Manager $manager,
        TagManagerInterface $tagManager,
        MediaManagerInterface $mediaManager,
        EventDispatcherInterface $eventDispatcher
    ) {
        $this->structureMetadataFactory = $structureMetadataFactory;
        $this->userManager = $userManager;
        $this->documentFactory = $documentFactory;
        $this->manager = $manager;
        $this->tagManager = $tagManager;
        $this->mediaManager = $mediaManager;
        $this->eventDispatcher = $eventDispatcher;
    }

    /**
     * {@inheritdoc}
     */
    public function clear()
    {
        $this->manager->dropAndCreateIndex();
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
        $article->setChanger($this->userManager->getFullNameByUserId($document->getChanger()));
        $article->setCreator($this->userManager->getFullNameByUserId($document->getCreator()));
        $article->setType($this->getType($structureMetadata));
        $article->setStructureType($document->getStructureType());

        $extensions = $document->getExtensionsData()->toArray();
        $article->setExcerpt($this->createExcerptObject($extensions['excerpt'], $document->getLocale()));
        $article->setSeo($this->createSeoObject($extensions['seo']));

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
     * Create a seo object by given data.
     *
     * @param array $data
     *
     * @return SeoViewObject
     */
    private function createSeoObject(array $data)
    {
        $seo = new SeoViewObject();
        $seo->title = $data['title'];
        $seo->description = $data['description'];
        $seo->keywords = $data['keywords'];
        $seo->canonicalUrl = $data['canonicalUrl'];
        $seo->noIndex = $data['noIndex'];
        $seo->noFollow = $data['noFollow'];

        return $seo;
    }

    /**
     * Create a excerpt object by given data.
     *
     * @param array $data
     * @param string $locale
     *
     * @return ExcerptViewObject
     */
    private function createExcerptObject(array $data, $locale)
    {
        $excerpt = new ExcerptViewObject();
        $excerpt->title = $data['title'];
        $excerpt->more = $data['more'];
        $excerpt->description = $data['description'];
        $excerpt->categories = $data['categories'];
        $excerpt->tags = $this->tagManager->resolveTagNames($data['tags']);
        $excerpt->icon = $this->createMediaCollectionObject($data['icon'], $locale);
        $excerpt->images = $this->createMediaCollectionObject($data['images'], $locale);

        return $excerpt;
    }

    private function createMediaCollectionObject(array $data, $locale)
    {
        $mediaCollection = new MediaCollectionViewObject();
        if (array_key_exists('ids', $data)) {
            $medias = $this->mediaManager->getByIds($data['ids'], $locale);
            $mediaCollection->setData($medias, $data['displayOption']);
        }

        return $mediaCollection;
    }
}
