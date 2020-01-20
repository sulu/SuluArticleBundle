<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\ArticleBundle\Document;

use Sulu\Component\Content\Document\Extension\ExtensionContainer;
use Sulu\Component\Content\Document\Structure\StructureInterface;

/**
 * This interface defines the intersection of article and article-page document.
 */
interface ArticleInterface
{
    /**
     * Returns article-uuid.
     */
    public function getArticleUuid(): string;

    /**
     * Returns page-uuid.
     */
    public function getPageUuid(): string;

    /**
     * Returns page-number.
     */
    public function getPageNumber(): int;

    /**
     * Returns page-title.
     */
    public function getPageTitle(): string;

    /**
     * Returns structure-type.
     */
    public function getStructureType(): string;

    /**
     * Returns structure.
     */
    public function getStructure(): StructureInterface;

    /**
     * Return the workflow stage.
     *
     * @return string|int
     */
    public function getWorkflowStage();

    /**
     * Returns all extension data.
     *
     * TODO cannot set return typehint to ExtensionContainer, because Sulu's ExtensionBehavior::getExtensionData()
     * returns an array
     *
     * @return ExtensionContainer
     */
    public function getExtensionsData();
}
