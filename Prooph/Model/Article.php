<?php

declare(strict_types=1);

namespace Sulu\Bundle\ArticleBundle\Prooph\Model;

use Prooph\EventSourcing\AggregateChanged;
use Prooph\EventSourcing\AggregateRoot;
use Sulu\Bundle\ArticleBundle\Prooph\Model\Event\ArticleCreated;
use Sulu\Bundle\ArticleBundle\Prooph\Model\Event\ArticlePublished;
use Sulu\Bundle\ArticleBundle\Prooph\Model\Event\ArticleUnpublished;
use Sulu\Bundle\ArticleBundle\Prooph\Model\Event\ArticleUpdated;
use Sulu\Component\Content\Document\WorkflowStage;

class Article extends AggregateRoot
{
    /**
     * @var string
     */
    private $id;

    /**
     * @var string
     */
    private $locale;

    /**
     * @var string
     */
    private $structureType;

    /**
     * @var array
     */
    private $structureData;

    /**
     * @var string
     */
    private $title;

    /**
     * @var int
     */
    private $creator;

    /**
     * @var int
     */
    private $changer;

    /**
     * @var \DateTime
     */
    private $created;

    /**
     * @var \DateTime
     */
    private $changed;

    /**
     * @var int
     */
    private $author;

    /**
     * @var \DateTime
     */
    private $authored;

    /**
     * @var \DateTime
     */
    private $published;

    /**
     * @var int
     */
    private $workflowStage = WorkflowStage::TEST;

    // TODO pageTitle, routePath, versions, extensions, pages

    static public function createWithData(
        string $id,
        string $locale,
        string $structureType,
        array $structureData,
        array $data,
        int $userId
    ): self {
        $obj = new self();
        $obj->recordThat(
            ArticleCreated::occur(
                $id,
                [
                    'locale' => $locale,
                    'structureType' => $structureType,
                    'structureData' => $structureData,
                    'creator' => $userId,
                    'created' => (new \DateTime())->format(\DateTime::ATOM),
                    'changer' => $userId,
                    'changed' => (new \DateTime())->format(\DateTime::ATOM),
                    'data' => $data,
                ]
            )
        );

        return $obj;
    }

    public function publish(string $locale, int $userId)
    {
        $this->recordThat(
            ArticlePublished::occur(
                $this->id,
                [
                    'locale' => $locale,
                    'changer' => $userId,
                    'changed' => (new \DateTime())->format(\DateTime::ATOM),
                    'published' => (new \DateTime())->format(\DateTime::ATOM),
                ]
            )
        );

        return $this;
    }

    public function unpublish(string $locale, int $userId)
    {
        $this->recordThat(
            ArticleUnpublished::occur(
                $this->id,
                [
                    'locale' => $locale,
                    'changer' => $userId,
                    'changed' => (new \DateTime())->format(\DateTime::ATOM),
                ]
            )
        );

        return $this;
    }

    public function updateWithData(
        string $locale,
        string $structureType,
        array $structureData,
        array $data,
        int $userId
    ): self {
        if ($structureType !== $this->structureType) {
            // TODO change template
        }

        $structureData = array_diff($structureData, $this->structureData);

        $this->recordThat(
            ArticleUpdated::occur(
                $this->id,
                [
                    'locale' => $locale,
                    'structureType' => $structureType,
                    'structureData' => $structureData,
                    'changer' => $userId,
                    'changed' => (new \DateTime())->format(\DateTime::ATOM),
                    'data' => $data,
                ]
            )
        );

        return $this;
    }

    protected function aggregateId(): string
    {
        return $this->id;
    }

    protected function apply(AggregateChanged $event): void
    {
        switch (get_class($event)) {
            case ArticleCreated::class:
                /** @var ArticleCreated $event */
                $this->id = $event->aggregateId();
                $this->locale = $event->locale();
                $this->structureType = $event->structureType();
                $this->structureData = $event->structureData();
                $this->changed = $event->changed();
                $this->changer = $event->changer();
                $this->authored = $this->created = $event->created();
                $this->author = $this->creator = $event->creator();
                $this->title = $event->structureData()['title'];
                break;
            case ArticlePublished::class:
                /** @var ArticlePublished $event */
                $this->id = $event->aggregateId();
                $this->locale = $event->locale();
                $this->workflowStage = WorkflowStage::PUBLISHED;
                $this->changed = $event->changed();
                $this->changer = $event->changer();
                $this->published = $event->published();
                break;
            case ArticleUnpublished::class:
                /** @var ArticleUnpublished $event */
                $this->id = $event->aggregateId();
                $this->locale = $event->locale();
                $this->workflowStage = WorkflowStage::TEST;
                $this->changed = $event->changed();
                $this->changer = $event->changer();
                break;
            case ArticleUpdated::class:
                /** @var ArticleUpdated $event */
                $this->id = $event->aggregateId();
                $this->locale = $event->locale();
                $this->structureData = array_merge($this->structureData, $event->structureData());
                break;
        }
    }
}
