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
 * This behavior has to be attached to documents which should have a sulu-route and handle their pages.
 */
interface RoutableBehavior extends RoutablePageBehavior
{
}
