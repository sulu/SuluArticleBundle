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
use Sulu\Component\Content\Compat\StructureManagerInterface;
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

        $data = array_merge($data, $this->resolveContent($article));

        return $data;
    }

    /**
     * Returns article page by page-number.
     *
     * @param ArticleDocument $article
     * @param int $pageNumber
     *
     * @return ArticleDocument|ArticlePageDocument
     */
    private function getArticleForPage(ArticleDocument $article, $pageNumber)
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
     *
     * @param ArticleInterface $article
     *
     * @return array
     */
    private function resolveContent(ArticleInterface $article)
    {
        $structure = $this->structureManager->getStructure($article->getStructureType(), 'article');
        $structure->setDocument($article);

        $data = $article->getStructure()->toArray();

        $extension = [];
        foreach ($article->getExtensionsData()->toArray() as $name => $extensionData) {
            $extension[$name] = $this->extensionManager->getExtension('article', $name)->getContentData($extensionData);
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
