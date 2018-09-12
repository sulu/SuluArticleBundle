<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
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

    /**
     * @param array $defaultMainWebspace
     * @param array $defaultAdditionalWebspaces
     */
    public function __construct(
        array $defaultMainWebspace,
        array $defaultAdditionalWebspaces
    ) {
        $this->defaultMainWebspace = $defaultMainWebspace;
        $this->defaultAdditionalWebspaces = $defaultAdditionalWebspaces;
    }

    /**
     * @param string $searchedLocale
     *
     * @return string
     */
    public function getDefaultMainWebspaceForLocale($searchedLocale)
    {
        foreach ($this->defaultMainWebspace as $locale => $mainWebspace) {
            if ($searchedLocale === $locale) {
                return $mainWebspace;
            }
        }

        if (array_key_exists('default', $this->defaultMainWebspace)) {
            return $this->defaultMainWebspace['default'];
        }

        throw new InvalidConfigurationException('Configured default main webspace for locale "' . $searchedLocale . '" not found');
    }

    /**
     * @param string $searchedLocale
     *
     * @return array
     */
    public function getDefaultAdditionalWebspacesForLocale($searchedLocale)
    {
        foreach ($this->defaultAdditionalWebspaces as $locale => $additionalWebspaces) {
            if ($searchedLocale === $locale) {
                return $additionalWebspaces;
            }
        }

        if (array_key_exists('default', $this->defaultAdditionalWebspaces)) {
            return $this->defaultAdditionalWebspaces['default'];
        }

        return [];
    }
}
