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

use Sulu\Bundle\ArticleBundle\Document\ExcerptViewObject;

/**
 * Create a media excerpt view object.
 */
class ExcerptFactory
{
    /**
     * @var CategoryCollectionFactory
     */
    private $categoryCollectionFactory;

    /**
     * @var TagCollectionFactory
     */
    private $tagCollectionFactory;

    /**
     * @var MediaCollectionFactory
     */
    private $mediaCollectionFactory;

    /**
     * ExcerptIndexerFactory constructor.
     *
     * @param CategoryCollectionFactory $categoryCollectionFactory
     * @param TagCollectionFactory $tagCollectionFactory
     * @param MediaCollectionFactory $mediaCollectionFactory
     */
    public function __construct(
        CategoryCollectionFactory $categoryCollectionFactory,
        TagCollectionFactory $tagCollectionFactory,
        MediaCollectionFactory $mediaCollectionFactory
    ) {
        $this->categoryCollectionFactory = $categoryCollectionFactory;
        $this->tagCollectionFactory = $tagCollectionFactory;
        $this->mediaCollectionFactory = $mediaCollectionFactory;
    }

    /**
     * Create a excerpt object by given data.
     *
     * @param array $data
     * @param string $locale
     *
     * @return ExcerptViewObject
     */
    public function create($data, $locale)
    {
        $excerpt = new ExcerptViewObject();

        if (empty($data)) {
            return $excerpt;
        }

        $excerpt->title = $data['title'];
        $excerpt->more = $data['more'];
        $excerpt->description = $data['description'];
        $excerpt->tags = $this->tagCollectionFactory->create($data['tags']);
        $excerpt->categories = $this->categoryCollectionFactory->create($data['categories'], $locale);
        $excerpt->icon = $this->mediaCollectionFactory->create($data['icon'], $locale);
        $excerpt->images = $this->mediaCollectionFactory->create($data['images'], $locale);

        return $excerpt;
    }
}
