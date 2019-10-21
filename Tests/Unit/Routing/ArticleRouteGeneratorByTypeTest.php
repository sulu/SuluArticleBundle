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
use Sulu\Bundle\ArticleBundle\Routing\ArticleRouteGeneratorByType;
use Sulu\Bundle\RouteBundle\Generator\RouteGeneratorInterface;
use Sulu\Component\Content\Document\Behavior\StructureBehavior;
use Sulu\Component\Content\Metadata\Factory\StructureMetadataFactoryInterface;
use Sulu\Component\Content\Metadata\StructureMetadata;

class ArticleRouteGeneratorByTypeTest extends TestCase
{
    /**
     * @var RouteGeneratorInterface
     */
    private $generatorBySchema;

    /**
     * @var RouteGeneratorInterface
     */
    private $generatorByTemplate;

    /**
     * @var StructureMetadataFactoryInterface
     */
    private $structureMetadataFactory;

    /**
     * @var array
     */
    private $config = ['type1' => '/test1/{entity.getTitle()', 'type2' => '/test2/{entity.getTitle()'];

    public function setUp(): void
    {
        $this->generatorBySchema = $this->prophesize(RouteGeneratorInterface::class);
        $this->structureMetadataFactory = $this->prophesize(StructureMetadataFactoryInterface::class);

        $this->generatorByTemplate = new ArticleRouteGeneratorByType(
            $this->generatorBySchema->reveal(),
            $this->structureMetadataFactory->reveal()
        );
    }

    public function testGenerate()
    {
        $metadata = new StructureMetadata();
        $metadata->setTags([
            ['name' => 'sulu_article.type', 'attributes' => ['type' => 'type1']],
        ]);

        $this->structureMetadataFactory->getStructureMetadata('article', 'test1')->willReturn($metadata);

        $entity = $this->prophesize(StructureBehavior::class);
        $entity->getStructureType()->willReturn('test1');

        $this->generatorByTemplate->generate($entity->reveal(), $this->config);

        $this->generatorBySchema->generate($entity->reveal(), ['route_schema' => '/test1/{entity.getTitle()'])
            ->shouldBeCalled();
    }

    public function testGenerateNotConfigured()
    {
        $this->expectException(RouteSchemaNotFoundException::class);

        $metadata = new StructureMetadata();
        $metadata->setTags([
            ['name' => 'sulu_article.type', 'attributes' => ['type' => 'type3']],
        ]);

        $this->structureMetadataFactory->getStructureMetadata('article', 'test3')->willReturn($metadata);

        $entity = $this->prophesize(StructureBehavior::class);
        $entity->getStructureType()->willReturn('test3');

        $this->generatorByTemplate->generate($entity->reveal(), $this->config);
    }
}
