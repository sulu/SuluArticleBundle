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

namespace Sulu\Bundle\ArticleBundle\Article\Domain\Model;

use Sulu\Bundle\ContentBundle\Content\Domain\Model\ContentRichEntityInterface;
use Sulu\Bundle\ContentBundle\Content\Domain\Model\DimensionContentTrait;
use Sulu\Bundle\ContentBundle\Content\Domain\Model\ExcerptTrait;
use Sulu\Bundle\ContentBundle\Content\Domain\Model\RoutableTrait;
use Sulu\Bundle\ContentBundle\Content\Domain\Model\SeoTrait;
use Sulu\Bundle\ContentBundle\Content\Domain\Model\TemplateTrait;
use Sulu\Bundle\ContentBundle\Content\Domain\Model\WorkflowTrait;

/**
 * @experimental
 */
class ArticleDimensionContent implements ArticleDimensionContentInterface
{
    use DimensionContentTrait;
    use ExcerptTrait;
    use RoutableTrait;
    use SeoTrait;
    use TemplateTrait {
        getTemplateData as parentGetTemplateData;
        setTemplateData as parentSetTemplateData;
    }
    use WorkflowTrait;

    /**
     * @var int
     */
    protected $id;

    /**
     * @var ArticleInterface
     */
    protected $article;

    /**
     * @var string|null
     */
    protected $title;

    public function __construct(ArticleInterface $article)
    {
        $this->article = $article;
    }

    /**
     * @return ArticleInterface
     */
    public function getResource(): ContentRichEntityInterface
    {
        return $this->article;
    }

    public function getTitle(): ?string
    {
        return $this->title;
    }

    public function setTitle(?string $title): void
    {
        $this->title = $title;
    }

    public function getTemplateData(): array
    {
        $data = $this->parentGetTemplateData();
        $data['title'] = $this->getTitle();

        return $data;
    }

    public function setTemplateData(array $templateData): void
    {
        $this->setTitle($templateData['title']);
        unset($templateData['title']);
        $this->parentSetTemplateData($templateData);
    }

    public static function getTemplateType(): string
    {
        return ArticleInterface::TEMPLATE_TYPE;
    }

    public static function getResourceKey(): string
    {
        return ArticleInterface::RESOURCE_KEY;
    }
}
