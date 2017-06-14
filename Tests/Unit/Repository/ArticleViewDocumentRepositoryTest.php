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

use ONGR\ElasticsearchBundle\Result\DocumentIterator;
use ONGR\ElasticsearchBundle\Service\Manager;
use ONGR\ElasticsearchBundle\Service\Repository;
use ONGR\ElasticsearchDSL\Search;
use Prophecy\Argument;
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

        $this->searchManager = $this->prophesize(Manager::class);
        $this->searchManager->getRepository(ArticleViewDocument::class)->willReturn($this->repository->reveal());

        $this->articleViewDocumentRepository = new ArticleViewDocumentRepository(
            $this->searchManager->reveal(),
            ArticleViewDocument::class,
            ['title','teaser_description']
        );
    }

    public function dataProvider()
    {
        return [
            [],
            ['123-123-123', ['blog','article']],
            ['321-321-321', ['blog'], 'en', 10],
        ];
    }

    /**
     * @dataProvider dataProvider
     *
     * @param string $uuid
     * @param array $types
     * @param string $locale
     * @param int $maxItems
     */
    public function testFindSimilar($uuid = '123-123-123', array $types = ['blog'], $locale = 'de', $maxItems = 12)
    {
        $termQuery = [];
        foreach ($types as $type) {
            $termQuery[] = [
                'term' => ['type' => $type]
            ];
        }

        $expectedSearch = [
            'bool' => [
                'filter' => [
                    [
                        'term' => ['locale' => $locale],
                    ]
                ],
                'must' => [
                    [
                        'bool' => [
                            'should' => $termQuery,
                        ],
                    ],
                    [
                        'more_like_this' => [
                            'like' => NULL,
                            'fields' => ['title', 'teaser_description'],
                            'min_term_freq' => 1,
                            'min_doc_freq' => 2,
                            'ids' => [$uuid . '-' . $locale],
                        ]
                    ]
                ]
            ]
        ];

        $documentIterator = $this->prophesize(DocumentIterator::class);

        $this->repository->findDocuments(Argument::that(function(Search $search) use ($expectedSearch, $maxItems) {
            $this->assertEquals($expectedSearch, $search->getQueries()->toArray());
            $this->assertEquals($maxItems, $search->getSize());

            return true;
        }))->willReturn($documentIterator->reveal());

        $this->assertSame(
            $documentIterator->reveal(),
            $this->articleViewDocumentRepository->findSimilar($uuid, $types, $locale, $maxItems)
        );
    }

    /**
     * @dataProvider dataProvider
     *
     * @param string $excludeUuid
     * @param array $types
     * @param string $locale
     * @param int $maxItems
     */
    public function testFindRecent(
        $excludeUuid = '123-123-123',
        array $types = ['blog'],
        $locale = 'de',
        $maxItems = 12
    ) {
        $termQuery = [];
        foreach ($types as $type) {
            $termQuery[] = [
                'term' => ['type' => $type]
            ];
        }

        $expectedSearch = [
            'bool' => [
                'filter' => [
                    [
                        'term' => ['locale' => $locale],
                    ]
                ],
                'must' => [
                    [
                        'bool' => [
                            'should' => $termQuery,
                        ],
                    ]
                ],
                'must_not' => [
                    [
                        'term' => [
                            'uuid' => $excludeUuid,
                        ],
                    ]
                ]
            ]
        ];

        $documentIterator = $this->prophesize(DocumentIterator::class);

        $this->repository->findDocuments(Argument::that(function(Search $search) use ($expectedSearch, $maxItems) {
            $this->assertEquals($expectedSearch, $search->getQueries()->toArray());
            $this->assertEquals($maxItems, $search->getSize());

            return true;
        }))->willReturn($documentIterator->reveal());

        $this->assertSame(
            $documentIterator->reveal(),
            $this->articleViewDocumentRepository->findRecent($excludeUuid, $types, $locale, $maxItems)
        );
    }
}
