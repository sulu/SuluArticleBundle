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
use FOS\RestBundle\View\View;
use Sulu\Component\Rest\RestController;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * @RouteResource("article-seo")
 */
class SeoController extends RestController
{
    public function getAction(string $id)
    {
        // TODO: Implement
        return $this->handleView($this->view());
    }

    public function putAction(string $id)
    {
        // TODO: Implement
        return $this->handleView($this->view());
    }
}
