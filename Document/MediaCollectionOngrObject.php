<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\ArticleBundle\Document;

use ONGR\ElasticsearchBundle\Annotation\Embedded;
use ONGR\ElasticsearchBundle\Annotation\Object;
use ONGR\ElasticsearchBundle\Annotation\Property;
use ONGR\ElasticsearchBundle\Collection\Collection;
use Sulu\Bundle\MediaBundle\Api\Media;

/**
 * Contains the ids and display-options.
 *
 * @Object
 */
class MediaCollectionOngrObject implements \Iterator, \ArrayAccess
{
    /**
     * @var int[]
     *
     * @Property(type="integer")
     */
    public $ids = [];

    /**
     * @var MediaOngrObject[]|Collection
     *
     * @Embedded(class="SuluArticleBundle:MediaOngrObject", multiple=true)
     */
    public $medias;

    /**
     * @var string
     *
     * @Property(type="string")
     */
    public $displayOption;

    /**
     * MediaCollectionOngrObject constructor.
     */
    public function __construct()
    {
        $this->medias = new Collection();
    }

    /**
     * Set data.
     *
     * @param Media[] $medias
     * @param string $displayOption
     */
    public function setData($medias = [], $displayOption = 'top')
    {
        $this->ids = [];
        $this->displayOption = $displayOption;
        $this->medias = new Collection();

        foreach ($medias as $media) {
            $this->ids[] = $media->getId();

            $mediaObject = new MediaOngrObject();
            $mediaObject->setData($media);

            $this->medias[] = $mediaObject;
        }
    }

    /**
     * @return int
     */
    public function getFirst()
    {
        return reset($this->ids) ?: null;
    }

    /**
     * {@inheritdoc}
     */
    public function current()
    {
        return $this->medias->current();
    }

    /**
     * {@inheritdoc}
     */
    public function next()
    {
        return $this->medias->next();
    }

    /**
     * {@inheritdoc}
     */
    public function key()
    {
        return $this->medias->key();
    }

    /**
     * {@inheritdoc}
     */
    public function valid()
    {
        return $this->medias->valid();
    }

    /**
     * {@inheritdoc}
     */
    public function rewind()
    {
        return $this->medias->rewind();
    }

    /**
     * {@inheritdoc}
     */
    public function offsetExists($offset)
    {
        return $this->medias->offsetExists($offset);
    }

    /**
     * {@inheritdoc}
     */
    public function offsetGet($offset)
    {
        return $this->medias->offsetGet($offset);
    }

    /**
     * {@inheritdoc}
     */
    public function offsetSet($offset, $value)
    {
        return $this->medias->offsetSet($offset, $value);
    }

    /**
     * {@inheritdoc}
     */
    public function offsetUnset($offset)
    {
        return $this->medias->offsetUnset($offset);
    }
}
