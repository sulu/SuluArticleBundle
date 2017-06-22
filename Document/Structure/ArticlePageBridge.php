<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\ArticleBundle\Document\Structure;

use Sulu\Component\Content\Compat\Structure\StructureBridge;

/**
 * Own structure bridge for articles.
 */
class ArticlePageBridge extends StructureBridge
{
    /**
     * {@inheritdoc}
     */
    public function getWebspaceKey()
    {
        return;
    }
}
