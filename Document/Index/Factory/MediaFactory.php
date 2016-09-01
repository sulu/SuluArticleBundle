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

use Sulu\Bundle\ArticleBundle\Document\MediaViewObject;
use Sulu\Bundle\MediaBundle\Media\Manager\MediaManagerInterface;

/**
 * Create a seo view object.
 */
class MediaFactory
{
    /**
     * @var MediaManagerInterface
     */
    private $mediaManager;

    /**
     * MediaCollectionFactory constructor.
     *
     * @param MediaManagerInterface $mediaManager
     */
    public function __construct(MediaManagerInterface $mediaManager)
    {
        $this->mediaManager = $mediaManager;
    }

    /**
     * @param int $id
     * @param string $locale
     *
     * @return MediaViewObject
     */
    public function create($id, $locale)
    {
        $mediaViewObject = new MediaViewObject();

        if (!$id) {
            return $mediaViewObject;
        }

        $media = $this->mediaManager->getById($id, $locale);

        $mediaViewObject->setData($media);

        return $mediaViewObject;
    }
}
