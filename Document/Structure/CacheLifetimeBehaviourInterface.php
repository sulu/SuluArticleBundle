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

namespace Sulu\Bundle\ArticleBundle\Document\Structure;

use Sulu\Component\Content\Compat\CacheLifetimeBehaviourInterface as SuluCacheLifetimeBehaviourInterface;

if (\interface_exists(SuluCacheLifetimeBehaviourInterface::class)) {
    /**
     * @deprecated will be removed, as soon as the ArticleBundle rises the minimum requirement of sulu to a version,
     * where this interface exists
     *
     * @internal
     */
    interface CacheLifetimeBehaviourInterface extends SuluCacheLifetimeBehaviourInterface
    {
        /**
         * cacheLifeTime of template definition.
         *
         * @return array{
         *     type: string,
         *     value: string,
         * }
         */
        public function getCacheLifeTime();
    }
} else {
    /**
     * @deprecated will be removed, as soon as the ArticleBundle rises the minimum requirement of sulu to a version,
     * where this interface exists
     *
     * @internal
     */
    interface CacheLifetimeBehaviourInterface
    {
        /**
         * cacheLifeTime of template definition.
         *
         * @return array{
         *     type: string,
         *     value: string,
         * }
         */
        public function getCacheLifeTime();
    }
}
