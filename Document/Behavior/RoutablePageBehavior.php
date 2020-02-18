<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\ArticleBundle\Document\Behavior;

use Sulu\Component\Route\Document\Behavior\RoutableBehavior as BaseRoutableBehavior;

/**
 * This behavior has to be attached to documents which should have a sulu-route but managed by their parent.
 */
interface RoutablePageBehavior extends BaseRoutableBehavior
{
}
