<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\ArticleBundle\Document\Behavior;

/**
 * The PHPCR nodes of objects implementing this behavior will be sharded on their creation date.
 */
interface DateShardingBehavior
{
}
