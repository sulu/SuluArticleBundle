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

namespace Sulu\Bundle\ArticleBundle\Builder;

use ONGR\ElasticsearchBundle\Service\Manager;
use Sulu\Bundle\CoreBundle\Build\SuluBuilder;

/**
 * Builder for article-index.
 */
class ArticleIndexBuilder extends SuluBuilder
{
    /** @var Manager */
    private $liveManager;

    /** @var Manager */
    private $defaultManager;

    public function __construct(Manager $liveManager, Manager $defaultManager)
    {
        $this->liveManager = $liveManager;
        $this->defaultManager = $defaultManager;
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'article_index';
    }

    /**
     * {@inheritdoc}
     */
    public function getDependencies()
    {
        return [];
    }

    /**
     * {@inheritdoc}
     */
    public function build()
    {
        $this->buildForManager($this->liveManager, $this->input->getOption('destroy'));
        $this->buildForManager($this->defaultManager, $this->input->getOption('destroy'));
    }

    /**
     * Build index for given manager.
     *
     * If index not exists - it will be created.
     * If index exists and destroy flag is true - drop and create index.
     * Else do nothing.
     */
    private function buildForManager(Manager $manager, bool $destroy): void
    {
        $name = $manager->getName();
        if (!$manager->indexExists()) {
            $this->output->writeln(sprintf('Create index for "<comment>%s</comment>" manager.', $name));
            $manager->createIndex();

            return;
        }

        if (!$destroy) {
            return;
        }

        $this->output->writeln(sprintf('Drop and create index for "<comment>%s</comment>" manager.', $name));
        $manager->dropAndCreateIndex();
    }
}
