<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\ArticleBundle\EventListener;

use JMS\Serializer\EventDispatcher\Events;
use JMS\Serializer\EventDispatcher\EventSubscriberInterface;
use JMS\Serializer\EventDispatcher\ObjectEvent;
use Sulu\Bundle\ArticleBundle\Document\ArticleOngrDocument;

/**
 * Append type translation to serialized article-ongr document.
 */
class ArticleOngrDocumentSerializeSubscriber implements EventSubscriberInterface
{
    /**
     * @var array
     */
    private $typeConfiguration;

    /**
     * @param array $typeConfiguration
     */
    public function __construct(array $typeConfiguration)
    {
        $this->typeConfiguration = $typeConfiguration;
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents()
    {
        return [
            [
                'event' => Events::POST_SERIALIZE,
                'format' => 'json',
                'method' => 'onPostSerialize',
            ],
        ];
    }

    /**
     * Add translated type.
     *
     * @param ObjectEvent $event
     */
    public function onPostSerialize(ObjectEvent $event)
    {
        $article = $event->getObject();
        $visitor = $event->getVisitor();
        $context = $event->getContext();

        if (!($article instanceof ArticleOngrDocument)) {
            return;
        }

        $visitor->addData('typeTranslation', $context->accept($this->getTitle($article->getType())));
    }

    /**
     * Returns title for given type.
     *
     * @param string $type
     *
     * @return string
     */
    private function getTitle($type)
    {
        if (!array_key_exists($type, $this->typeConfiguration)) {
            return ucfirst($type);
        }

        return $this->typeConfiguration[$type]['translation_key'];
    }
}
