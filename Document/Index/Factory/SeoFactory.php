<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\ArticleBundle\Document\Index\Factory;

use Sulu\Bundle\ArticleBundle\Document\SeoViewObject;

/**
 * Create a seo view object.
 */
class SeoFactory
{
    /**
     * Create a seo view object by given data.
     *
     * @param array $data
     *
     * @return SeoViewObject
     */
    public function create($data)
    {
        $seo = new SeoViewObject();

        if (empty($data)) {
            return $seo;
        }

        $seo->title = $data['title'];
        $seo->description = $data['description'];
        $seo->keywords = $data['keywords'];
        $seo->canonicalUrl = $data['canonicalUrl'];
        $seo->noIndex = $data['noIndex'];
        $seo->noFollow = $data['noFollow'];
        $seo->hideInSitemap = $data['hideInSitemap'];

        return $seo;
    }
}
