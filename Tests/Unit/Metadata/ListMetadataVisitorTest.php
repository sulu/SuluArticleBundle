<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\ArticleBundle\Tests\Unit\Metadata;

use PHPUnit\Framework\TestCase;
use Prophecy\Prophecy\ObjectProphecy;
use Sulu\Bundle\AdminBundle\Metadata\ListMetadata\FieldMetadata;
use Sulu\Bundle\AdminBundle\Metadata\ListMetadata\ListMetadata;
use Sulu\Bundle\ArticleBundle\Metadata\ListMetadataVisitor;
use Sulu\Component\Content\Compat\Structure\StructureBridge;
use Sulu\Component\Content\Compat\StructureManagerInterface;
use Sulu\Component\Content\Metadata\StructureMetadata;
use Sulu\Component\Webspace\Manager\WebspaceCollection;
use Sulu\Component\Webspace\Manager\WebspaceManagerInterface;
use Sulu\Component\Webspace\Webspace;

class ListMetadataVisitorTest extends TestCase
{
    /**
     * @var ObjectProphecy<StructureManagerInterface>
     */
    private $structureManager;

    /**
     * @var ObjectProphecy<WebspaceManagerInterface>
     */
    private $webspaceManager;

    /**
     * @var ListMetadataVisitor
     */
    private $visitor;

    /**
     * @var array<string, array{translation_key: string}>
     */
    private $articleTypeConfigurations;

    public function setUp(): void
    {
        $this->structureManager = $this->prophesize(StructureManagerInterface::class);
        $this->webspaceManager = $this->prophesize(WebspaceManagerInterface::class);

        $this->articleTypeConfigurations = [
            'default' => ['translation_key' => 'sulu_article.type.default'],
            'blog' => ['translation_key' => 'sulu_article.type.blog'],
        ];

        $this->visitor = new ListMetadataVisitor(
            $this->structureManager->reveal(),
            $this->webspaceManager->reveal(),
            $this->articleTypeConfigurations
        );
    }

    public function testVisitListMetadataWithNonArticlesKey(): void
    {
        $listMetadata = $this->prophesize(ListMetadata::class);
        $listMetadata->getField('mainWebspace')->shouldNotBeCalled();
        $listMetadata->getField('type')->shouldNotBeCalled();

        $this->visitor->visitListMetadata($listMetadata->reveal(), 'pages', 'en');
    }

    public function testVisitListMetadataWithArticlesKey(): void
    {
        $listMetadata = $this->prophesize(ListMetadata::class);
        $mainWebspaceField = $this->prophesize(FieldMetadata::class);
        $typeField = $this->prophesize(FieldMetadata::class);

        // Setup webspace collection
        $webspace1 = $this->prophesize(Webspace::class);
        $webspace1->getKey()->willReturn('sulu_io');
        $webspace1->getName()->willReturn('Sulu.io');

        $webspace2 = $this->prophesize(Webspace::class);
        $webspace2->getKey()->willReturn('test');
        $webspace2->getName()->willReturn('Test Webspace');

        $webspaceCollection = new WebspaceCollection([
            $webspace1->reveal(),
            $webspace2->reveal(),
        ]);

        $this->webspaceManager->getWebspaceCollection()->willReturn($webspaceCollection);

        // Setup structure manager
        $structure1 = $this->prophesize(StructureBridge::class);
        $structureMetadata1 = $this->prophesize(StructureMetadata::class);
        $structureMetadata1->getTags()->willReturn([]);
        $structureMetadata1->hasTag('sulu_article.type')->willReturn(false);
        $structure1->getStructure()->willReturn($structureMetadata1->reveal());

        $this->structureManager->getStructures('article')->willReturn([$structure1->reveal()]);

        $listMetadata->getField('mainWebspace')->willReturn($mainWebspaceField->reveal());
        $listMetadata->getField('type')->willReturn($typeField->reveal());

        $mainWebspaceField->setFilterTypeParameters([
            'options' => [
                'sulu_io' => 'Sulu.io',
                'test' => 'Test Webspace',
            ],
        ])->shouldBeCalled();

        $typeField->setFilterType(null)->shouldNotBeCalled();
        $typeField->setFilterTypeParameters([
            'options' => [
                'default' => 'sulu_article.type.default',
                'blog' => 'sulu_article.type.blog',
            ],
        ])->shouldBeCalled();

        $this->visitor->visitListMetadata($listMetadata->reveal(), 'articles', 'en');
    }

