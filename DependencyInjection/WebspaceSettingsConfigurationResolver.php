<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\ArticleBundle\DependencyInjection;

use Symfony\Component\Form\Exception\InvalidConfigurationException;

class WebspaceSettingsConfigurationResolver
{
    /**
     * @var array
     */
    private $defaultMainWebspace;

    /**
     * @var array
     */
    private $defaultAdditionalWebspaces;

    public function __construct(
        array $defaultMainWebspace,
        array $defaultAdditionalWebspaces
    ) {
        $this->defaultMainWebspace = $defaultMainWebspace;
        $this->defaultAdditionalWebspaces = $defaultAdditionalWebspaces;
    }

    public function getDefaultMainWebspaceForLocale(string $searchedLocale): string
    {
        if (array_key_exists($searchedLocale, $this->defaultMainWebspace)) {
            return $this->defaultMainWebspace[$searchedLocale];
        }

        if (array_key_exists('default', $this->defaultMainWebspace)) {
            return $this->defaultMainWebspace['default'];
        }

        throw new InvalidConfigurationException('No configured default main webspace for locale "' . $searchedLocale . '" not found.');
    }

    public function getDefaultAdditionalWebspacesForLocale(string $searchedLocale): array
    {
        if (array_key_exists($searchedLocale, $this->defaultAdditionalWebspaces)) {
            return $this->defaultAdditionalWebspaces[$searchedLocale];
        }

        if (array_key_exists('default', $this->defaultAdditionalWebspaces)) {
            return $this->defaultAdditionalWebspaces['default'];
        }

        return [];
    }
}
