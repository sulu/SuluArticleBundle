<?php

namespace Sulu\Bundle\ArticleBundle\Prooph\Model;

use Sulu\Component\Content\Document\WorkflowStage;

class ArticleTranslation
{
    /**
     * @var string
     */
    public $id;

    /**
     * @var string
     */
    public $locale;

    /**
     * @var string
     */
    public $title;

    /**
     * @var string
     */
    public $structureType;

    /**
     * @var array
     */
    public $structureData = [];

    /**
     * @var int
     */
    public $createdBy;

    /**
     * @var int
     */
    public $modifiedBy;

    /**
     * @var \DateTimeImmutable
     */
    public $createdAt;

    /**
     * @var \DateTimeImmutable
     */
    public $modifiedAt;

    /**
     * @var int
     */
    public $publishedBy;

    /**
     * @var \DateTimeImmutable
     */
    public $publishedAt;

    /**
     * @var int
     */
    public $workflowStage = WorkflowStage::TEST;

    // TODO pageTitle, routePath, versions, extensions, pages, author
}
