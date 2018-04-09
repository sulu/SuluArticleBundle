<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\ArticleBundle\Tests\Unit\Twig;

use ONGR\ElasticsearchBundle\Result\DocumentIterator;
use Sulu\Bundle\ArticleBundle\Content\ArticleResourceItem;
use Sulu\Bundle\ArticleBundle\Content\ArticleResourceItemFactory;
use Sulu\Bundle\ArticleBundle\Document\ArticleDocument;
use Sulu\Bundle\ArticleBundle\Document\ArticleViewDocument;
use Sulu\Bundle\ArticleBundle\Document\Repository\ArticleViewDocumentRepository;
use Sulu\Bundle\ArticleBundle\Twig\ArticleViewDocumentTwigExtension;
use Sulu\Bundle\WebsiteBundle\ReferenceStore\ReferenceStore;
use Sulu\Component\Content\Metadata\Factory\StructureMetadataFactory;
use Sulu\Component\Content\Metadata\StructureMetadata;
use Sulu\Component\DocumentManager\Metadata\MetadataFactory;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

class ArticleViewDocumentTwigExtensionTest extends \PHPUnit_Framework_TestCase
{
    public function testFindSimilar()
    {
        $articleDocuments = $this->getArticleDocuments();
        $articleViewDocuments = $this->getArticleViewDocuments($articleDocuments);
        $articleResourceItems = $this->getArticleResourceItems($articleDocuments, $articleViewDocuments);
        $documentIterator = $this->getDocumentIterator($articleViewDocuments);
        $metadataFactory = $this->getMetadataFactory();

        $articleViewDocumentRepository = $this->prophesize(ArticleViewDocumentRepository::class);
        $articleViewDocumentRepository->findSimilar(
            $articleDocuments[0]->getUuid(),
            5,
            [
                $articleDocuments[0]->getStructureType(),
            ],
            $articleDocuments[0]->getLocale()
        )->willReturn($documentIterator);

        $articleResourceItemFactory = $this->prophesize(ArticleResourceItemFactory::class);
        $articleResourceItemFactory->createResourceItem($articleViewDocuments[1])->willReturn($articleResourceItems[1]);
        $articleResourceItemFactory->createResourceItem($articleViewDocuments[2])->willReturn($articleResourceItems[2]);

        $request = $this->prophesize(Request::class);
        $request->get('object')->willReturn($articleDocuments[0]);
        $request->getLocale()->willReturn('de');

        $requestStack = $this->prophesize(RequestStack::class);
        $requestStack->getCurrentRequest()->wilLReturn($request);

        $referenceStore = $this->prophesize(ReferenceStore::class);
        $referenceStore->add($articleDocuments[1]->getUuid())->shouldBeCalled();
        $referenceStore->add($articleDocuments[2]->getUuid())->shouldBeCalled();

        $extension = new ArticleViewDocumentTwigExtension(
            $articleViewDocumentRepository->reveal(),
            $articleResourceItemFactory->reveal(),
            $referenceStore->reveal(),
            $metadataFactory->reveal(),
            $requestStack->reveal()
        );

        $this->assertEquals(
            [
                $articleResourceItems[1],
                $articleResourceItems[2],
            ],
            $extension->loadSimilar()
        );
    }

