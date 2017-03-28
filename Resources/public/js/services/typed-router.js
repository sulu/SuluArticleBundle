/*
 * This file is part of the Sulu CMS.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

define(['services/husky/mediator', 'suluarticle/services/base-router'], function(Mediator, BaseRouter) {

    'use strict';

    var routes = {
            list: _.template('articles:<%= type %>/<%= locale %>'),
            add: _.template('articles/<%= locale %>/add:<%= type %>')
        },

        goto = function(route) {
            Mediator.emit('sulu.router.navigate', route);
        };

    return {
        toList: function(locale, type) {
            goto(routes.list({locale: locale, type: type}));
        },
        toAdd: function(locale, type) {
            goto(routes.add({locale: locale, type: type}));
        },
        toEdit: function(id, locale, tab) {
            BaseRouter.toEdit(id, locale, tab);
        },
        toEditForce: function(id, locale, tab) {
            BaseRouter.toEditForce(id, locale, tab);
        },
        toEditUpdate: function(id, locale, tab) {
            BaseRouter.toEditUpdate(id, locale, tab);
        },
        toPageEdit: function(id, page, locale) {
            BaseRouter.toPageEdit(id, page, locale);
        },
        toPageAdd: function(id, locale) {
            BaseRouter.toPageEdit(id, locale);
        }
    };
});
