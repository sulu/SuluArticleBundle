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
use Sulu\Bundle\ArticleBundle\Util\TypeTrait;
use Sulu\Bundle\SecurityBundle\UserManager\UserManager;
use Sulu\Component\Content\Compat\StructureManagerInterface;

/**
 * Provides methods to index articles.
 */
class ArticleIndexer implements IndexerInterface
{
    use TypeTrait;

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
     * @param StructureManagerInterface $structureManager
     * @param UserManager $userManager
     * @param Manager $manager
     */
    public function __construct(StructureManagerInterface $structureManager, UserManager $userManager, Manager $manager)
    {
        $this->structureManager = $structureManager;
        $this->userManager = $userManager;
        $this->manager = $manager;
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
        $article->setChanged($document->getChanged());
        $article->setCreated($document->getCreated());
        $article->setChanger($this->userManager->getFullNameByUserId($document->getChanger()));
        $article->setCreator($this->userManager->getFullNameByUserId($document->getCreator()));
        $article->setType($this->getType($structure->getStructure()));

        $this->manager->persist($article);
    }

    /**
     * {@inheritdoc}
     */
    public function remove($document)
    {
        $article = $this->manager->find(ArticleOngrDocument::class, $document->getUuid());

        $this->manager->remove($article);
    }

    /**
     * {@inheritdoc}
     */
    public function flush()
    {
        $this->manager->commit();
    }
}
