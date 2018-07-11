<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\ArticleBundle\Document\Resolver;

use Sulu\Bundle\ArticleBundle\Document\Behavior\WebspaceBehavior;

class WebspaceResolver
{
    /**
     * @var string
     */
    private $defaultMainWebspace;

    /**
     * @var string[]
     */
    private $defaultAdditionalWebspaces;

    /**
     * @param string $defaultMainWebspace
     * @param string[] $defaultAdditionalWebspaces
     */
    public function __construct(
        $defaultMainWebspace,
        $defaultAdditionalWebspaces
    ) {
        $this->defaultMainWebspace = $defaultMainWebspace;
        $this->defaultAdditionalWebspaces = $defaultAdditionalWebspaces;
    }

    /**
     * @param WebspaceBehavior $document
     *
     * @return null|string
     */
    public function resolveMainWebspace(WebspaceBehavior $document)
    {
        return $document->getMainWebspace() ? $document->getMainWebspace() : $this->defaultMainWebspace;
    }

    /**
     * @param WebspaceBehavior $document
     *
     * @return null|string[]
     */
    public function resolveAdditionalWebspaces(WebspaceBehavior $document)
    {
        return $document->getAdditionalWebspaces() ? $document->getAdditionalWebspaces(): $this->defaultAdditionalWebspaces;
    }
}
