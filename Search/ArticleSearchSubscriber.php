<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\ArticleBundle\Search;

use Massive\Bundle\SearchBundle\Search\Event\PreIndexEvent;
use Massive\Bundle\SearchBundle\Search\Field;
use Massive\Bundle\SearchBundle\Search\SearchEvents;
use Sulu\Bundle\ArticleBundle\Document\Behavior\WebspaceBehavior;
use Sulu\Bundle\ArticleBundle\Document\Resolver\WebspaceResolver;
use Sulu\Bundle\SearchBundle\Search\Factory;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class ArticleSearchSubscriber implements EventSubscriberInterface
{
    /**
     * @var Factory
     */
    private $factory;

    /**
     * @var WebspaceResolver
     */
    private $webspaceResolver;

    /**
     * @param Factory $factory
     */
    public function __construct(Factory $factory, WebspaceResolver $webspaceResolver)
    {
        $this->factory = $factory;
        $this->webspaceResolver = $webspaceResolver;
    }

    /**
     * Returns the events this subscriber has subscribed.
     */
    public static function getSubscribedEvents()
    {
        return [
            SearchEvents::PRE_INDEX => 'handlePreIndex',
        ];
    }

    /**
     * @param PreIndexEvent $event
     */
    public function handlePreIndex(PreIndexEvent $event)
    {
        $subject = $event->getSubject();
        $document = $event->getDocument();

        if (!$subject instanceof WebspaceBehavior) {
            return;
        }

        $document->addField(
            $this->factory->createField(
                'mainWebspace',
                $this->webspaceResolver->resolveMainWebspace($subject),
                Field::TYPE_STRING
            )
        );

        $document->addField(
            $this->factory->createField(
                'additionalWebspaces',
                $this->webspaceResolver->resolveAdditionalWebspaces($subject),
                Field::TYPE_ARRAY
            )
        );
    }
}
