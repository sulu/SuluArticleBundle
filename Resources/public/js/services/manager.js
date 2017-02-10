/*
 * This file is part of the Sulu CMS.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

define(['jquery', 'services/husky/util'], function($, Util) {
    'use strict';

    var templates = {
        url: _.template(
            '/admin/api/articles' +
            '<% if (typeof id !== "undefined") { %>/<%= id %><% } %>' +
            '?locale=<%= locale %>' +
            '<% if (typeof action !== "undefined") { %>&action=<%= action %><% } %>'
        )
    };

    return {
        url: templates.url,

        load: function(id, locale) {
            return Util.load(templates.url({id: id, locale: locale}));
        },

        save: function(data, id, locale, action) {
            return Util.save(templates.url({id: id, locale: locale, action: action}), !id ? 'POST' : 'PUT', data);
        },

        remove: function(id, locale) {
            return Util.save(templates.url({id: id, locale: locale}), 'DELETE');
        },

        unpublish: function(id, locale) {
            return Util.save(
                templates.url({id: id, locale: locale, action: 'unpublish'}),
                'POST'
            );
        },

        removeDraft: function(id, locale) {
            return Util.save(
                templates.url({id: id, locale: locale, action: 'remove-draft'}),
                'POST'
            );
        }
    };
});
