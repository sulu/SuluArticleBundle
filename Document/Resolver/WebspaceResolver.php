<?php

declare(strict_types=1);

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
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

    public function __construct(
        WebspaceManagerInterface $webspaceManager,
        WebspaceSettingsConfigurationResolver $webspaceSettingsConfigurationResolver
    ) {
        $this->webspaceManager = $webspaceManager;
        $this->webspaceSettingsConfigurationResolver = $webspaceSettingsConfigurationResolver;
    }

    public function resolveMainWebspace(WebspaceBehavior $document): ?string
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
     * @return string[]|null
     */
    public function resolveAdditionalWebspaces(WebspaceBehavior $document): ?array
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
     */
    private function hasMoreThanOneWebspace(): bool
    {
        return count($this->webspaceManager->getWebspaceCollection()->getWebspaces()) > 1;
    }
}
