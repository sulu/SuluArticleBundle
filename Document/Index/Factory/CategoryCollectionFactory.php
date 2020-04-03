<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\ArticleBundle\Document\Index\Factory;

use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Query\Expr\Join;
use ONGR\ElasticsearchBundle\Collection\Collection;
use Sulu\Bundle\ArticleBundle\Document\CategoryViewObject;

/**
 * Create a category collection view object.
 */
class CategoryCollectionFactory
{
    /**
     * @var EntityRepository
     */
    private $categoryRepository;

    /**
     * CategoryCollectionFactory constructor.
     */
    public function __construct(EntityRepository $categoryRepository)
    {
        $this->categoryRepository = $categoryRepository;
    }

    /**
     * Create category collection.
     *
     * @param int[] $categoryIds
     * @param string $locale
     *
     * @return Collection
     */
    public function create($categoryIds, $locale)
    {
        if (empty($categoryIds)) {
            return new Collection();
        }

        // Load category with keywords
        $queryBuilder = $this->categoryRepository->createQueryBuilder('category')
            ->select(['category.id', 'category.key', 'translate.translation as name', 'keyword.keyword'])
            ->leftJoin('category.translations', 'translate', Join::WITH, 'translate.locale = :locale')
            ->setParameter('locale', $locale)
            ->leftJoin('translate.keywords', 'keyword');

        $queryBuilder->where($queryBuilder->expr()->in('category.id', $categoryIds));

        $categories = [];

        foreach ($queryBuilder->getQuery()->getResult() as $categoryData) {
            $id = (int) $categoryData['id'];

            if (!isset($categories[$id])) {
                $categories[$id] = new CategoryViewObject();
                $categories[$id]->id = $id;
                $categories[$id]->key = $categoryData['key'];
                $categories[$id]->name = $categoryData['name'];
            }

            $categories[$id]->keywords[] = $categoryData['keyword'];
        }

        return new Collection(array_values($categories));
    }
}