    public function testVisitListMetadataWithSingleWebspace(): void
    {
        $listMetadata = $this->prophesize(ListMetadata::class);
        $mainWebspaceField = $this->prophesize(FieldMetadata::class);
        $typeField = $this->prophesize(FieldMetadata::class);

        // Setup webspace collection with single webspace
        $webspace = $this->prophesize(Webspace::class);
        $webspace->getKey()->willReturn('sulu_io');
        $webspace->getName()->willReturn('Sulu.io');

        $webspaceCollection = new WebspaceCollection([
            $webspace->reveal(),
        ]);

        $this->webspaceManager->getWebspaceCollection()->willReturn($webspaceCollection);

        // Setup structure manager
        $structure = $this->prophesize(StructureBridge::class);
        $structureMetadata = $this->prophesize(StructureMetadata::class);
        $structureMetadata->getTags()->willReturn([]);
        $structureMetadata->hasTag('sulu_article.type')->willReturn(false);
        $structure->getStructure()->willReturn($structureMetadata->reveal());

        $this->structureManager->getStructures('article')->willReturn([$structure->reveal()]);

        // With single webspace, the field should be removed and return early
        $listMetadata->getField('mainWebspace')->willReturn($mainWebspaceField->reveal());
        $listMetadata->removeField('mainWebspace')->shouldBeCalled();

        $listMetadata->getField('type')->willReturn($typeField->reveal());

        $typeField->setFilterTypeParameters([
            'options' => [
                'default' => 'sulu_article.type.default',
                'blog' => 'sulu_article.type.blog',
            ],
        ])->shouldBeCalled();

        $this->visitor->visitListMetadata($listMetadata->reveal(), 'articles', 'en');
    }

    public function testVisitListMetadataWithSingleType(): void
    {
        $listMetadata = $this->prophesize(ListMetadata::class);
        $mainWebspaceField = $this->prophesize(FieldMetadata::class);
        $typeField = $this->prophesize(FieldMetadata::class);

        // Setup webspace collection
        $webspace = $this->prophesize(Webspace::class);
        $webspace->getKey()->willReturn('sulu_io');
        $webspace->getName()->willReturn('Sulu.io');

        $webspaceCollection = new WebspaceCollection([
            $webspace->reveal(),
        ]);

        $this->webspaceManager->getWebspaceCollection()->willReturn($webspaceCollection);

        // Setup structure manager with single type
        $this->articleTypeConfigurations = ['default' => ['translation_key' => 'sulu_article.type.default']];

        $this->visitor = new ListMetadataVisitor(
            $this->structureManager->reveal(),
            $this->webspaceManager->reveal(),
            $this->articleTypeConfigurations
        );

        $structure = $this->prophesize(StructureBridge::class);
        $structureMetadata = $this->prophesize(StructureMetadata::class);
        $structureMetadata->getTags()->willReturn([]);
        $structureMetadata->hasTag('sulu_article.type')->willReturn(false);
        $structure->getStructure()->willReturn($structureMetadata->reveal());

        $this->structureManager->getStructures('article')->willReturn([$structure->reveal()]);

        // With single webspace, the field should be removed
        $listMetadata->getField('mainWebspace')->willReturn($mainWebspaceField->reveal());
        $listMetadata->removeField('mainWebspace')->shouldBeCalled();

        $listMetadata->getField('type')->willReturn($typeField->reveal());

        // With single type, filter should be removed
        $typeField->setFilterType(null)->shouldBeCalled();
        $typeField->setFilterTypeParameters(null)->shouldBeCalled();

        $this->visitor->visitListMetadata($listMetadata->reveal(), 'articles', 'en');
    }

    public function testVisitListMetadataWithStructureType(): void
    {
        $listMetadata = $this->prophesize(ListMetadata::class);
        $mainWebspaceField = $this->prophesize(FieldMetadata::class);
        $typeField = $this->prophesize(FieldMetadata::class);

        // Setup webspace collection
        $webspace = $this->prophesize(Webspace::class);
        $webspace->getKey()->willReturn('sulu_io');
        $webspace->getName()->willReturn('Sulu.io');

        $webspaceCollection = new WebspaceCollection([
            $webspace->reveal(),
        ]);

        $this->webspaceManager->getWebspaceCollection()->willReturn($webspaceCollection);

        // Setup structure manager with structure that has a type tag
        $structure = $this->prophesize(StructureBridge::class);
        $structureMetadata = $this->prophesize(StructureMetadata::class);
        $structureMetadata->getTags()->willReturn([
            ['name' => 'sulu_article.type', 'attributes' => ['type' => 'custom']],
        ]);
        $structureMetadata->hasTag('sulu_article.type')->willReturn(true);
        $structureMetadata->getTag('sulu_article.type')->willReturn([
            'name' => 'sulu_article.type',
            'attributes' => ['type' => 'custom'],
        ]);
        $structure->getStructure()->willReturn($structureMetadata->reveal());

        $this->structureManager->getStructures('article')->willReturn([$structure->reveal()]);

        // With single webspace, the field should be removed
        $listMetadata->getField('mainWebspace')->willReturn($mainWebspaceField->reveal());
        $listMetadata->removeField('mainWebspace')->shouldBeCalled();

        $listMetadata->getField('type')->willReturn($typeField->reveal());

        $typeField->setFilterTypeParameters([
            'options' => [
                'default' => 'sulu_article.type.default',
                'blog' => 'sulu_article.type.blog',
                'custom' => 'Custom',
            ],
        ])->shouldBeCalled();

        $this->visitor->visitListMetadata($listMetadata->reveal(), 'articles', 'en');
    }
}
