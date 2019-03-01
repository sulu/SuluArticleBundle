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

use Sulu\Bundle\ArticleBundle\DependencyInjection\WebspaceSettingsConfigurationResolver;
use Sulu\Bundle\ArticleBundle\Document\Behavior\WebspaceBehavior;
use Sulu\Component\Webspace\Manager\WebspaceManagerInterface;

class WebspaceResolver
{
    /**
     * @var WebspaceManagerInterface
     */
    private $webspaceManager;

    /**
     * @var WebspaceSettingsConfigurationResolver
     */
    private $webspaceSettingsConfigurationResolver;

    /**
     * @param WebspaceManagerInterface $webspaceManager
     * @param WebspaceSettingsConfigurationResolver $webspaceSettingsConfigurationResolver
     */
    public function __construct(
        WebspaceManagerInterface $webspaceManager,
        WebspaceSettingsConfigurationResolver $webspaceSettingsConfigurationResolver
    ) {
        $this->webspaceManager = $webspaceManager;
        $this->webspaceSettingsConfigurationResolver = $webspaceSettingsConfigurationResolver;
    }

    /**
     * @param WebspaceBehavior $document
     *
     * @return null|string
     */
    public function resolveMainWebspace(WebspaceBehavior $document)
    {
        if (!$this->hasMoreThanOneWebspace()) {
            $webspaces = $this->webspaceManager->getWebspaceCollection()->getWebspaces();

            return reset($webspaces)->getKey();
        }

        if ($document->getMainWebspace()) {
            return $document->getMainWebspace();
        }

        return $this->webspaceSettingsConfigurationResolver->getDefaultMainWebspaceForLocale($document->getOriginalLocale());
    }

    /**
     * @param WebspaceBehavior $document
     *
     * @return null|string[]
     */
    public function resolveAdditionalWebspaces(WebspaceBehavior $document)
    {
        if (!$this->hasMoreThanOneWebspace()) {
            return [];
        }

        if ($document->getAdditionalWebspaces()) {
            return $document->getAdditionalWebspaces();
        }

        return $this->webspaceSettingsConfigurationResolver->getDefaultAdditionalWebspacesForLocale($document->getOriginalLocale());
    }

    /**
     * Check if system has more than one webspace.
     *
     * @return bool
     */
    private function hasMoreThanOneWebspace()
    {
        return count($this->webspaceManager->getWebspaceCollection()->getWebspaces()) > 1;
    }
}
