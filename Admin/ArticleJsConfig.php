<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\ArticleBundle\Admin;

use Sulu\Bundle\AdminBundle\Admin\JsConfigInterface;
use Sulu\Bundle\ArticleBundle\DependencyInjection\WebspaceSettingsConfigurationResolver;
use Sulu\Bundle\ArticleBundle\Metadata\StructureTagTrait;
use Sulu\Component\Content\Compat\StructureManagerInterface;
use Sulu\Component\Localization\Localization;
use Sulu\Component\Webspace\Manager\WebspaceManager;

/**
 * Provides js-configuration.
 */
class ArticleJsConfig implements JsConfigInterface
{
    use StructureTagTrait;

    /**
     * @var WebspaceManager
     */
    private $webspaceManager;

    /**
     * @var StructureManagerInterface
     */
    private $structureManager;

    /**
     * @var WebspaceSettingsConfigurationResolver
     */
    private $webspaceSettingsConfigurationResolver;

    /**
     * @var array
     */
    private $typeConfiguration;

    /**
     * @var array
     */
    private $parameter;

    public function __construct(
        WebspaceManager $webspaceManager,
        StructureManagerInterface $structureManager,
        WebspaceSettingsConfigurationResolver $webspaceSettingsConfigurationResolver,
        array $typeConfiguration,
        array $parameter
    ) {
        $this->webspaceManager = $webspaceManager;
        $this->structureManager = $structureManager;
        $this->webspaceSettingsConfigurationResolver = $webspaceSettingsConfigurationResolver;
        $this->typeConfiguration = $typeConfiguration;
        $this->parameter = $parameter;
    }

    /**
     * {@inheritdoc}
     */
    public function getParameters()
    {
        $config = array_merge(
            $this->parameter,
            [
                'types' => [],
                'templates' => [],
            ]
        );

        foreach ($this->structureManager->getStructures('article') as $structure) {
            $type = $this->getType($structure->getStructure());
            if (!array_key_exists($type, $config['types'])) {
                $config['types'][$type] = [
                    'default' => $structure->getKey(),
                    'title' => $this->getTitle($type),
                ];
            }

            $config['templates'][$structure->getKey()] = [
                'multipage' => ['enabled' => $this->getMultipage($structure->getStructure())],
            ];
        }

        $config = array_merge($config, $this->getWebspaceSettingsConfig());

        return $config;
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'sulu_article';
    }

    /**
     * Returns title for given type.
     *
     * @param string $type
     *
     * @return string
     */
    private function getTitle($type)
    {
        if (!array_key_exists($type, $this->typeConfiguration)) {
            return ucfirst($type);
        }

        return $this->typeConfiguration[$type]['translation_key'];
    }

    /**
     * @return array
     */
    private function getWebspaceSettingsConfig()
    {
        $showWebspaceSettings = count($this->webspaceManager->getWebspaceCollection()->getWebspaces()) > 1;

        if (!$showWebspaceSettings) {
            return [
                'showWebspaceSettings' => $showWebspaceSettings,
            ];
        }

        $webspaces = [];
        foreach ($this->webspaceManager->getWebspaceCollection()->getWebspaces() as $webspace) {
            $webspaces[] = [
                'id' => $webspace->getKey(),
                'key' => $webspace->getKey(),
                'name' => $webspace->getName(),
            ];
        }

        $webspaceSettings = [];
        /** @var Localization $localization */
        foreach ($this->webspaceManager->getAllLocalizations() as $localization) {
            $locale = $localization->getLocale();

            $defaultMainWebspace = $this->getDefaultMainWebspace($locale, $webspaces);
            $defaultAdditionalWebspaces = $this->getDefaultAdditionalWebspaces($defaultMainWebspace, $locale, $webspaces);

            $webspaceSettings[$localization->getLocale()] = [
                'defaultMainWebspace' => $defaultMainWebspace,
                'defaultAdditionalWebspaces' => $defaultAdditionalWebspaces,
            ];
        }

        return [
            'showWebspaceSettings' => $showWebspaceSettings,
            'webspaces' => $webspaces,
            'webspaceSettings' => $webspaceSettings,
        ];
    }

    /**
     * @param string $locale
     * @param array $webspaces
     *
     * @return string
     */
    private function getDefaultMainWebspace($locale, $webspaces)
    {
        $defaultMainWebspace = $this->webspaceSettingsConfigurationResolver->getDefaultMainWebspaceForLocale($locale);
        if (!$this->webspaceManager->findWebspaceByKey($defaultMainWebspace)) {
            throw new \InvalidArgumentException('Configured default main webspace "' . $defaultMainWebspace . '" not found. Available webspaces: "' . implode(',', array_column($webspaces, 'key')) . '"');
        }

        return $defaultMainWebspace;
    }

    /**
     * @param string $defaultMainWebspace
     * @param string $locale
     * @param array $webspaces
     *
     * @return array
     */
    private function getDefaultAdditionalWebspaces($defaultMainWebspace, $locale, $webspaces)
    {
        $defaultAdditionalWebspaces = [];
        $additionalWebspaces = $this->webspaceSettingsConfigurationResolver->getDefaultAdditionalWebspacesForLocale($locale);
        foreach ($additionalWebspaces as $additionalWebspace) {
            if ($defaultMainWebspace === $additionalWebspace) {
                throw new \InvalidArgumentException('Configured default additional webspace "' . $additionalWebspace . '" is the default main webspace.');
            }

            if (!$this->webspaceManager->findWebspaceByKey($additionalWebspace)) {
                throw new \InvalidArgumentException('Configured default additional webspace "' . $additionalWebspace . '" not found. Available webspaces: "' . implode(',', array_column($webspaces, 'key')) . '"');
            }
            $defaultAdditionalWebspaces[] = $additionalWebspace;
        }

        return $defaultAdditionalWebspaces;
    }
}
