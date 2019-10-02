<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\ArticleBundle\Document\Serializer;

use JMS\Serializer\EventDispatcher\Events;
use JMS\Serializer\EventDispatcher\EventSubscriberInterface;
use JMS\Serializer\EventDispatcher\ObjectEvent;
use JMS\Serializer\Metadata\StaticPropertyMetadata;
use Sulu\Bundle\ArticleBundle\Document\ArticlePageDocument;

/**
 * Extends serialization for article-pages.
 */
class ArticlePageSubscriber implements EventSubscriberInterface
{
    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents()
    {
        return [
            [
                'event' => Events::POST_SERIALIZE,
                'format' => 'json',
                'method' => 'addTitleOnPostSerialize',
            ],
        ];
    }

    /**
     * Append title to result.
     *
     * @param ObjectEvent $event
     */
    public function addTitleOnPostSerialize(ObjectEvent $event)
    {
        $articlePage = $event->getObject();
        $visitor = $event->getVisitor();
        $context = $event->getContext();

        if (!$articlePage instanceof ArticlePageDocument) {
            return;
        }

        $title = $articlePage->getParent()->getTitle();
        $visitor->visitProperty(new StaticPropertyMetadata('', 'title', $title), $title);
    }
}
