<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\ArticleBundle\PageTree;

use Sulu\Bundle\ContentBundle\Document\BasePageDocument;

/**
 * Does nothing and is only used a placeholder if the "page_route_cache" is "off".
 */
class NullPageTreeUpdater implements PageTreeUpdaterInterface
{
    /**
     * {@inheritdoc}
     */
    public function update(BasePageDocument $document)
    {
        // do nothing
    }
}
