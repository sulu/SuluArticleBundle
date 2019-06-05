<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\ArticleBundle\Controller;

use FOS\RestBundle\Controller\Annotations\RouteResource;
use Sulu\Bundle\PageBundle\Content\Structure\SeoStructureExtension;
use Sulu\Bundle\PageBundle\Controller\AbstractExtensionController;

/**
 * @RouteResource("article-seo")
 */
class SeoController extends AbstractExtensionController
{
    protected function getExtensionName()
    {
        return SeoStructureExtension::SEO_EXTENSION_NAME;
    }
}
