<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\ArticleBundle\Tests\Unit\Content;

use Sulu\Bundle\ArticleBundle\Content\ArticleResourceItem;
use Sulu\Bundle\ArticleBundle\Document\ArticleDocument;
use Sulu\Bundle\ArticleBundle\Document\ArticleViewDocumentInterface;

class ArticleResourceItemTest extends \PHPUnit_Framework_TestCase
{
    public function test__get()
    {
        $viewDocument = $this->prophesize(ArticleViewDocumentInterface::class);
        $document = $this->prophesize(ArticleDocument::class);

        $resourceItem = new ArticleResourceItem($viewDocument->reveal(), $document->reveal());

        $viewDocument->__get('test')->willReturn('value')->shouldBeCalled();

        $this->assertEquals('value', $resourceItem->__get('test'));
    }
}
