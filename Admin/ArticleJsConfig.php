<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\ArticleBundle\Admin;

use Sulu\Bundle\AdminBundle\Admin\JsConfigInterface;
use Sulu\Bundle\ArticleBundle\Metadata\StructureTagTrait;
use Sulu\Component\Content\Compat\StructureManagerInterface;
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
     * @var array
     */
    private $typeConfiguration;

    /**
     * @var array
     */
    private $parameter;

    /**
     * @param WebspaceManager $webspaceManager
     * @param StructureManagerInterface $structureManager
     * @param array $typeConfiguration
     * @param array $parameter
     */
    public function __construct(
        WebspaceManager $webspaceManager,
        StructureManagerInterface $structureManager,
        array $typeConfiguration,
        array $parameter
    ) {
        $this->webspaceManager = $webspaceManager;
        $this->structureManager = $structureManager;
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

        $defaultMainWebspace = $this->parameter['defaultMainWebspace'];
        if (!$defaultMainWebspace || !$this->webspaceManager->findWebspaceByKey($defaultMainWebspace)) {
            throw new \InvalidArgumentException('You have more than one webspace, so you need to set config parameter "sulu_article.default_main_webspace" to one of "' . join(',', array_column($webspaces, 'key')) . '"');
        }

        foreach ($this->parameter['defaultAdditionalWebspaces'] as $defaultAdditionalWebspace) {
            if (!$this->webspaceManager->findWebspaceByKey($defaultAdditionalWebspace)) {
                throw new \InvalidArgumentException('Configured default additional webspace "' . $defaultAdditionalWebspace . '" not found. Available webspaces: "' . join(',', array_column($webspaces, 'key')) . '"');
            }
        }

        return [
            'showWebspaceSettings' => $showWebspaceSettings,
            'webspaces' => $webspaces,
        ];
    }
}
