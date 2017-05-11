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

use Doctrine\ORM\EntityManagerInterface;
use Sulu\Bundle\AutomationBundle\Entity\Task;
use Sulu\Bundle\AutomationBundle\Tasks\Manager\TaskManagerInterface;
use Sulu\Bundle\ContentBundle\Document\BasePageDocument;

/**
 * Schedules the route-update task.
 */
class AutomationPageTreeUpdater implements PageTreeUpdaterInterface
{
    /**
     * @var TaskManagerInterface
     */
    private $taskManager;

    /**
     * @var EntityManagerInterface
     */
    private $entityManager;

    /**
     * @param TaskManagerInterface $taskManager
     * @param EntityManagerInterface $entityManager
     */
    public function __construct(TaskManagerInterface $taskManager, EntityManagerInterface $entityManager)
    {
        $this->taskManager = $taskManager;
        $this->entityManager = $entityManager;
    }

    /**
     * {@inheritdoc}
     */
    public function update(BasePageDocument $document)
    {
        $task = new Task();
        $task->setEntityClass(BasePageDocument::class)
            ->setEntityId($document->getUuid())
            ->setLocale($document->getLocale())
            ->setHandlerClass(PageTreeRouteUpdateHandler::class)
            ->setSchedule(new \DateTime());

        $this->taskManager->create($task);
        $this->entityManager->flush();
    }
}
