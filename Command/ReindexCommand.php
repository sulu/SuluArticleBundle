<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\ArticleBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Reindixes articles.
 */
class ReindexCommand extends ContainerAwareCommand
{
    /**
     * {@inheritdoc}
     */
    public function configure()
    {
        $this->setName('sulu:article:index-rebuild')
            ->addArgument('locale', InputArgument::REQUIRED)
            ->addOption('clear', null, InputOption::VALUE_NONE);
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $indexer = $this->getContainer()->get('sulu_article.elastic_search.article_indexer');
        $documentManager = $this->getContainer()->get('sulu_document_manager.document_manager');
        $query = $documentManager->createQuery(
            'SELECT * FROM [nt:unstructured] AS a WHERE [jcr:mixinTypes] = "sulu:article"'
        );

        if ($input->getOption('clear')) {
            $indexer->clear();
        }

        $progessBar = new ProgressBar($output);
        $progessBar->setFormat('debug_nomax');
        $progessBar->start();

        $query->setLocale($input->getArgument('locale'));
        foreach ($query->execute() as $document) {
            $indexer->index($document);
            $progessBar->advance();
        }

        $indexer->flush();
        $progessBar->finish();
    }
}
