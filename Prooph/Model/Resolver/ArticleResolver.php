<?php

namespace Sulu\Bundle\ArticleBundle\Prooph\Model\Resolver;

use Sulu\Bundle\ArticleBundle\Prooph\Model\Article;
use Sulu\Bundle\ArticleBundle\Prooph\Model\ArticleTranslation;
use Sulu\Bundle\ArticleBundle\Prooph\Model\Event\ChangeTranslationStructure;
use Sulu\Bundle\ArticleBundle\Prooph\Model\Event\CreateArticle;
use Sulu\Bundle\ArticleBundle\Prooph\Model\Event\CreateTranslation;
use Sulu\Bundle\ArticleBundle\Prooph\Model\Event\ModifyTranslationStructure;
use Sulu\Bundle\ArticleBundle\Prooph\Model\Event\PublishTranslation;
use Sulu\Bundle\ArticleBundle\Prooph\Model\Event\RemoveArticle;
use Sulu\Bundle\ArticleBundle\Prooph\Model\Event\UnpublishTranslation;
use Sulu\Component\Content\Document\WorkflowStage;

class ArticleResolver implements EventResolverInterface
{
    public function getResolvingEvents(): array
    {
        return [
            CreateArticle::class => ['create'],
            CreateTranslation::class => ['createTranslation'],
            ChangeTranslationStructure::class => ['changeTranslationStructure'],
            ModifyTranslationStructure::class => ['modifyTranslationStructure'],
            PublishTranslation::class => ['publishTranslation'],
            UnpublishTranslation::class => ['unpublishTranslation'],
            RemoveArticle::class => ['removeArticle'],
        ];
    }

    public function create(Article $article, CreateArticle $event): void
    {
        $article->id = $event->aggregateId();
        $article->createdAt = $event->createdAt();
        $article->createdBy = $event->createdBy();
    }

    public function createTranslation(Article $article, CreateTranslation $event): void
    {
        $article->translations[$event->locale()] = $translation = new ArticleTranslation();
        $translation->locale = $event->locale();
        $translation->structureType = $event->structureType();
        $translation->createdAt = $event->createdAt();
        $translation->createdBy = $event->createdBy();
    }

    public function changeTranslationStructure(Article $article, ChangeTranslationStructure $event)
    {
        $translation = $article->findTranslation($event->locale());
        $translation->structureType = $event->structureType();
        $translation->modifiedAt = $event->createdAt();
        $translation->modifiedBy = $event->createdBy();
        $article->modifiedAt = $event->createdAt();
        $article->modifiedBy = $event->createdBy();
    }

    public function modifyTranslationStructure(Article $article, ModifyTranslationStructure $event)
    {
        $translation = $article->findTranslation($event->locale());
        $translation->structureData = array_merge($translation->structureData, $event->structureData());
        $translation->modifiedAt = $event->createdAt();
        $translation->modifiedBy = $event->createdBy();
        $article->modifiedAt = $event->createdAt();
        $article->modifiedBy = $event->createdBy();
    }

    public function publishTranslation(Article $article, PublishTranslation $event)
    {
        $translation = $article->findTranslation($event->locale());
        $translation->workflowStage = WorkflowStage::PUBLISHED;
        $translation->publishedAt = $event->createdAt();
        $translation->publishedBy = $event->createdBy();
        $translation->modifiedAt = $event->createdAt();
        $translation->modifiedBy = $event->createdBy();
        $article->modifiedAt = $event->createdAt();
        $article->modifiedBy = $event->createdBy();
    }

    public function unpublishTranslation(Article $article, UnpublishTranslation $event)
    {
        $translation = $article->findTranslation($event->locale());
        $translation->workflowStage = WorkflowStage::TEST;
        $translation->modifiedAt = $event->createdAt();
        $translation->modifiedBy = $event->createdBy();
        $article->modifiedAt = $event->createdAt();
        $article->modifiedBy = $event->createdBy();
    }

    public function removeArticle(Article $article, RemoveArticle $event)
    {
        $article->translations = [];
        $article->removedAt = $event->createdAt();
        $article->removedBy = $event->createdBy();
        $article->modifiedAt = $event->createdAt();
        $article->modifiedBy = $event->createdBy();
    }
}
