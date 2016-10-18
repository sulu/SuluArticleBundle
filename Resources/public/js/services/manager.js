/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

define(['jquery', 'underscore', 'services/husky/util'], function($, _, Util) {

    var templates = {
        url: _.template('/admin/api/articles<% if (typeof id !== "undefined") { %>/<%= id %><% } %>?locale=<%= locale %>')
    };

    return {
        url: templates.url,

        load: function(id, locale) {
            return Util.load(templates.url({id: id, locale: locale}));
        },

        save: function(data, id, locale) {
            return Util.save(templates.url({id: id, locale: locale}), !id ? 'POST' : 'PUT', data);
        },

        remove: function(id, locale) {
            return Util.save(templates.url({id: id, locale: locale}), 'DELETE');
        }
    };
});
