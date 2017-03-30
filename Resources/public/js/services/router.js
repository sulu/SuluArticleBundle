/*
 * This file is part of the Sulu CMS.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

define([
    'config',
    'suluarticle/services/base-router',
    'suluarticle/services/typed-router'
], function(Config, BaseRouter, TypedRouter) {

    'use strict';

    var typeNames = Object.keys(Config.get('sulu_article').types);
    if (typeNames.length > 1) {
        return TypedRouter;
    }

    return BaseRouter;
});
