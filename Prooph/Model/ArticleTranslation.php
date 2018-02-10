<?php

namespace Sulu\Bundle\ArticleBundle\Prooph\Model;

use Sulu\Bundle\RouteBundle\Model\RoutableInterface;
use Sulu\Bundle\RouteBundle\Model\RouteInterface;
use Sulu\Component\Content\Document\WorkflowStage;

class ArticleTranslation implements RoutableInterface
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
    public $routePath;

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

    // TODO pageTitle, versions, extensions, pages, author

    /**
     * @var  RouteInterface
     */
    private $route;

    public function getId()
    {
        return $this->id;
    }

    public function getRoute()
    {
        return $this->route;
    }

    public function setRoute(RouteInterface $route)
    {
        $this->route = $route;
        $this->routePath = $route->getPath();
    }

    public function getLocale()
    {
        return $this->locale;
    }
}
