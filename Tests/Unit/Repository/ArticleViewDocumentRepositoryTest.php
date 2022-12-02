<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\ArticleBundle\Tests\Unit\Repository;

use ONGR\ElasticsearchBundle\Result\DocumentIterator;
use ONGR\ElasticsearchBundle\Service\Manager;
use ONGR\ElasticsearchBundle\Service\Repository;
use ONGR\ElasticsearchDSL\Search;
use ONGR\ElasticsearchDSL\Sort\FieldSort;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Sulu\Bundle\ArticleBundle\Document\ArticleViewDocument;
use Sulu\Bundle\ArticleBundle\Document\Repository\ArticleViewDocumentRepository;

class ArticleViewDocumentRepositoryTest extends TestCase
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
     * @var Search
     */
    private $search;

    /**
     * @var ArticleViewDocumentRepository
     */
    private $articleViewDocumentRepository;

    public function setUp(): void
    {
        $this->search = $this->prophesize(Search::class);

        $this->repository = $this->prophesize(Repository::class);
        $this->repository->createSearch()->willReturn(new Search());

        $this->searchManager = $this->prophesize(Manager::class);
        $this->searchManager->getRepository(ArticleViewDocument::class)->willReturn($this->repository->reveal());

        $this->articleViewDocumentRepository = new ArticleViewDocumentRepository(
            $this->searchManager->reveal(),
            ArticleViewDocument::class,
            ['title', 'teaser_description']
        );
    }

    public function dataProvider()
    {
        return [
            [],
            ['123-123-123', ['blog', 'article']],
            ['321-321-321', ['blog'], 'en', 10],
            ['444-444-444', ['blog'], 'en', 10, 'test_webspace'],
        ];
    }

    /**
     * @dataProvider dataProvider
     *
     * @param string $uuid
     * @param string $locale
     * @param int $limit
     * @param string $webspaceKey
     */
    public function testFindSimilar(
        $uuid = '123-123-123',
        array $types = ['blog'],
        $locale = 'de',
        $limit = 12,
        $webspaceKey = null
    ) {
        $termQuery = [];
        foreach ($types as $type) {
            $termQuery[] = [
                'term' => ['type' => $type],
            ];
        }

        $mustQuery = [];
        $mustQuery[] = [
            'bool' => [
                'should' => $termQuery,
            ],
        ];

        if ($webspaceKey) {
            $mustQuery[] = [
                'bool' => [
                    'should' => [
                        [
                            'term' => ['main_webspace' => $webspaceKey],
                        ],
                        [
                            'term' => ['additional_webspaces' => $webspaceKey],
                        ],
                    ],
                ],
            ];
        }

        $mustQuery[] = [
            'more_like_this' => [
                'like' => [
                    [
                        '_id' => $uuid . '-' . $locale,
                    ],
                ],
                'fields' => ['title', 'teaser_description'],
                'min_term_freq' => 1,
                'min_doc_freq' => 2,
            ],
        ];

        $expectedSearch = [
            'bool' => [
                'filter' => [
                    [
                        'term' => ['locale' => $locale],
                    ],
                ],
                'must' => $mustQuery,
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
            $this->articleViewDocumentRepository->findSimilar($uuid, $limit, $types, $locale, $webspaceKey)
        );
    }

    /**
     * @dataProvider dataProvider
     *
     * @param string $excludeUuid
     * @param string $locale
     * @param int $limit
     * @param string $webspaceKey
     */
    public function testFindRecent(
        $excludeUuid = '123-123-123',
        array $types = ['blog'],
        $locale = 'de',
        $limit = 12,
        $webspaceKey = null
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

        if ($webspaceKey) {
            $expectedSearch['bool']['must'][] = [
                'bool' => [
                    'should' => [
                        [
                            'term' => ['main_webspace' => $webspaceKey],
                        ],
                        [
                            'term' => ['additional_webspaces' => $webspaceKey],
                        ],
                    ],
                ],
            ];
        }

        $documentIterator = $this->prophesize(DocumentIterator::class);

        $this->repository->findDocuments(Argument::that(function(Search $search) use ($expectedSearch, $limit) {
            $this->assertEquals($expectedSearch, $search->getQueries()->toArray());
            $this->assertEquals($limit, $search->getSize());
            $this->assertCount(1, $search->getSorts());
            $this->assertEquals(new FieldSort('authored', FieldSort::DESC), \current($search->getSorts()));

            return true;
        }))->willReturn($documentIterator->reveal());

        $this->assertSame(
            $documentIterator->reveal(),
            $this->articleViewDocumentRepository->findRecent($excludeUuid, $limit, $types, $locale, $webspaceKey)
        );
    }
}
