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
use Sulu\Bundle\SecurityBundle\UserManager\UserManager;

/**
 * Provides methods to index articles.
 */
class ArticleIndexer implements IndexerInterface
{
    /**
     * @var UserManager
     */
    private $userManager;

    /**
     * @var Manager
     */
    private $manager;

    /**
     * @param UserManager $userManager
     * @param Manager $manager
     */
    public function __construct(UserManager $userManager, Manager $manager)
    {
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

        $article->setTitle($document->getTitle());
        $article->setChanged($document->getChanged());
        $article->setCreated($document->getCreated());
        $article->setChanger($this->userManager->getFullNameByUserId($document->getChanger()));
        $article->setCreator($this->userManager->getFullNameByUserId($document->getCreator()));

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
