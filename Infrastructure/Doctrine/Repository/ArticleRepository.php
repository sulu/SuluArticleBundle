<?php

namespace Sulu\Bundle\ArticleBundle\Infrastructure\Doctrine\Repository;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\NoResultException;
use Doctrine\ORM\QueryBuilder;
use Sulu\Bundle\ArticleBundle\Domain\Exception\ArticleNotFoundException;
use Sulu\Bundle\ArticleBundle\Domain\Model\ArticleDimensionContentInterface;
use Sulu\Bundle\ArticleBundle\Domain\Model\ArticleInterface;
use Sulu\Bundle\ArticleBundle\Domain\Repository\ArticleRepositoryInterface;
use Sulu\Bundle\ContentBundle\Content\Infrastructure\Doctrine\DimensionContentQueryEnhancer;
use Webmozart\Assert\Assert;

class ArticleRepository implements ArticleRepositoryInterface
{
    protected static $contexts = [
        'article_admin' => [
            'with-content' => true,
        ],
    ];

    /**
     * @var EntityManagerInterface
     */
    private $entityManager;

    /**
     * @var EntityRepository<ArticleInterface>
     */
    protected $entityRepository;

    /**
     * @var EntityRepository<ArticleDimensionContentInterface>
     */
    protected $entityDimensionContentRepository;

    /**
     * @var DimensionContentQueryEnhancer
     */
    protected $dimensionContentQueryEnhancer;

    /**
     * @var class-string<ArticleInterface> $articleClassName
     */
    protected $articleClassName;

    /**
     * @var class-string<ArticleDimensionContentInterface> $articleDimensionContentClassName
     */
    protected $articleDimensionContentClassName;

    public function __construct(
        EntityManagerInterface $entityManager,
        DimensionContentQueryEnhancer $dimensionContentQueryEnhancer
    ) {
        $this->entityRepository = $entityManager->getRepository(ArticleInterface::class);
        $this->entityDimensionContentRepository = $entityManager->getRepository(ArticleDimensionContentInterface::class);
        $this->entityManager = $entityManager;
        $this->dimensionContentQueryEnhancer = $dimensionContentQueryEnhancer;
        $this->articleClassName = $this->entityRepository->getClassName();
        $this->articleDimensionContentClassName = $this->entityDimensionContentRepository->getClassName();
    }

    public function createNew(?string $uuid = null): ArticleInterface
    {
        $className = $this->articleClassName;

        return new $className($uuid);
    }

    public function getOneBy(array $filters, array $options = []): ArticleInterface
    {
        $queryBuilder = $this->createQueryBuilder($filters, [], $options);

        try {
            /** @var ArticleInterface $article */
            $article = $queryBuilder->getQuery()->getSingleResult();
        } catch (NoResultException $e) {
            throw new ArticleNotFoundException($filters);
        }

        return $article;
    }

    public function findOneBy(array $filters, array $options = []): ?ArticleInterface
    {
        $queryBuilder = $this->createQueryBuilder($filters, [], $options);

        try {
            /** @var ArticleInterface $article */
            $article = $queryBuilder->getQuery()->getSingleResult();
        } catch (NoResultException $e) {
            return null;
        }

        return $article;
    }

    public function countBy(array $filters = []): int
    {
        // The countBy method will ignore any page and limit parameters
        // for better developer experience we will strip them away here
        // instead of that the developer need to take that into account
        // in there call of the countBy method.
        unset($filters['page']);
        unset($filters['limit']);

        $queryBuilder = $this->createQueryBuilder($filters);

        $queryBuilder->select('COUNT(DISTINCT article.uuid)');

        return (int) $queryBuilder->getQuery()->getSingleScalarResult();
    }

    public function findBy(array $filters = [], array $orderBy = []): iterable
    {
        $queryBuilder = $this->createQueryBuilder($filters, $orderBy);

        /** @var iterable<ArticleInterface> $articles */
        $articles = $queryBuilder->getQuery()->getResult();

        foreach ($articles as $article) {
            yield $article;
        }
    }

    public function add(ArticleInterface $article): void
    {
        $this->entityManager->persist($article);
    }

    public function remove(ArticleInterface $article): void
    {
        $this->entityManager->remove($article);
    }

    /**
     * @param array{
     *     uuid?: string,
     *     uuids?: string[],
     *     locale?: string,
     *     stage?: string,
     *     categoryIds?: int[],
     *     categoryKeys?: int[],
     *     tagIds?: int[],
     *     tagNames?: string[],
     *     templateKeys?: string[],
     *     page?: int,
     *     limit?: int,
     * } $filters
     *
     * @param array{
     *     uuid?: 'asc'|'desc', // TODO
     *     title?: 'asc'|'desc', // TODO
     * } $sortBy
     *
     * @param array{
     *     context?: string,
     * } $options
     */
    private function createQueryBuilder(array $filters, array $orderBy = [], array $options = []): QueryBuilder
    {
        $queryBuilder = $this->entityRepository->createQueryBuilder('article');

        $uuid = $filters['uuid'] ?? null;
        if ($uuid !== null) {
            Assert::string($uuid);
            $queryBuilder->andWhere('article.uuid = :uuid')
                ->setParameter('uuid', $uuid);
        }

        $uuids = $filters['uuids'] ?? null;
        if ($uuids !== null) {
            Assert::isArray($uuids);
            $queryBuilder->andWhere('article.uuid IN(:uuids)')
                ->setParameter('uuids', $uuids);
        }

        $limit = $filters['limit'] ?? null;
        if ($limit !== null) {
            Assert::integer($limit);
            $queryBuilder->setMaxResults($limit);
        }

        $page = $filters['page'] ?? null;
        if ($page !== null) {
            Assert::notNull($limit);
            Assert::integer($page);
            $offset = (int) ($limit * ($page - 1));
            $queryBuilder->setFirstResult($offset);
        }

        $contentFilters = \array_filter([
            'locale' => $filters['locale'] ?? null,
            'stage' => $filters['stage'] ?? null,
            'categoryIds' => $filters['categoryIds'] ?? null,
            'categoryKeys' => $filters['categoryKeys'] ?? null,
            'categoryOperator' => $filters['categoryOperator'] ?? null,
            'tagNames' => $filters['tagNames'] ?? null,
            'tagIds' => $filters['tagIds'] ?? null,
            'tagOperator' => $filters['tagOperator'] ?? null,
            'templateKeys' => $filters['templateKeys'] ?? null,
        ]);

        if (!empty($contentFilters)) {
            Assert::keyExists($contentFilters, 'locale');
            Assert::keyExists($contentFilters, 'stage');

            $this->dimensionContentQueryEnhancer->enhanceDimensionContentFilter(
                $queryBuilder,
                $this->articleDimensionContentClassName,
                $contentFilters
            );
        }

        /*
        $withs = static::$contexts[$options['context']] ?? [];

        $dimensionAttributes = $filters['dimensionAttributes'] ?? null;

        if ($dimensionAttributes !== null) {
            $queryBuilder->leftJoin('article.dimensionContents', 'dimensionContent');
            $dimensionContentClassName = $this->entityDimensionContentRepository->getClassName();
            $this->dimensionContentQueryEnhancer->selectDimensionContent(
                $queryBuilder,
                $dimensionContentClassName,
                $dimensionAttributes
            );
        }
        */

        return $queryBuilder;
    }
}
