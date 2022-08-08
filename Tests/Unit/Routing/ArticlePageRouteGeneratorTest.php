<?php

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
use Sulu\Bundle\ArticleBundle\Routing\ArticlePageRouteGenerator;
use Sulu\Bundle\RouteBundle\Generator\RouteGeneratorInterface;
use Sulu\Bundle\RouteBundle\Generator\TokenProviderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ArticlePageRouteGeneratorTest extends TestCase
{
    /**
     * @var string
     */
    private $routeSchema = '/page-{entity.getPageNumber()}';

    /**
     * @var RouteGeneratorInterface
     */
    private $routeGenerator;

    /**
     * @var TokenProviderInterface
     */
    private $tokenProvider;

    /**
     * @var ArticlePageRouteGenerator
     */
    private $articleRouteGenerator;

    public function setUp(): void
    {
        $this->routeGenerator = $this->prophesize(RouteGeneratorInterface::class);
        $this->tokenProvider = $this->prophesize(TokenProviderInterface::class);

        $this->articleRouteGenerator = new ArticlePageRouteGenerator(
            $this->routeGenerator->reveal(),
            $this->tokenProvider->reveal()
        );
    }

    public function testGenerate()
    {
        $entity = $this->prophesize(\stdClass::class);

        $parent = '/{object.getParentPath()}';
        $this->tokenProvider->provide($entity->reveal(), 'object.getParentPath()')->willReturn('parent');

        $this->routeGenerator->generate($entity->reveal(), ['route_schema' => '/parent' . $this->routeSchema])
            ->shouldBeCalled()
            ->willReturn('/parent/page-2');

        $result = $this->articleRouteGenerator->generate(
            $entity->reveal(),
            ['parent' => $parent, 'route_schema' => $this->routeSchema]
        );
        $this->assertEquals('/parent/page-2', $result);
    }

    public function testGetOptionsResolver()
    {
        $this->assertInstanceOf(OptionsResolver::class, $this->articleRouteGenerator->getOptionsResolver([]));
    }
}
