<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\ArticleBundle\Tests\Unit\Document\Index;

use ONGR\ElasticsearchBundle\Service\Manager;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Sulu\Bundle\ArticleBundle\Document\ArticleDocument;
use Sulu\Bundle\ArticleBundle\Document\Index\ArticleGhostIndexer;
use Sulu\Bundle\ArticleBundle\Document\Index\ArticleIndexer;
use Sulu\Bundle\ArticleBundle\Document\Index\DocumentFactoryInterface;
use Sulu\Bundle\ArticleBundle\Document\Index\Factory\ExcerptFactory;
use Sulu\Bundle\ArticleBundle\Document\Index\Factory\SeoFactory;
use Sulu\Bundle\ArticleBundle\Document\Resolver\WebspaceResolver;
use Sulu\Bundle\ContactBundle\Entity\ContactRepository;
use Sulu\Bundle\DocumentManagerBundle\Bridge\DocumentInspector;
use Sulu\Bundle\SecurityBundle\UserManager\UserManager;
use Sulu\Component\Content\Metadata\Factory\Exception\StructureTypeNotFoundException;
use Sulu\Component\Content\Metadata\Factory\StructureMetadataFactoryInterface;
use Sulu\Component\DocumentManager\DocumentManagerInterface;
use Sulu\Component\Webspace\Manager\WebspaceManagerInterface;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

class ArticleIndexerTest extends TestCase
{
    public function provideIndexerClasses(): array
    {
        return [
            'indexer, factory returns null' => [ArticleIndexer::class, false],
            'indexer, factory throws' => [ArticleIndexer::class, true],
            'ghost indexer, factory returns null' => [ArticleGhostIndexer::class, false],
            'ghost indexer, factory throws' => [ArticleGhostIndexer::class, true],
        ];
    }

    /**
     * @dataProvider provideIndexerClasses
     */
    public function testIndexSkipsArticleWithMissingStructure(string $indexerClass, bool $throws): void
    {
        $structureMetadataFactory = $this->createMock(StructureMetadataFactoryInterface::class);
        $structureMetadataFactoryMethod = $structureMetadataFactory->method('getStructureMetadata')
            ->with('article', 'removed_template');
        if ($throws) {
            $structureMetadataFactoryMethod->willThrowException(
                new StructureTypeNotFoundException('Could not load structure type "removed_template"')
            );
        } else {
            $structureMetadataFactoryMethod->willReturn(null);
        }

        $manager = $this->createMock(Manager::class);
        $manager->expects($this->never())->method('persist');

        $eventDispatcher = $this->createMock(EventDispatcherInterface::class);
        $eventDispatcher->expects($this->never())->method('dispatch');

        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects($this->once())
            ->method('warning')
            ->with($this->stringContains('"removed_template"'));

        $arguments = [
            $structureMetadataFactory,
            $this->createMock(UserManager::class),
            $this->createMock(ContactRepository::class),
            $this->createMock(DocumentFactoryInterface::class),
            $manager,
            $this->createMock(ExcerptFactory::class),
            $this->createMock(SeoFactory::class),
            $eventDispatcher,
            $this->createMock(TranslatorInterface::class),
            $this->createMock(DocumentManagerInterface::class),
            $this->createMock(DocumentInspector::class),
            $this->createMock(WebspaceResolver::class),
            [],
        ];
        if (ArticleGhostIndexer::class === $indexerClass) {
            $arguments[] = $this->createMock(WebspaceManagerInterface::class);
        }
        $arguments[] = $logger;

        $indexer = new $indexerClass(...$arguments);

        $document = new ArticleDocument();
        $document->setUuid('123-123-123');
        $document->setLocale('de');
        $document->setStructureType('removed_template');

        $indexer->index($document);
    }
}
