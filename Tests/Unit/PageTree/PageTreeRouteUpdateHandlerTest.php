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
use PHPUnit\Framework\TestCase;
use Sulu\Bundle\ArticleBundle\PageTree\PageTreeRouteUpdateHandler;
use Sulu\Bundle\ArticleBundle\PageTree\PageTreeUpdaterInterface;
use Sulu\Bundle\PageBundle\Document\HomeDocument;
use Sulu\Bundle\PageBundle\Document\PageDocument;
use Sulu\Component\DocumentManager\DocumentManagerInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Task\Executor\RetryTaskHandlerInterface;

class PageTreeRouteUpdateHandlerTest extends TestCase
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
     * @var EntityManagerInterface
     */
    private $entityManager;

    /**
     * @var PageTreeRouteUpdateHandler
     */
    private $handler;

    protected function setUp()
    {
        $this->routeUpdater = $this->prophesize(PageTreeUpdaterInterface::class);
        $this->documentManager = $this->prophesize(DocumentManagerInterface::class);
        $this->entityManager = $this->prophesize(EntityManagerInterface::class);

        $this->handler = new PageTreeRouteUpdateHandler(
            $this->routeUpdater->reveal(),
            $this->documentManager->reveal(),
            $this->entityManager->reveal()
        );
    }

    public function testConfigureOptionsResolver()
    {
        $optionsResolver = new OptionsResolver();

        $result = $this->handler->configureOptionsResolver($optionsResolver);
        $this->assertEquals($optionsResolver, $result);
        $this->assertEquals(['id', 'locale'], $optionsResolver->getRequiredOptions());
    }

    public function testSupports()
    {
        $this->assertTrue($this->handler->supports(PageDocument::class));
        $this->assertTrue($this->handler->supports(HomeDocument::class));
        $this->assertFalse($this->handler->supports(\stdClass::class));
    }

    public function testGetConfiguration()
    {
        $result = $this->handler->getConfiguration();
        $this->assertEquals('sulu_article.update_route', $result->getTitle());
    }

    public function testHandle()
    {
        $document = $this->prophesize(PageDocument::class);

        $this->documentManager->find('123-123-123', 'de')->willReturn($document->reveal());
        $this->documentManager->flush()->shouldBeCalled();

        $this->entityManager->beginTransaction()->shouldBeCalled();
        $this->entityManager->commit()->shouldBeCalled();
        $this->entityManager->rollback()->shouldNotBeCalled();

        $this->routeUpdater->update($document->reveal())->shouldBeCalled();

        $this->handler->handle(['id' => '123-123-123', 'locale' => 'de']);
    }

    public function testHandleUpdateException()
    {
        $this->expectException(\InvalidArgumentException::class);

        $document = $this->prophesize(PageDocument::class);

        $this->documentManager->find('123-123-123', 'de')->willReturn($document->reveal());
        $this->documentManager->flush()->shouldNotBeCalled();

        $this->entityManager->beginTransaction()->shouldBeCalled();
        $this->entityManager->commit()->shouldNotBeCalled();
        $this->entityManager->rollback()->shouldBeCalled();

        $this->routeUpdater->update($document->reveal())->willThrow(new \InvalidArgumentException());

        $this->handler->handle(['id' => '123-123-123', 'locale' => 'de']);
    }

    public function testHandleDocumentExceptionException()
    {
        $this->expectException(\InvalidArgumentException::class);

        $document = $this->prophesize(PageDocument::class);

        $this->documentManager->find('123-123-123', 'de')->willReturn($document->reveal());
        $this->documentManager->flush()->willThrow(new \InvalidArgumentException());

        $this->entityManager->beginTransaction()->shouldBeCalled();
        $this->entityManager->commit()->shouldNotBeCalled();
        $this->entityManager->rollback()->shouldBeCalled();

        $this->routeUpdater->update($document->reveal())->shouldBeCalled();

        $this->handler->handle(['id' => '123-123-123', 'locale' => 'de']);
    }

    public function testGetMaximumAttempts()
    {
        $this->assertInstanceOf(RetryTaskHandlerInterface::class, $this->handler);
        $this->assertEquals(3, $this->handler->getMaximumAttempts());
    }
}
