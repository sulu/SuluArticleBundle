<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\ArticleBundle\Metadata;

use Sulu\Bundle\AdminBundle\Metadata\ListMetadata\ListMetadata;
use Sulu\Bundle\AdminBundle\Metadata\ListMetadata\ListMetadataVisitorInterface;
use Sulu\Component\Content\Compat\Structure\StructureBridge;
use Sulu\Component\Content\Compat\StructureManagerInterface;
use Sulu\Component\Webspace\Manager\WebspaceManagerInterface;

/**
 * @final
 *
 * @internal
 */
class ListMetadataVisitor implements ListMetadataVisitorInterface
{
    use StructureTagTrait;

    /**
     * @var StructureManagerInterface
     */
    private $structureManager;

    /**
     * @var WebspaceManagerInterface
     */
    private $webspaceManager;

    /**
     * @var array<string, array{
     *      translation_key: string,
     * }>
     */
    private $articleTypeConfigurations;

    /**
     * @param array<string, array{
     *      translation_key: string,
     * }> $articleTypeConfigurations
     */
    public function __construct(
        StructureManagerInterface $structureManager,
        WebspaceManagerInterface $webspaceManager,
        array $articleTypeConfigurations
    ) {
        $this->structureManager = $structureManager;
        $this->webspaceManager = $webspaceManager;
        $this->articleTypeConfigurations = $articleTypeConfigurations;
    }

    public function visitListMetadata(ListMetadata $listMetadata, string $key, string $locale, array $metadataOptions = []): void
    {
        if ('articles' !== $key) {
            return;
        }

        $this->addMainWebspaceFilter($listMetadata);
        $this->adaptTypeFilter($listMetadata);
    }

    protected function addMainWebspaceFilter(ListMetadata $listMetadata): void
    {
        $webspaceField = $listMetadata->getField('mainWebspace');

        $webspaces = [];
        foreach ($this->webspaceManager->getWebspaceCollection() as $webspace) {
            $webspaces[$webspace->getKey()] = $webspace->getName();
        }

        if (1 === \count($webspaces)) {
            $listMetadata->removeField('mainWebspace');

            return;
        }

        $webspaceField->setFilterTypeParameters(['options' => $webspaces]);
    }

    protected function adaptTypeFilter(ListMetadata $listMetadata): void
    {
        $typeField = $listMetadata->getField('type');

        $types = $this->getTypes();
        if (1 === \count($types)) {
            $typeField->setFilterType(null);
            $typeField->setFilterTypeParameters(null);

            return;
        }

        $options = [];
        foreach ($types as $type) {
            $options[$type['type']] = $type['title'];
        }

        $typeField->setFilterTypeParameters(['options' => $options]);
    }

    /**
     * @return array<string, array{
     *     type: string,
     *     title: string,
     * }>
     */
    private function getTypes(): array
    {
        $types = [];

        // prefill array with keys from configuration to keep order of configuration for tabs
        foreach ($this->articleTypeConfigurations as $typeKey => $articleTypeConfiguration) {
            $types[$typeKey] = [
                'type' => $typeKey,
                'title' => $this->getTitle($typeKey),
            ];
        }

        /** @var StructureBridge $structure */
        foreach ($this->structureManager->getStructures('article') as $structure) {
            /** @var string|null $type */
            $type = $this->getType($structure->getStructure(), null);
            $typeKey = $type ?: 'default';
            if (empty($types[$typeKey])) {
                $types[$typeKey] = [
                    'type' => $typeKey,
                    'title' => $this->getTitle($typeKey),
                ];
            }
        }

        return $types;
    }

    private function getTitle(string $type): string
    {
        if (!\array_key_exists($type, $this->articleTypeConfigurations)) {
            return \ucfirst($type);
        }

        return $this->articleTypeConfigurations[$type]['translation_key'];
    }
}
