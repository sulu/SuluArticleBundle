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
        ),
        urlVersion: _.template(
            '/admin/api/articles' +
            '/<%= id %>' +
            '/versions' +
            '<% if (typeof version !== "undefined") { %>/<%= version %><% } %>' +
            '?locale=<%= locale %>' +
            '<% if (typeof action !== "undefined") { %>&action=<%= action %><% } %>'
        )
    };

    return {
        url: templates.url,

        /**
         * Load article.
         *
         * @param {String} id
         * @param {String} locale
         */
        load: function(id, locale) {
            return Util.load(templates.url({id: id, locale: locale}));
        },

        /**
         * Save article.
         *
         * @param {Array} data
         * @param {String} id
         * @param {String} locale
         * @param {String} action
         */
        save: function(data, id, locale, action) {
            return Util.save(templates.url({id: id, locale: locale, action: action}), !id ? 'POST' : 'PUT', data);
        },

        /**
         * Remove article.
         *
         * @param {String} id
         * @param {String} locale
         */
        remove: function(id, locale) {
            return Util.save(templates.url({id: id, locale: locale}), 'DELETE');
        },

        /**
         * Unpublish article.
         *
         * @param {String} id
         * @param {String} locale
         */
        unpublish: function(id, locale) {
            return Util.save(
                templates.url({id: id, locale: locale, action: 'unpublish'}),
                'POST'
            );
        },

        /**
         * Remove article.
         *
         * @param {String} id
         * @param {String} locale
         */
        removeDraft: function(id, locale) {
            return Util.save(
                templates.url({id: id, locale: locale, action: 'remove-draft'}),
                'POST'
            );
        },

        /**
         * Restore article to given version.
         *
         * @param {String} id
         * @param {String} version
         * @param {String} locale
         */
        restoreVersion: function(id, version, locale) {
            return Util.save(
                templates.urlVersion({id: id, locale: locale, version: version, action: 'restore'}),
                'POST'
            );
        },

        /**
         * Returns copy article from a given locale to a array of other locales url.
         *
         * @param {String} id
         * @param {String} src
         * @param {String[]} dest
         *
         * @returns {String}
         */
        getCopyLocaleUrl: function(id, src, dest) {
            return [
                templates.url({id: id, locale: src, action: 'copy-locale'}), '&dest=', dest
            ].join('');
        },

        /**
         * Returns get versions url for given id and locale.
         *
         * @param {String} id
         * @param {String} locale
         *
         * @return {String}
         */
        getVersionsUrl: function(id, locale) {
            return templates.urlVersion({id: id, locale: locale});
        }
    };
});
