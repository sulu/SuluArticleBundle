<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\ArticleBundle\PageTree;

use Sulu\Bundle\AutomationBundle\TaskHandler\AutomationTaskHandlerInterface;
use Sulu\Bundle\AutomationBundle\TaskHandler\TaskHandlerConfiguration;
use Sulu\Bundle\ContentBundle\Document\BasePageDocument;
use Sulu\Component\DocumentManager\DocumentManagerInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Task-Handler to update page-tree-routes.
 */
class PageTreeRouteUpdateHandler implements AutomationTaskHandlerInterface
{
    /**
     * @var PageTreeUpdaterInterface
     */
    private $routeUpdater;

    /**
     * @var DocumentManagerInterface
     */
    private $documentManager;

    /**
     * @param PageTreeUpdaterInterface $routeUpdater
     * @param DocumentManagerInterface $documentManager
     */
    public function __construct(
        PageTreeUpdaterInterface $routeUpdater,
        DocumentManagerInterface $documentManager
    ) {
        $this->routeUpdater = $routeUpdater;
        $this->documentManager = $documentManager;
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptionsResolver(OptionsResolver $optionsResolver)
    {
        return $optionsResolver->setRequired(['id', 'locale'])
            ->setAllowedTypes('id', 'string')
            ->setAllowedTypes('locale', 'string');
    }

    /**
     * {@inheritdoc}
     */
    public function supports($entityClass)
    {
        return is_subclass_of($entityClass, BasePageDocument::class);
    }

    /**
     * {@inheritdoc}
     */
    public function getConfiguration()
    {
        return TaskHandlerConfiguration::create('sulu_article.update_route');
    }

    /**
     * {@inheritdoc}
     */
    public function handle($workload)
    {
        $this->routeUpdater->update($this->documentManager->find($workload['id'], $workload['locale']));

        $this->documentManager->flush();
    }
}
