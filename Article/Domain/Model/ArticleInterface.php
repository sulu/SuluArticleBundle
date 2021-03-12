<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\ArticleBundle\Article\Domain\Model;

use Sulu\Component\Persistence\Model\AuditableInterface;

/**
 * @experimental
 */
interface ArticleInterface extends AuditableInterface
{
    public const TEMPLATE_TYPE = 'article';
    public const RESOURCE_KEY = 'article';

    public function getId(): string;
}
