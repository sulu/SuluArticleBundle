<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\ArticleBundle\Document\Behavior;

use Sulu\Component\DocumentManager\Behavior\Mapping\ParentBehavior;
use Sulu\Component\DocumentManager\Behavior\Mapping\PathBehavior;
use Sulu\Component\DocumentManager\Behavior\Mapping\UuidBehavior;

/**
 * This behavior has to be attached to documents which should have a page-number property.
 */
interface PageBehavior extends ParentBehavior, UuidBehavior, PathBehavior
{
    /**
     * Returns page-number.
     *
     * @return int
     */
    public function getPageNumber();

    /**
     * Set page-number.
     *
     * @param int $pageNumber
     *
     * @return self
     */
    public function setPageNumber($pageNumber);
}
