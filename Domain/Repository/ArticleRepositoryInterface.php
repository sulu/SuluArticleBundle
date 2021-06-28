<?php

namespace Sulu\Bundle\ArticleBundle\Domain\Repository;

use Sulu\Bundle\ArticleBundle\Domain\Exception\ArticleNotFoundException;
use Sulu\Bundle\ArticleBundle\Domain\Model\ArticleInterface;

/**
 * Implementation can be found in the following class:
 *
 * @see Sulu\Bundle\ArticleBundle\Infrastructure\Doctrine\Repository\ArticleRepository
 */
interface ArticleRepositoryInterface
{
    public function createNew(?string $uuid = null): ArticleInterface;

    /**
     * @param array{
     *     uuid?: string,
     *     uuids?: string[],
     *     locale?: string,
     *     stage?: string,
     * } $filters
     *
     * @param array{
     *     context?: string,
     * } $options
     *
     * @throws ArticleNotFoundException
     */
    public function getOneBy(array $filters, array $options = []): ArticleInterface;

    /**
     * @param array{
     *     uuid?: string,
     *     uuids?: string[],
     *     locale?: string,
     *     stage?: string,
     * } $filters
     */
    public function findOneBy(array $filters): ?ArticleInterface;

    /**
     * @param array{
     *     uuid?: string,
     *     uuids?: string[],
     *     locale?: string,
     *     stage?: string,
     *     categoryIds?: int[],
     *     categoryKeys?: int[],
     *     categoryOperator?: 'AND'|'OR',
     *     tagIds?: int[],
     *     tagNames?: string[],
     *     tagOperator?: 'AND'|'OR',
     *     templateKeys?: string[],
     *     page?: int,
     *     limit?: int,
     * } $filters
     *
     * @param array{
     *     id?: 'asc'|'desc',
     *     title?: 'asc'|'desc',
     * } $sortBy
     *
     * @return iterable<ArticleInterface>
     */
    public function findBy(array $filters = [], array $sortBy = []): iterable;

    /**
     * @param array{
     *     uuid?: string,
     *     uuids?: string[],
     *     locale?: string,
     *     stage?: string,
     *     categoryIds?: int[],
     *     categoryKeys?: int[],
     *     categoryOperator?: 'AND'|'OR',
     *     tagIds?: int[],
     *     tagNames?: string[],
     *     tagOperator?: 'AND'|'OR',
     *     templateKeys?: string[],
     * } $filters
     */
    public function countBy(array $filters = []): int;

    public function add(ArticleInterface $article): void;

    public function remove(ArticleInterface $article): void;
}
