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
     *
     * @return string
     */
    public function getArticleUuid();

    /**
     * Returns page-uuid.
     *
     * @return string
     */
    public function getPageUuid();

    /**
     * Returns page-number.
     *
     * @return int
     */
    public function getPageNumber();

    /**
     * Returns page-title.
     *
     * @return string
     */
    public function getPageTitle();

    /**
     * Returns structure-type.
     *
     * @return string
     */
    public function getStructureType();

    /**
     * Returns structure.
     *
     * @return StructureInterface
     */
    public function getStructure();

    /**
     * Return the workflow stage.
     *
     * @return string|int
     */
    public function getWorkflowStage();

    /**
     * Returns all extension data.
     *
     * @return ExtensionContainer
     */
    public function getExtensionsData();
}
