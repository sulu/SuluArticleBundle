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

namespace Sulu\Bundle\ArticleBundle\Tests\Unit\Content;

use ONGR\ElasticsearchBundle\Service\Manager;
use ONGR\ElasticsearchBundle\Service\Repository;
use PHPUnit\Framework\TestCase;
use Sulu\Bundle\ArticleBundle\Content\SingleArticleSelectionContentType;
use Sulu\Bundle\ArticleBundle\Document\ArticleViewDocument;
use Sulu\Bundle\WebsiteBundle\ReferenceStore\ReferenceStoreInterface;
use Sulu\Component\Content\Compat\PropertyInterface;
use Sulu\Component\Content\Compat\StructureInterface;

/**
 * Unit testcases for SingleArticleSelection ContentType.
 */
class SingleArticleSelectionContentTypeTest extends TestCase
{
    public function testGetContentData()
    {
        $id = '123-123-123';
        $locale = 'de';
        $localizedId = sprintf('%s-%s', $id, $locale);
        $article = new ArticleViewDocument($id);

        $manager = $this->prophesize(Manager::class);
        $property = $this->prophesize(PropertyInterface::class);
        $structure = $this->prophesize(StructureInterface::class);
        $repository = $this->prophesize(Repository::class);
        $referenceStore = $this->prophesize(ReferenceStoreInterface::class);

        $structure->getLanguageCode()->willReturn($locale);
        $property->getStructure()->willReturn($structure->reveal());

        $property->getValue()->willReturn($id);
        $manager->getRepository(ArticleViewDocument::class)->willReturn($repository->reveal());
        $repository->find($localizedId)->willReturn($article);
        $repository->find($localizedId)->shouldBeCalled();

        $contentType = new SingleArticleSelectionContentType(
            $manager->reveal(),
            $referenceStore->reveal(),
            ArticleViewDocument::class
        );

        $result = $contentType->getContentData($property->reveal());

        $this->assertNotNull($result);
        $this->assertIsObject($result);
        $this->assertEquals($id, $result->getUuid());
    }

    public function testGetContentDataNull()
    {
        $id = null;

        $manager = $this->prophesize(Manager::class);
        $property = $this->prophesize(PropertyInterface::class);
        $referenceStore = $this->prophesize(ReferenceStoreInterface::class);

        $property->getValue()->willReturn($id);
        $manager->getRepository(ArticleViewDocument::class)->shouldNotBeCalled();

        $contentType = new SingleArticleSelectionContentType(
            $manager->reveal(),
            $referenceStore->reveal(),
            ArticleViewDocument::class
        );

        $result = $contentType->getContentData($property->reveal());

        $this->assertNull($result);
    }
}
