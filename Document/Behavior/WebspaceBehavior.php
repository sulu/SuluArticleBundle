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

use Sulu\Component\DocumentManager\Behavior\Mapping\LocaleBehavior;

/**
 * This behavior adds the webspace functionality to articles.
 */
interface WebspaceBehavior extends LocaleBehavior
{
    public function getMainWebspace(): ?string;

    public function setMainWebspace(?string $webspace): self;

    /**
     * @return string[]|null
     */
    public function getAdditionalWebspaces(): ?array;

    /**
     * @param string[]|null $additionalWebspaces
     */
    public function setAdditionalWebspaces(?array $additionalWebspaces): self;
}
