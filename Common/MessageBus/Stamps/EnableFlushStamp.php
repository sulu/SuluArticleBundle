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

namespace Sulu\Bundle\ArticleBundle\Common\MessageBus\Stamps;

use Symfony\Component\Messenger\Stamp\StampInterface;

/**
 * Marker stamp to enable DoctrineFlushMiddleware for envelopes with this stamp.
 *
 * @experimental
 */
class EnableFlushStamp implements StampInterface
{
}
