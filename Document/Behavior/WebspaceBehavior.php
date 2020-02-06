<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\ArticleBundle\Document\Behavior;

use Sulu\Component\DocumentManager\Behavior\Mapping\LocaleBehavior;

/**
 * This behavior adds the webspace functionality to articles.
 */
interface WebspaceBehavior extends LocaleBehavior
{
    /**
     * @return null|string
     */
    public function getMainWebspace();

    /**
     * @param null|string $webspace
     *
     * @return self
     */
    public function setMainWebspace($webspace);

    /**
     * @return null|string[]
     */
    public function getAdditionalWebspaces();

    /**
     * @param null|string[] $additionalWebspaces
     *
     * @return self
     */
    public function setAdditionalWebspaces($additionalWebspaces);
}
