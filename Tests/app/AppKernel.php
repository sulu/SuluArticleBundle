<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

use Sulu\Bundle\ArticleBundle\SuluArticleBundle;
use Sulu\Bundle\TestBundle\Kernel\SuluTestKernel;

/**
 * AppKernel for functional tests.
 */
class AppKernel extends SuluTestKernel
{
    /**
     * {@inheritdoc}
     */
    public function registerBundles()
    {
        return array_merge([new SuluArticleBundle()], parent::registerBundles());
    }
}
