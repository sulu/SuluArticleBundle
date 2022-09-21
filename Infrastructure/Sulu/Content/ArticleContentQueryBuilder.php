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

namespace Sulu\Bundle\ArticleBundle\Infrastructure\Sulu\Content;

use Sulu\Component\Content\Compat\PropertyParameter;
use Sulu\Component\Content\Query\ContentQueryBuilder;

/**
 * Query builder to load smart content.
 */
class ArticleContentQueryBuilder extends ContentQueryBuilder
{
    /**
     * @var string[]
     */
    protected static $mixinTypes = ['sulu:article'];

    /**
     * @var string
     */
    protected static $structureType = 'article';

    /**
     * @var string[]
     */
    protected $properties = ['routePath'];

    /**
     * disable automatic excerpt loading.
     *
     * @var bool
     */
    protected $excerpt = false;

    /**
     * array of ids to load.
     *
     * @var string[]
     */
    private $ids = [];

    /**
     * configuration which properties should be loaded.
     *
     * @var PropertyParameter[]
     */
    private $propertiesConfig = [];

    /**
     * @param array{
     *     ids: string[],
     *     properties?: PropertyParameter[],
     *     published?: bool,
     * } $options
     *
     * @return void
     */
    public function init(array $options)
    {
        $ids = $options['ids'];

        if (!\is_array($ids) || \count($ids) < 1) {
            throw new \InvalidArgumentException('The "ids" option passed to ArticleContentQueryBuilder::init() must be a non empty array');
        }

        $this->ids = $ids;
        $this->propertiesConfig = \array_merge(
            ['routePath' => new PropertyParameter('routePath', 'routePath')],
            isset($options['properties']) ? $options['properties'] : []
        );
        $this->published = isset($options['published']) ? $options['published'] : false;
    }

    /**
     * @param string $webspaceKey
     * @param string $locale
     *
     * @return string
     */
    protected function buildWhere($webspaceKey, $locale)
    {
        $idsWhere = [];

        foreach ($this->ids as $id) {
            $idsWhere[] = \sprintf("page.[jcr:uuid] = '%s'", $id);
        }

        return '(' . \implode(' OR ', $idsWhere) . ')';
    }

    /**
     * @param string $webspaceKey
     * @param string $locale
     * @param array<string, array<array{name: string, property: string, extension?: string, templateKey?: string}>> $additionalFields
     *
     * @return string
     */
    protected function buildSelect($webspaceKey, $locale, &$additionalFields)
    {
        $select = [];

        if (\count($this->propertiesConfig) > 0) {
            $this->buildPropertiesSelect($locale, $additionalFields);
        }

        return \implode(', ', $select);
    }

    /**
     * @param string $locale
     * @param array<string, array<array{name: string, property: string, extension?: string, templateKey?: string}>> $additionalFields
     */
    private function buildPropertiesSelect($locale, &$additionalFields): void
    {
        foreach ($this->propertiesConfig as $parameter) {
            $alias = $parameter->getName();
            /** @var string $propertyName */
            $propertyName = $parameter->getValue();

            if (false !== \strpos($propertyName, '.')) {
                $parts = \explode('.', $propertyName);

                $this->buildExtensionSelect($alias, $parts[0], $parts[1], $locale, $additionalFields);
            } else {
                $this->buildPropertySelect($alias, $propertyName, $locale, $additionalFields);
            }
        }
    }

    /**
     * @param string $alias
     * @param string $propertyName
     * @param string $locale
     * @param array<string, array<array{name: string, property: string, extension?: string, templateKey?: string}>> $additionalFields
     */
    private function buildPropertySelect($alias, $propertyName, $locale, &$additionalFields): void
    {
        foreach ($this->structureManager->getStructures(static::$structureType) as $structure) {
            if ($structure->hasProperty($propertyName)) {
                $property = $structure->getProperty($propertyName);
                $additionalFields[$locale][] = [
                    'name' => $alias,
                    'property' => $property,
                    'templateKey' => $structure->getKey(),
                ];
            }
        }
    }

    /**
     * @param string $alias
     * @param string $extension
     * @param string $propertyName
     * @param string $locale
     * @param array<string, array<array{name: string, property: string, extension?: string, templateKey?: string}>> $additionalFields
     */
    private function buildExtensionSelect($alias, $extension, $propertyName, $locale, &$additionalFields): void
    {
        $extension = $this->extensionManager->getExtension('all', $extension);
        $additionalFields[$locale][] = [
            'name' => $alias,
            'extension' => $extension,
            'property' => $propertyName,
        ];
    }
}
