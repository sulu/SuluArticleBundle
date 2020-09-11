<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\ArticleBundle\Tests\TestExtendBundle\Document;

use ONGR\ElasticsearchBundle\Annotation\Document;
use ONGR\ElasticsearchBundle\Annotation\Property;
use Sulu\Bundle\ArticleBundle\Document\ArticleViewDocument as SuluArticleViewDocument;

/**
 * @Document(type="article")
 */
class ArticleViewDocument extends SuluArticleViewDocument
{
    /**
     * @var string
     *
     * @Property(type="text")
     */
    public $article;
}
