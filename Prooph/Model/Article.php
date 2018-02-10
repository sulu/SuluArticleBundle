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
use Sulu\Bundle\ArticleBundle\Prooph\Model\Resolver\EventResolverPool;

class Article extends AggregateRoot
{
    /**
     * @var string
     */
    public $id;

    /**
     * @var int
     */
    public $createdBy;

    /**
     * @var \DateTimeImmutable
     */
    public $createdAt;

    /**
     * @var int
     */
    public $modifiedBy;

    /**
     * @var \DateTimeImmutable
     */
    public $modifiedAt;

    /**
     * @var int
     */
    public $removedBy;

    /**
     * @var \DateTimeImmutable
     */
    public $removedAt;

    /**
     * @var ArticleTranslation[]
     */
    public $translations = [];

    /**
     * @var EventResolverPool
     */
    static public $eventResolver;

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
        self::$eventResolver->resolve($this, $event);
    }

    /**
     * Allow extensibility from outside this class.
     */
    public function recordThat(AggregateChanged $event): void
    {
        parent::recordThat($event);
    }
}
