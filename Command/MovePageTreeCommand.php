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
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Move articles from given parent-page to another.
 */
class MovePageTreeCommand extends ContainerAwareCommand
{
    /**
     * {@inheritdoc}
     */
    public function configure()
    {
        $this->setName('sulu:article:page-tree:move')
            ->addArgument('source-segment', InputArgument::REQUIRED)
            ->addArgument('destination-segment', InputArgument::REQUIRED)
            ->addArgument('webspace-key', InputArgument::REQUIRED)
            ->addArgument('locale', InputArgument::REQUIRED);
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $source = $input->getArgument('source-segment');
        $destination = $input->getArgument('destination-segment');
        $webspaceKey = $input->getArgument('webspace-key');
        $locale = $input->getArgument('locale');

        $mover = $this->getContainer()->get('sulu_article.page_tree_route.mover');
        $strategyPool = $this->getContainer()->get('sulu.content.resource_locator.strategy_pool');
        $documentManager = $this->getContainer()->get('sulu_document_manager.document_manager');
        $strategy = $strategyPool->getStrategyByWebspaceKey($webspaceKey);

        $destinationUuid = $strategy->loadByResourceLocator($destination, $webspaceKey, $locale);
        $document = $documentManager->find($destinationUuid, $locale);

        $mover->move($source, $document);

        $documentManager->flush();
    }
}
