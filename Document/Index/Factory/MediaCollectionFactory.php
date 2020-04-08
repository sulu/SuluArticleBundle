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

use ONGR\ElasticsearchBundle\Collection\Collection;
use Sulu\Bundle\ArticleBundle\Document\MediaViewObject;
use Sulu\Bundle\MediaBundle\Media\Manager\MediaManagerInterface;

/**
 * Create a media collection view object.
 */
class MediaCollectionFactory
{
    /**
     * @var MediaManagerInterface
     */
    private $mediaManager;

    /**
     * MediaCollectionFactory constructor.
     */
    public function __construct(MediaManagerInterface $mediaManager)
    {
        $this->mediaManager = $mediaManager;
    }

    /**
     * Create media collection object.
     *
     * @return MediaViewObject[]|Collection
     */
    public function create(array $data, string $locale)
    {
        $mediaCollection = new Collection();

        if (empty($data)) {
            return $mediaCollection;
        }

        if (array_key_exists('ids', $data)) {
            $medias = $this->mediaManager->getByIds($data['ids'], $locale);

            foreach ($medias as $media) {
                $mediaViewObject = new MediaViewObject();
                $mediaViewObject->setData($media);

                $mediaCollection[] = $mediaViewObject;
            }
        }

        return $mediaCollection;
    }
}
