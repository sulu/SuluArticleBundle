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

namespace Sulu\Bundle\ArticleBundle\Tests\Unit\Routing;

use PHPUnit\Framework\TestCase;
use Sulu\Bundle\ArticleBundle\Document\ArticleDocument;
use Sulu\Bundle\ArticleBundle\Document\Resolver\WebspaceResolver;
use Sulu\Bundle\ArticleBundle\Routing\ArticleRouteEnhancer;
use Sulu\Component\Webspace\Analyzer\Attributes\RequestAttributes;
use Sulu\Component\Webspace\Manager\WebspaceManagerInterface;
use Sulu\Component\Webspace\Webspace;
use Symfony\Component\HttpFoundation\Request;

class ArticleRouteEnhancerTest extends TestCase
{
    /**
     * @var Webspace
     */
    private $webspace;

    /**
     * @var RequestAttributes
     */
    private $requestAttributes;

    /**
     * @var WebspaceManagerInterface
     */
    private $webspaceManager;

    /**
     * @var WebspaceResolver
     */
    private $webspaceResolver;

    /**
     * @var Request
     */
    private $request;

    /**
     * @var ArticleRouteEnhancer
     */
    private $articleRouteGenerator;

    /**
     * @var array
     */
    private $defaults = [];

    /**
     * @var ArticleDocument
     */
    private $article;

    /**
     * {@inheritdoc}
     */
    public function setUp(): void
    {
        $this->webspace = $this->prophesize(Webspace::class);
        $this->webspace->getKey()->willReturn('webspace_key');

        $this->requestAttributes = $this->prophesize(RequestAttributes::class);
        $this->requestAttributes->getAttribute('webspace')->willReturn($this->webspace->reveal());

        $this->request = $this->prophesize(Request::class);
        $this->request->get('_sulu')->willReturn($this->requestAttributes);

        $this->article = $this->prophesize(ArticleDocument::class);
        $this->article->getRoutePath()->willReturn('/route/path');
        $this->article->getLocale()->willReturn('locale');

        $this->defaults['object'] = $this->article->reveal();

        $this->webspaceManager = $this->prophesize(WebspaceManagerInterface::class);
        $this->webspaceManager->findUrlByResourceLocator(
            '/route/path',
            'env_value',
            'locale',
            'main_webspace_key'
        )->willReturn('main-webspace/route/path');

        $this->webspaceResolver = $this->prophesize(WebspaceResolver::class);
        $this->webspaceResolver->resolveAdditionalWebspaces($this->article)->willReturn(['webspace_key']);
        $this->webspaceResolver->resolveMainWebspace($this->article)->willReturn('main_webspace_key');

        $this->articleRouteGenerator = new ArticleRouteEnhancer(
            $this->webspaceManager->reveal(),
            $this->webspaceResolver->reveal(),
            'env_value'
        );
    }

    public function testEnhance()
    {
        $defaults = $this->articleRouteGenerator->enhance($this->defaults, $this->request->reveal());
        $this->assertArrayHasKey('_seo', $defaults);
        $this->assertTrue(is_array($defaults['_seo']));
        $this->assertArrayHasKey('canonicalUrl', $defaults['_seo']);
        $this->assertEquals('main-webspace/route/path', $defaults['_seo']['canonicalUrl']);
    }

    public function testEnhanceInvalidOne()
    {
        $this->webspaceResolver->resolveAdditionalWebspaces($this->article)->willReturn(['another_webspace_key']);

        $defaults = $this->articleRouteGenerator->enhance($this->defaults, $this->request->reveal());
        $this->assertArrayNotHasKey('_seo', $defaults);
    }
}