    public function testFindRecent()
    {
        $articleDocuments = $this->getArticleDocuments();
        $articleViewDocuments = $this->getArticleViewDocuments($articleDocuments);
        $articleResourceItems = $this->getArticleResourceItems($articleDocuments, $articleViewDocuments);
        $documentIterator = $this->getDocumentIterator($articleViewDocuments);
        $metadataFactory = $this->getMetadataFactory();

        $articleViewDocumentRepository = $this->prophesize(ArticleViewDocumentRepository::class);
        $articleViewDocumentRepository->findRecent(
            $articleDocuments[0]->getUuid(),
            5,
            [
                $articleDocuments[0]->getStructureType(),
            ],
            $articleDocuments[0]->getLocale()
        )->willReturn($documentIterator);

        $articleResourceItemFactory = $this->prophesize(ArticleResourceItemFactory::class);
        $articleResourceItemFactory->createResourceItem($articleViewDocuments[1])->willReturn($articleResourceItems[1]);
        $articleResourceItemFactory->createResourceItem($articleViewDocuments[2])->willReturn($articleResourceItems[2]);

        $request = $this->prophesize(Request::class);
        $request->get('object')->willReturn($articleDocuments[0]);
        $request->getLocale()->willReturn('de');

        $requestStack = $this->prophesize(RequestStack::class);
        $requestStack->getCurrentRequest()->wilLReturn($request);

        $referenceStore = $this->prophesize(ReferenceStore::class);
        $referenceStore->add($articleDocuments[1]->getUuid())->shouldBeCalled();
        $referenceStore->add($articleDocuments[2]->getUuid())->shouldBeCalled();

        $extension = new ArticleViewDocumentTwigExtension(
            $articleViewDocumentRepository->reveal(),
            $articleResourceItemFactory->reveal(),
            $referenceStore->reveal(),
            $metadataFactory->reveal(),
            $requestStack->reveal()
        );

        $this->assertEquals(
            [
                $articleResourceItems[1],
                $articleResourceItems[2],
            ],
            $extension->loadRecent()
        );
    }

    /**
     * @return MetadataFactory
     */
    private function getMetadataFactory()
    {
        $metadata = new StructureMetadata();
        $metadata->setTags([
            ['name' => 'sulu_article.type', 'attributes' => ['type' => 'blog']]
        ]);

        $metadataFactory = $this->prophesize(StructureMetadataFactory::class);
        $metadataFactory->getStructureMetadata('article', 'blog')->willReturn($metadata);

        return $metadataFactory;
    }

    /**
     * @return array
     */
    private function getArticleDocuments()
    {
        $ids = ['123-123-123', '321-321-321', '111-111-111'];

        return array_map(
            function ($id) {
                $articleDocument = new ArticleDocument();
                $articleDocument->setUuid($id);
                $articleDocument->setLocale('de');
                $articleDocument->setStructureType('blog');

                return $articleDocument;
            },
            array_values($ids)
        );
    }

    /**
     * @param array $articleDocuments
     *
     * @return array
     */
    private function getArticleViewDocuments(array $articleDocuments)
    {
        return  array_map(
            function ($articleDocument) {
                $articleViewDocument = new ArticleViewDocument($articleDocument->getUuid());
                $articleViewDocument->setLocale($articleDocument->getLocale());
                $articleViewDocument->setStructureType($articleDocument->getStructureType());

                return $articleViewDocument;
            },
            array_values($articleDocuments)
        );
    }

    /**
     * @param array $articleDocuments
     * @param array $articleViewDocuments
     *
     * @return array
     */
    private function getArticleResourceItems(array $articleDocuments, array $articleViewDocuments)
    {
        $articleResourceItems = [];

        foreach ($articleViewDocuments as $key => $value) {
            $articleResourceItems[] = new ArticleResourceItem($articleViewDocuments[$key], $articleDocuments[$key]);
        }

        return $articleResourceItems;
    }

    /**
     * @param array $articleViewDocuments
     *
     * @return DocumentIterator
     */
    private function getDocumentIterator(array $articleViewDocuments)
    {
        $documentIteratorCount = 1;

        $documentIterator = $this->prophesize(DocumentIterator::class);
        $documentIterator->rewind()->willReturn(0);
        $documentIterator->next()->willReturn($documentIteratorCount);
        $documentIterator->current()->will(function() use (&$documentIteratorCount, $articleViewDocuments) {
            return $articleViewDocuments[$documentIteratorCount++];
        });
        $documentIterator->valid()->will(function() use (&$documentIteratorCount, $articleViewDocuments) {
            if (array_key_exists($documentIteratorCount, $articleViewDocuments)) {
                return true;
            }

            return false;
        });

        return $documentIterator;
    }
}
