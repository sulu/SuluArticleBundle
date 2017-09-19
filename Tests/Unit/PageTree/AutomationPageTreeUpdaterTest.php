<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\ArticleBundle\Tests\Unit\PageTree;

use Doctrine\ORM\EntityManagerInterface;
use Prophecy\Argument;
use Sulu\Bundle\ArticleBundle\PageTree\AutomationPageTreeUpdater;
use Sulu\Bundle\ArticleBundle\PageTree\PageTreeRouteUpdateHandler;
use Sulu\Bundle\ArticleBundle\PageTree\PageTreeUpdaterInterface;
use Sulu\Bundle\AutomationBundle\Tasks\Manager\TaskManagerInterface;
use Sulu\Bundle\AutomationBundle\Tasks\Model\TaskInterface;
use Sulu\Bundle\ContentBundle\Document\BasePageDocument;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

class AutomationPageTreeUpdaterTest extends \PHPUnit_Framework_TestCase
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
     * @var RequestStack
     */
    private $requestStack;

    /**
     * @var PageTreeUpdaterInterface
     */
    private $updater;

    /**
     * @var Request
     */
    private $request;

    protected function setUp()
    {
        $this->taskManager = $this->prophesize(TaskManagerInterface::class);
        $this->entityManager = $this->prophesize(EntityManagerInterface::class);
        $this->requestStack = $this->prophesize(RequestStack::class);

        $this->updater = new AutomationPageTreeUpdater($this->taskManager->reveal(), $this->entityManager->reveal(), $this->requestStack->reveal());

        $this->request = $this->prophesize(Request::class);
        $this->request->getScheme()->willReturn('http');
        $this->request->getHost()->willReturn('sulu.io');

        $this->requestStack->getCurrentRequest()->willReturn($this->request->reveal());
    }

    public function testUpdate()
    {
        $document = $this->prophesize(BasePageDocument::class);
        $document->getUuid()->willReturn('123-123-123');
        $document->getLocale()->willReturn('de');

        $this->updater->update($document->reveal());

        $this->taskManager->create(
            Argument::that(
                function (TaskInterface $task) {
                    return $task->getEntityClass() === BasePageDocument::class
                        && $task->getEntityId() === '123-123-123'
                        && $task->getLocale() === 'de'
                        && $task->getHandlerClass() === PageTreeRouteUpdateHandler::class
                        && $task->getSchedule() <= new \DateTime()
                        && $task->getHost() === 'sulu.io'
                        && $task->getScheme() === 'http';
                }
            )
        )->shouldBeCalled();
        $this->entityManager->flush()->shouldBeCalled();
    }
}
