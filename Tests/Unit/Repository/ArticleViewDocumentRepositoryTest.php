<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\ArticleBundle\Tests\Unit\Repository;

use Elasticsearch\Common\Exceptions\NoNodesAvailableException;
use ONGR\ElasticsearchBundle\Result\Converter;
use ONGR\ElasticsearchBundle\Result\DocumentIterator;
use ONGR\ElasticsearchBundle\Service\Manager;
use ONGR\ElasticsearchBundle\Service\Repository;
use ONGR\ElasticsearchDSL\Search;
use ONGR\ElasticsearchDSL\Sort\FieldSort;
use Prophecy\Argument;
use Psr\Log\LoggerInterface;
use Sulu\Bundle\ArticleBundle\Document\ArticleViewDocument;
use Sulu\Bundle\ArticleBundle\Document\Repository\ArticleViewDocumentRepository;

class ArticleViewDocumentRepositoryTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var Manager
     */
    private $searchManager;

    /**
     * @var Repository
     */
    private $repository;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var Converter
     */
    private $converter;

    /**
     * @var Search
     */
    private $search;

    /**
     * @var ArticleViewDocumentRepository
     */
    private $articleViewDocumentRepository;

    protected function setUp()
    {
        $this->search = $this->prophesize(Search::class);

        $this->repository = $this->prophesize(Repository::class);
        $this->repository->createSearch()->willReturn(new Search());

        $this->converter = $this->prophesize(Converter::class);

        $this->searchManager = $this->prophesize(Manager::class);
        $this->searchManager->getRepository(ArticleViewDocument::class)->willReturn($this->repository->reveal());
        $this->searchManager->getConverter()->willReturn($this->converter->reveal());
        $this->searchManager->getConfig()->willReturn([]);

        $this->logger = $this->prophesize(LoggerInterface::class);

        $this->articleViewDocumentRepository = new ArticleViewDocumentRepository(
            $this->searchManager->reveal(),
            ArticleViewDocument::class,
            ['title', 'teaser_description']
        );

        $this->articleViewDocumentRepository->setLogger($this->logger->reveal());
    }

    public function dataProvider()
    {
        return [
            [],
            ['123-123-123', ['blog', 'article']],
            ['321-321-321', ['blog'], 'en', 10],
        ];
    }

    /**
     * @dataProvider dataProvider
     *
     * @param string $uuid
     * @param array $types
     * @param string $locale
     * @param int $limit
     */
    public function testFindSimilar($uuid = '123-123-123', array $types = ['blog'], $locale = 'de', $limit = 12)
    {
        $termQuery = [];
        foreach ($types as $type) {
            $termQuery[] = [
                'term' => ['type' => $type],
            ];
        }

        $expectedSearch = [
            'bool' => [
                'filter' => [
                    [
                        'term' => ['locale' => $locale],
                    ],
                ],
                'must' => [
                    [
                        'bool' => [
                            'should' => $termQuery,
                        ],
                    ],
                    [
                        'more_like_this' => [
                            'like' => null,
                            'fields' => ['title', 'teaser_description'],
                            'min_term_freq' => 1,
                            'min_doc_freq' => 2,
                            'ids' => [$uuid . '-' . $locale],
                        ],
                    ],
                ],
            ],
        ];

        $documentIterator = $this->prophesize(DocumentIterator::class);

        $this->repository->findDocuments(Argument::that(function(Search $search) use ($expectedSearch, $limit) {
            $this->assertEquals($expectedSearch, $search->getQueries()->toArray());
            $this->assertEquals($limit, $search->getSize());
            $this->assertCount(0, $search->getSorts());

            return true;
        }))->willReturn($documentIterator->reveal());

        $this->assertSame(
            $documentIterator->reveal(),
            $this->articleViewDocumentRepository->findSimilar($uuid, $limit, $types, $locale)
        );
    }

    public function testFindSimilarNoNodesAvailable()
    {
        $this->repository->findDocuments(Argument::any())->willThrow(new NoNodesAvailableException('No nodes alive'));

        $this->logger->error('No nodes alive')->shouldBeCalled();

        $result = $this->articleViewDocumentRepository->findSimilar('123-123-123');
        $this->assertInstanceOf(DocumentIterator::class, $result);
        $this->assertCount(0, $result);
    }

    /**
     * @dataProvider dataProvider
     *
     * @param string $excludeUuid
     * @param array $types
     * @param string $locale
     * @param int $limit
     */
    public function testFindRecent(
        $excludeUuid = '123-123-123',
        array $types = ['blog'],
        $locale = 'de',
        $limit = 12
    ) {
        $termQuery = [];
        foreach ($types as $type) {
            $termQuery[] = [
                'term' => ['type' => $type],
            ];
        }

        $expectedSearch = [
            'bool' => [
                'filter' => [
                    [
                        'term' => ['locale' => $locale],
                    ],
                ],
                'must' => [
                    [
                        'bool' => [
                            'should' => $termQuery,
                        ],
                    ],
                ],
                'must_not' => [
                    [
                        'term' => [
                            'uuid' => $excludeUuid,
                        ],
                    ],
                ],
            ],
        ];

        $documentIterator = $this->prophesize(DocumentIterator::class);

        $this->repository->findDocuments(Argument::that(function(Search $search) use ($expectedSearch, $limit) {
            $this->assertEquals($expectedSearch, $search->getQueries()->toArray());
            $this->assertEquals($limit, $search->getSize());
            $this->assertCount(1, $search->getSorts());
            $this->assertEquals(new FieldSort('authored', FieldSort::DESC), current($search->getSorts()));

            return true;
        }))->willReturn($documentIterator->reveal());

        $this->assertSame(
            $documentIterator->reveal(),
            $this->articleViewDocumentRepository->findRecent($excludeUuid, $limit, $types, $locale)
        );
    }

    public function testFindRecentNoNodesAvailable()
    {
        $this->repository->findDocuments(Argument::any())->willThrow(new NoNodesAvailableException('No nodes alive'));

        $this->logger->error('No nodes alive')->shouldBeCalled();

        $result = $this->articleViewDocumentRepository->findRecent('123-123-123');
        $this->assertInstanceOf(DocumentIterator::class, $result);
        $this->assertCount(0, $result);
    }
}
