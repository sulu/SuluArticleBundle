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

use Sulu\Bundle\ArticleBundle\PageTree\PageTreeRouteUpdateHandler;
use Sulu\Bundle\ArticleBundle\PageTree\PageTreeUpdaterInterface;
use Sulu\Bundle\ContentBundle\Document\HomeDocument;
use Sulu\Bundle\ContentBundle\Document\PageDocument;
use Sulu\Component\DocumentManager\DocumentManagerInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class PageTreeRouteUpdateHandlerTest extends \PHPUnit_Framework_TestCase
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
     * @var PageTreeRouteUpdateHandler
     */
    private $handler;

    protected function setUp()
    {
        $this->routeUpdater = $this->prophesize(PageTreeUpdaterInterface::class);
        $this->documentManager = $this->prophesize(DocumentManagerInterface::class);

        $this->handler = new PageTreeRouteUpdateHandler(
            $this->routeUpdater->reveal(),
            $this->documentManager->reveal()
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

        $this->routeUpdater->update($document->reveal())->shouldBeCalled();

        $this->handler->handle(['id' => '123-123-123', 'locale' => 'de']);
    }
}
