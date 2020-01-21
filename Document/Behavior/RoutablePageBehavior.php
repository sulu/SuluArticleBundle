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

namespace Sulu\Bundle\ArticleBundle\Document\Behavior;

use Sulu\Bundle\RouteBundle\Model\RoutableInterface;
use Sulu\Component\Content\Document\Behavior\StructureBehavior;
use Sulu\Component\DocumentManager\Behavior\Mapping\LocaleBehavior;
use Sulu\Component\DocumentManager\Behavior\Mapping\UuidBehavior;

/**
 * This behavior has to be attached to documents which should have a sulu-route but managed by their parent.
 */
interface RoutablePageBehavior extends RoutableInterface, UuidBehavior, LocaleBehavior, StructureBehavior
{
    /**
     * Returns route-path.
     */
    public function getRoutePath(): ?string;

    /**
     * Remove route.
     */
    public function removeRoute(): self;

    /**
     * Set route-path.
     */
    public function setRoutePath(?string $routePath): self;

    /**
     * Set uuid.
     */
    public function setUuid(string $uuid): self;

    /**
     * Returns class of document without proxies.
     */
    public function getClass(): string;
}
