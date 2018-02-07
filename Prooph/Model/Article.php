<?php

declare(strict_types=1);

namespace Sulu\Bundle\ArticleBundle\Prooph\Model;

use Prooph\EventSourcing\AggregateChanged;
use Prooph\EventSourcing\AggregateRoot;
use Sulu\Bundle\ArticleBundle\Prooph\Model\Event\ChangeTranslationStructure;
use Sulu\Bundle\ArticleBundle\Prooph\Model\Event\CreateArticle;
use Sulu\Bundle\ArticleBundle\Prooph\Model\Event\CreateTranslation;
use Sulu\Bundle\ArticleBundle\Prooph\Model\Event\ModifyTranslationStructure;
use Sulu\Bundle\ArticleBundle\Prooph\Model\Event\PublishTranslation;
use Sulu\Bundle\ArticleBundle\Prooph\Model\Event\RemoveArticle;
use Sulu\Bundle\ArticleBundle\Prooph\Model\Event\UnpublishTranslation;
use Sulu\Component\Content\Document\WorkflowStage;

class Article extends AggregateRoot
{
    /**
     * @var string
     */
    private $id;

    /**
     * @var int
     */
    private $createdBy;

    /**
     * @var \DateTimeImmutable
     */
    private $createdAt;

    /**
     * @var int
     */
    private $modifiedBy;

    /**
     * @var \DateTimeImmutable
     */
    private $modifiedAt;

    /**
     * @var int
     */
    private $removedBy;

    /**
     * @var \DateTimeImmutable
     */
    private $removedAt;

    /**
     * @var ArticleTranslation[]
     */
    private $translations = [];

    static public function create(string $id, int $userId)
    {
        $article = new self();
        $article->recordThat(CreateArticle::occur($id, ['createdBy' => $userId]));

        return $article;
    }

    public function modifyTranslationStructure(
        string $locale,
        string $structureType,
        array $structureData,
        int $userId,
        array $requestData
    ) {
        $translation = $this->findTranslation($locale);
        if (!$translation) {
            $this->recordThat(
                CreateTranslation::occur(
                    $this->id,
                    [
                        'locale' => $locale,
                        'createdBy' => $userId,
                        'structureType' => $structureType,
                        'requestData' => $requestData,
                    ]
                )
            );

            $translation = new ArticleTranslation();
        } elseif ($structureType !== $translation->structureType) {
            $this->recordThat(
                ChangeTranslationStructure::occur(
                    $this->id,
                    [
                        'locale' => $locale,
                        'structureType' => $structureType,
                        'createdBy' => $userId,
                    ]
                )
            );
        }

        $this->recordThat(
            ModifyTranslationStructure::occur(
                $this->id,
                [
                    'locale' => $locale,
                    'structureData' => array_diff($structureData, $translation->structureData),
                    'createdBy' => $userId,
                    'requestData' => $requestData,
                ]
            )
        );

        return $this;
    }

    public function publishTranslation(string $locale, int $userId)
    {
        $this->recordThat(
            PublishTranslation::occur(
                $this->id,
                [
                    'locale' => $locale,
                    'createdBy' => $userId,
                ]
            )
        );

        return $this;
    }

    public function unpublishTranslation(string $locale, int $userId)
    {
        $this->recordThat(
            UnpublishTranslation::occur(
                $this->id,
                [
                    'locale' => $locale,
                    'createdBy' => $userId,
                ]
            )
        );

        return $this;
    }

    public function remove(int $userId)
    {
        $this->recordThat(RemoveArticle::occur($this->id, ['createdBy' => $userId]));

        return $this;
    }

    protected function aggregateId(): string
    {
        return $this->id;
    }

    public function findTranslation(string $locale): ?ArticleTranslation
    {
        if (!array_key_exists($locale, $this->translations)) {
            return null;
        }

        return $this->translations[$locale];
    }

    protected function apply(AggregateChanged $event): void
    {
        if ($event instanceof CreateArticle) {
            $this->id = $event->aggregateId();
            $this->createdAt = $event->createdAt();
            $this->createdBy = $event->createdBy();
        } elseif ($event instanceof CreateTranslation) {
            $this->translations[$event->locale()] = $translation = new ArticleTranslation();
            $translation->locale = $event->locale();
            $translation->structureType = $event->structureType();
            $translation->createdAt = $event->createdAt();
            $translation->createdBy = $event->createdBy();
        } elseif ($event instanceof ChangeTranslationStructure) {
            $translation = $this->findTranslation($event->locale());
            $translation->structureType = $event->structureType();
            $translation->modifiedAt = $event->createdAt();
            $translation->modifiedBy = $event->createdBy();
            $this->modifiedAt = $event->createdAt();
            $this->modifiedBy = $event->createdBy();
        } elseif ($event instanceof ModifyTranslationStructure) {
            $translation = $this->findTranslation($event->locale());
            $translation->structureData = array_merge($translation->structureData, $event->structureData());
            $translation->modifiedAt = $event->createdAt();
            $translation->modifiedBy = $event->createdBy();
            $this->modifiedAt = $event->createdAt();
            $this->modifiedBy = $event->createdBy();
        } elseif ($event instanceof PublishTranslation) {
            $translation = $this->findTranslation($event->locale());
            $translation->workflowStage = WorkflowStage::PUBLISHED;
            $translation->publishedAt = $event->createdAt();
            $translation->publishedBy = $event->createdBy();
            $translation->modifiedAt = $event->createdAt();
            $translation->modifiedBy = $event->createdBy();
            $this->modifiedAt = $event->createdAt();
            $this->modifiedBy = $event->createdBy();
        } elseif ($event instanceof UnpublishTranslation) {
            $translation = $this->findTranslation($event->locale());
            $translation->workflowStage = WorkflowStage::TEST;
            $translation->modifiedAt = $event->createdAt();
            $translation->modifiedBy = $event->createdBy();
            $this->modifiedAt = $event->createdAt();
            $this->modifiedBy = $event->createdBy();
        } elseif ($event instanceof RemoveArticle) {
            $this->translations = [];
            $this->removedAt = $event->createdAt();
            $this->removedBy = $event->createdBy();
            $this->modifiedAt = $event->createdAt();
            $this->modifiedBy = $event->createdBy();
        }
    }
}
