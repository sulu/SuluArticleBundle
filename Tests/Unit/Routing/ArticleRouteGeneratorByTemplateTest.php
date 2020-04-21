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
use Sulu\Bundle\ArticleBundle\Exception\RouteSchemaNotFoundException;
use Sulu\Bundle\ArticleBundle\Routing\ArticleRouteGeneratorByTemplate;
use Sulu\Bundle\RouteBundle\Generator\RouteGeneratorInterface;
use Sulu\Component\Content\Document\Behavior\StructureBehavior;

class ArticleRouteGeneratorByTemplateTest extends TestCase
{
    /**
     * @var RouteGeneratorInterface
     */
    private $generatorBySchema;

    /**
     * @var RouteGeneratorInterface
     */
    private $generatorByTemplate;

    private $config = ['test1' => '/test1/{entity.getTitle()', 'test2' => '/test2/{entity.getTitle()'];

    public function setUp(): void
    {
        $this->generatorBySchema = $this->prophesize(RouteGeneratorInterface::class);
        $this->generatorByTemplate = new ArticleRouteGeneratorByTemplate($this->generatorBySchema->reveal());
    }

    public function testGenerate()
    {
        $entity = $this->prophesize(StructureBehavior::class);
        $entity->getStructureType()->willReturn('test1');

        $this->generatorByTemplate->generate($entity->reveal(), $this->config);

        $this->generatorBySchema->generate($entity->reveal(), ['route_schema' => '/test1/{entity.getTitle()'])
            ->shouldBeCalled();
    }

    public function testGenerateNotConfigured()
    {
        $this->expectException(RouteSchemaNotFoundException::class);

        $entity = $this->prophesize(StructureBehavior::class);
        $entity->getStructureType()->willReturn('test3');

        $this->generatorByTemplate->generate($entity->reveal(), $this->config);
    }
}
