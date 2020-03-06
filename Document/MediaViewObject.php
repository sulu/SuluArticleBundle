<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\ArticleBundle\Document;

use ONGR\ElasticsearchBundle\Annotation\ObjectType;
use ONGR\ElasticsearchBundle\Annotation\Property;
use Sulu\Bundle\MediaBundle\Api\Media;

/**
 * Contains the ids and display-options.
 *
 * @ObjectType
 */
class MediaViewObject
{
    /**
     * @var int
     *
     * @Property(type="integer")
     */
    public $id;

    /**
     * @var string
     *
     * @Property(type="keyword")
     */
    public $title;

    /**
     * @var string
     *
     * @Property(type="keyword")
     */
    public $copyright;

    /**
     * @var string
     *
     * @Property(type="keyword")
     */
    protected $formats = '{}';

    /**
     * @var string
     *
     * @Property(type="keyword")
     */
    public $url;

    /**
     * Set data.
     *
     * @return $this
     */
    public function setData(Media $media)
    {
        $this->id = $media->getId();
        $this->title = $media->getTitle();
        $this->setFormats($media->getFormats());
        $this->url = $media->getUrl();
        $this->copyright = $media->getCopyright();

        return $this;
    }

    /**
     * Get formats.
     *
     * @return string
     */
    public function getFormats()
    {
        return $this->formats;
    }

    /**
     * Set formats.
     *
     * @param string[] $formats
     *
     * @return $this
     */
    public function setFormats($formats)
    {
        if (is_array($formats)) {
            $formats = json_encode($formats);
        }

        $this->formats = $formats;

        return $this;
    }

    /**
     * Get thumbnails.
     *
     * @return array
     */
    public function getThumbnails()
    {
        return json_decode($this->formats, true);
    }
}
