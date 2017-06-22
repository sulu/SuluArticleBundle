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
 * Interface for page-tree-updater.
 */
interface PageTreeUpdaterInterface
{
    /**
     * Updates routes of linked articles.
     *
     * @param BasePageDocument $document
     */
    public function update(BasePageDocument $document);
}
