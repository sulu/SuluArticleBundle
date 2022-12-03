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

namespace Sulu\Bundle\ArticleBundle\Common\MessageBus\Middleware;

use Doctrine\ORM\EntityManagerInterface;
use Sulu\Bundle\ArticleBundle\Common\MessageBus\Stamps\EnableFlushStamp;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\Middleware\MiddlewareInterface;
use Symfony\Component\Messenger\Middleware\StackInterface;

/**
 * @experimental
 *
 * @internal This class should not be instantiated by a project.
 *           Create instead your own middleware when you want to
 *           extend it.
 */
final class DoctrineFlushMiddleware implements MiddlewareInterface
{
    /**
     * @var EntityManagerInterface
     */
    private $entityManager;

    /**
     * @var int
     */
    private $messageDepth = 0;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    public function handle(Envelope $envelope, StackInterface $stack): Envelope
    {
        ++$this->messageDepth;

        try {
            $envelope = $stack->next()->handle($envelope, $stack);
        } finally {
            // need to decrease message depth in every case to start handling of next message at depth 0
            --$this->messageDepth;
        }

        // flush unit-of-work to the database after the root message was handled successfully
        if (0 === $this->messageDepth && !empty($envelope->all(EnableFlushStamp::class))) {
            $this->entityManager->flush();
        }

        return $envelope;
    }
}
