<?php

declare(strict_types=1);

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\ArticleBundle\Document\Subscriber;

use Sulu\Bundle\ArticleBundle\Document\Behavior\DateShardingBehavior;
use Sulu\Component\DocumentManager\Event\PersistEvent;
use Sulu\Component\DocumentManager\Events;
use Sulu\Component\DocumentManager\NodeManager;
use Sulu\Component\DocumentManager\PathBuilder;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Executes the date-sharding for documents which implements the DateShardingBehavior.
 */
class DateShardingSubscriber implements EventSubscriberInterface
{
    /**
     * @var PathBuilder
     */
    private $pathBuilder;

    /**
     * @var NodeManager
     */
    private $nodeManager;

    public function __construct(PathBuilder $pathBuilder, NodeManager $nodeManager)
    {
        $this->pathBuilder = $pathBuilder;
        $this->nodeManager = $nodeManager;
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents()
    {
        return [
            Events::PERSIST => [
                ['handleSetParentNode', 481],
            ],
        ];
    }

    /**
     * Set parent-node if no parent exists.
     *
     * The format of parent node is: /%base%/%articles%/
     */
    public function handleSetParentNode(PersistEvent $event): void
    {
        if (!$event->getDocument() instanceof DateShardingBehavior || $event->hasParentNode()) {
            return;
        }

        $date = $event->getDocument()->getCreated();
        if (null === $date) {
            $date = new \DateTime();
        }

        $path = $this->pathBuilder->build(['%base%', '%articles%', $date->format('Y'), $date->format('m')]);

        $event->setParentNode($this->nodeManager->createPath($path));
    }
}
