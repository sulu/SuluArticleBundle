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
            '<% if (typeof postfix !== "undefined") { %>/<%= postfix %><% } %>' +
            '<% if (typeof version !== "undefined") { %>/<%= version %><% } %>' +
            '?locale=<%= locale %>' +
            '<% if (typeof action !== "undefined") { %>&action=<%= action %><% } %>' +
            '<% if (typeof ids !== "undefined") { %>&ids=<%= ids.join(",") %><% } %>'
        ),
        pageUrl: _.template(
            '/admin/api/articles/<%= articleId %>/pages' +
            '<% if (typeof pageId !== "undefined" && !!pageId) { %>/<%= pageId %><% } %>' +
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
         * Load article synchronous.
         *
         * @param {String} id
         * @param {String} locale
         */
        loadSync: function(id, locale) {
            return Util.ajax({
                url: templates.url({id: id, locale: locale}),
                dataType: 'json',
                async: false
            });
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
         * Save article-page.
         *
         * @param {Array} data
         * @param {String} articleId
         * @param {String} pageId
         * @param {String} locale
         * @param {String} action
         */
        savePage: function(data, articleId, pageId, locale, action) {
            return Util.save(
                templates.pageUrl({articleId: articleId, pageId: pageId, locale: locale, action: action}),
                pageId ? 'PUT' : 'POST',
                data
            );
        },

        /**
         * Remove article.
         *
         * @param {String|Array} id
         * @param {String} locale
         */
        remove: function(id, locale) {
            if (typeof id === 'string') {
                return Util.save(templates.url({id: id, locale: locale}), 'DELETE');
            }

            return Util.save(templates.url({ids: id, locale: locale}), 'DELETE');
        },

        /**
         * Remove article-page.
         *
         * @param {String} articleId
         * @param {String} pageId
         * @param {String} locale
         */
        removePage: function(articleId, pageId, locale) {
            return Util.save(templates.pageUrl({articleId: articleId, pageId: pageId, locale: locale}), 'DELETE');
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
                templates.url({id: id, postfix: 'versions', locale: locale, version: version, action: 'restore'}),
                'POST'
            );
        },

        /**
         * Copy given article.
         *
         * @param {String} id
         * @param {String} locale
         */
        copy: function(id, locale) {
            return Util.save(templates.url({id: id, locale: locale, action: 'copy'}), 'POST');
        },

        /**
         * Copy given article-pages.
         *
         * @param {String} id
         * @param {String[]} pages
         * @param {String} locale
         */
        orderPages: function(id, pages, locale) {
            return Util.save(templates.url({id: id, locale: locale, action: 'order'}), 'POST', {pages: pages});
        },

        /**
         * Returns url for copy article from a given locale to a array of other locales url.
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
            return templates.url({id: id, postfix: 'versions', locale: locale});
        }
    };
});
