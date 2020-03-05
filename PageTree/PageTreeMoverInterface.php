<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\ArticleBundle\PageTree;

use Sulu\Bundle\ContentBundle\Document\BasePageDocument;

/**
 * Interface for page-tree-mover.
 */
interface PageTreeMoverInterface
{
    /**
     * @param string $source
     */
    public function move($source, BasePageDocument $destination);
}
