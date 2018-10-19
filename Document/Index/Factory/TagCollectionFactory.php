<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\ArticleBundle\Document\Index\Factory;

use Doctrine\Common\Collections\Collection;
use Sulu\Bundle\ArticleBundle\Document\TagViewObject;
use Sulu\Bundle\TagBundle\Tag\TagManagerInterface;

/**
 * Create a collection with tag view objects.
 */
class TagCollectionFactory
{
    /**
     * @var TagManagerInterface
     */
    private $tagManager;

    /**
     * TagIndexerFactory constructor.
     *
     * @param TagManagerInterface $tagManager
     */
    public function __construct(TagManagerInterface $tagManager)
    {
        $this->tagManager = $tagManager;
    }

    /**
     * Create tag collection.
     *
     * @param string[] $tagNames
     *
     * @return Collection
     */
    public function create($tagNames)
    {
        $collection = new Collection();

        foreach ($tagNames as $tagName) {
            $tagEntity = $this->tagManager->findByName($tagName);

            if (!$tagEntity) {
                return;
            }

            $tag = new TagViewObject();
            $tag->name = $tagName;
            $tag->id = $tagEntity->getId();

            $collection[] = $tag;
        }

        return $collection;
    }
}
