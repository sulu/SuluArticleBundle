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
use Sulu\Bundle\ArticleBundle\Document\ArticleOngrDocument;
use Sulu\Bundle\ArticleBundle\Document\ExcerptOngrObject;
use Sulu\Bundle\ArticleBundle\Document\MediaCollectionOngrObject;
use Sulu\Bundle\ArticleBundle\Document\SeoOngrObject;
use Sulu\Bundle\ArticleBundle\Metadata\ArticleTypeTrait;
use Sulu\Bundle\SecurityBundle\UserManager\UserManager;
use Sulu\Bundle\TagBundle\Tag\TagManagerInterface;
use Sulu\Component\Content\Compat\StructureManagerInterface;

/**
 * Provides methods to index articles.
 */
class ArticleIndexer implements IndexerInterface
{
    use ArticleTypeTrait;

    /**
     * @var StructureManagerInterface
     */
    private $structureManager;

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
     * @param StructureManagerInterface $structureManager
     * @param UserManager $userManager
     * @param Manager $manager
     * @param TagManagerInterface $tagManager
     */
    public function __construct(
        StructureManagerInterface $structureManager,
        UserManager $userManager,
        Manager $manager,
        TagManagerInterface $tagManager
    ) {
        $this->structureManager = $structureManager;
        $this->userManager = $userManager;
        $this->manager = $manager;
        $this->tagManager = $tagManager;
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
        $article = $this->manager->find(ArticleOngrDocument::class, $document->getUuid());
        if (!$article) {
            $article = new ArticleOngrDocument($document->getUuid());
        }

        $structure = $this->structureManager->getStructure($document->getStructureType(), 'article');

        $article->setTitle($document->getTitle());
        $article->setRoutePath($document->getRoutePath());
        $article->setChanged($document->getChanged());
        $article->setCreated($document->getCreated());
        $article->setAuthored($document->getAuthored());
        $article->setAuthors($document->getAuthors());
        $article->setChanger($this->userManager->getFullNameByUserId($document->getChanger()));
        $article->setCreator($this->userManager->getFullNameByUserId($document->getCreator()));
        $article->setType($this->getType($structure->getStructure()));
        $article->setStructureType($document->getStructureType());

        $extensions = $document->getExtensionsData()->toArray();
        $article->setExcerpt($this->createExcerptObject($extensions['excerpt']));
        $article->setSeo($this->createSeoObject($extensions['seo']));

        if ($structure->hasTag('sulu.teaser.description')) {
            $descriptionProperty = $structure->getPropertyByTagName('sulu.teaser.description');
            $article->setTeaserDescription(
                $document->getStructure()->getProperty($descriptionProperty->getName())->getValue()
            );
        }
        if ($structure->hasTag('sulu.teaser.media')) {
            $mediaProperty = $structure->getPropertyByTagName('sulu.teaser.media');
            $mediaData = $document->getStructure()->getProperty($mediaProperty->getName())->getValue();
            if (null !== $mediaData && array_key_exists('ids', $mediaData)) {
                $article->setTeaserMediaId(reset($mediaData['ids']) ?: null);
            }
        }

        $this->manager->persist($article);
    }

    /**
     * {@inheritdoc}
     */
    public function remove($document)
    {
        $article = $this->manager->find(ArticleOngrDocument::class, $document->getUuid());
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
     * @return SeoOngrObject
     */
    private function createSeoObject(array $data)
    {
        $seo = new SeoOngrObject();
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
     *
     * @return ExcerptOngrObject
     */
    private function createExcerptObject(array $data)
    {
        $excerpt = new ExcerptOngrObject();
        $excerpt->title = $data['title'];
        $excerpt->more = $data['more'];
        $excerpt->description = $data['description'];
        $excerpt->categories = $data['categories'];
        $excerpt->tags = $this->tagManager->resolveTagNames($data['tags']);
        $excerpt->title = $data['title'];
        $excerpt->icon = $this->createMediaCollectionObject($data['icon']);
        $excerpt->images = $this->createMediaCollectionObject($data['images']);

        return $excerpt;
    }

    private function createMediaCollectionObject(array $data)
    {
        $mediaCollection = new MediaCollectionOngrObject();
        if (array_key_exists('ids', $data)) {
            $mediaCollection->ids = $data['ids'];
        }
        if (array_key_exists('displayOption', $data)) {
            $mediaCollection->displayOption = $data['displayOption'];
        }

        return $mediaCollection;
    }
}
