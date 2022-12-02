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

namespace Sulu\Bundle\ArticleBundle\Infrastructure\SuluHeadlessBundle\ContentTypeResolver;

use Sulu\Bundle\HeadlessBundle\Content\ContentTypeResolver\ContentTypeResolverInterface;
use Sulu\Bundle\HeadlessBundle\Content\ContentView;
use Sulu\Component\Content\Compat\PropertyInterface;

class SingleArticleSelectionResolver implements ContentTypeResolverInterface
{
    public static function getContentType(): string
    {
        return 'single_article_selection';
    }

    /**
     * @var ContentTypeResolverInterface
     */
    private $articleSelectionResolver;

    public function __construct(ContentTypeResolverInterface $articleSelectionResolver)
    {
        $this->articleSelectionResolver = $articleSelectionResolver;
    }

    public function resolve($data, PropertyInterface $property, string $locale, array $attributes = []): ContentView
    {
        /** @var string|null $id */
        $id = $data;

        if (empty($id)) {
            return new ContentView(null, ['id' => null]);
        }

        $content = $this->articleSelectionResolver->resolve([$id], $property, $locale, $attributes);

        /** @var mixed[]|null $contentData */
        $contentData = $content->getContent();

        return new ContentView($contentData[0] ?? null, ['id' => $id]);
    }
}
