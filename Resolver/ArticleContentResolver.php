<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\ArticleBundle\Resolver;

use JMS\Serializer\SerializationContext;
use Sulu\Bundle\ArticleBundle\Document\ArticleDocument;
use Sulu\Bundle\ArticleBundle\Document\ArticleInterface;
use Sulu\Bundle\ArticleBundle\Document\ArticlePageDocument;
use Sulu\Bundle\ArticleBundle\Document\Structure\ContentProxyFactory;
use Sulu\Component\Content\Compat\Structure\StructureBridge;
use Sulu\Component\Content\Compat\StructureManagerInterface;
use Sulu\Component\Content\Document\Extension\ExtensionContainer;
use Sulu\Component\Content\Extension\ExtensionManagerInterface;
use Sulu\Component\Serializer\ArraySerializerInterface;

class ArticleContentResolver implements ArticleContentResolverInterface
{
    /**
     * @var ArraySerializerInterface
     */
    private $serializer;

    /**
     * @var StructureManagerInterface
     */
    private $structureManager;

    /**
     * @var ExtensionManagerInterface
     */
    private $extensionManager;

    /**
     * @var ContentProxyFactory
     */
    private $contentProxyFactory;

    public function __construct(
        ArraySerializerInterface $serializer,
        StructureManagerInterface $structureManager,
        ExtensionManagerInterface $extensionManager,
        ContentProxyFactory $contentProxyFactory
    ) {
        $this->serializer = $serializer;
        $this->structureManager = $structureManager;
        $this->extensionManager = $extensionManager;
        $this->contentProxyFactory = $contentProxyFactory;
    }

    public function resolve(ArticleInterface $article, int $pageNumber = 1)
    {
        $data = $this->serializer->serialize(
            $article,
            SerializationContext::create()
                ->setSerializeNull(true)
                ->setGroups(['website', 'content'])
                ->setAttribute('pageNumber', $pageNumber)
                ->setAttribute('urls', true)
        );

        if ($article instanceof ArticlePageDocument) {
            $article = $article->getParent();
        }

        $data['page'] = $pageNumber;
        $data['pages'] = $article->getPages();

        $article = $this->getArticleForPage($article, $pageNumber);

        $data['id'] = $article->getArticleUuid();
        $data['uuid'] = $article->getArticleUuid();
        $data['pageUuid'] = $article->getPageUuid();

        $data = \array_merge($data, $this->resolveContent($article));

        return $data;
    }

    /**
     * Returns article page by page-number.
     */
    private function getArticleForPage(ArticleDocument $article, int $pageNumber): ArticleDocument
    {
        $children = $article->getChildren();
        if (null === $children || 1 === $pageNumber) {
            return $article;
        }

        foreach ($children as $child) {
            if ($child instanceof ArticlePageDocument && $child->getPageNumber() === $pageNumber) {
                return $child;
            }
        }

        return $article;
    }

    /**
     * Returns content and view of article.
     */
    private function resolveContent(ArticleDocument $article): array
    {
        /** @var StructureBridge $structure */
        $structure = $this->structureManager->getStructure($article->getStructureType(), 'article');
        $structure->setDocument($article);

        $data = $article->getStructure()->toArray();

        $extension = [];

        $extensionData = $article->getExtensionsData();
        if ($extensionData instanceof ExtensionContainer) {
            $extensionData = $extensionData->toArray();
        }

        foreach ($extensionData as $name => $value) {
            $extension[$name] = $this->extensionManager->getExtension('article', $name)->getContentData($value);
        }

        $content = $this->contentProxyFactory->createContentProxy($structure, $data);
        $view = $this->contentProxyFactory->createViewProxy($structure, $data);

        return [
            'content' => $content,
            'view' => $view,
            'extension' => $extension,
        ];
    }
}
