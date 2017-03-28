/*
 * This file is part of the Sulu CMS.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

define(['underscore', 'services/husky/mediator'], function(_, Mediator) {

    'use strict';

    var routes = {
            list: _.template('articles/<%= locale %>'),
            add: _.template('articles/<%= locale %>/add'),
            edit: _.template('articles/<%= locale %>/edit:<%= id %>/<%= tab %>')
        },

        goto = function(route, trigger, force) {
            if (typeof trigger === 'undefined') {
                trigger = true;
            }

            Mediator.emit('sulu.router.navigate', route, trigger, (force || false));
        };

    return {
        toList: function(locale) {
            goto(routes.list({locale: locale}));
        },
        toAdd: function(locale) {
            goto(routes.add({locale: locale}));
        },
        toEdit: function(id, locale, tab) {
            goto(routes.edit({id: id, locale: locale, tab: (tab || 'details')}));
        },
        toEditForce: function(id, locale, tab) {
            goto(routes.edit({id: id, locale: locale, tab: (tab || 'details')}), true, true);
        },
        toEditUpdate: function(id, locale, tab) {
            goto(routes.edit({id: id, locale: locale, tab: (tab || 'details')}), true, true);
        }
    };
});
