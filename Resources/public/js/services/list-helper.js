/*
 * This file is part of the Sulu CMS.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

define(['underscore', 'services/husky/translator'], function(_, translator) {

    'use strict';

    var translate = function(translations) {
            var keys = _.keys(translations),
                length = keys.length,
                results = {};

            for (var index = 0; index < length; index++) {
                var currentKey = keys[index];
                results[currentKey] = translator.translate(translations[currentKey]);
            }

            return results;
        },

        translations = translate(
            {
                filterAll: 'sulu_article.list.filter.all',
                from: 'sulu_article.authored-selection-overlay.from',
                to: 'sulu_article.authored-selection-overlay.to',
                published: 'public.published',
                unpublished: 'public.unpublished',
                publishedWithDraft: 'public.published-with-draft',
                shadowArticle: 'sulu_article.shadow_article',
                filterByAuthor: 'sulu_article.list.filter.by-author',
                filterMe: 'sulu_article.list.filter.me',
                filterByCategory: 'sulu_article.list.filter.by-category',
                filterByTag: 'sulu_article.list.filter.by-tag',
                filterByPage: 'sulu_article.list.filter.by-page',
                filterByTimescale: 'sulu_article.list.filter.by-timescale',
            }
        ),

        templates = {
            draftIcon: _.template('<span class="draft-icon" title="<%= title %>"/>'),
            publishedIcon: _.template('<span class="published-icon" title="<%= title %>"/>'),
            shadowIcon: _.template('<span class="fa-share" title="<%= title %>"></span>'),
        };

    return {

        translations: translations,

        /**
         * Returns the title for the authored button.
         *
         * @param {Object} authored
         *
         * @return {String}
         */
        getAuthoredTitle: function(authored) {
            if (!authored) {
                return translations.filterAll;
            }

            var parts = [];
            if (authored.from) {
                parts.push(translations.from);
                parts.push(App.date.format(authored.from + 'T00:00'));
            }
            if (authored.to) {
                parts.push(parts.length > 0 ? translations.to.toLowerCase() : translations.to);
                parts.push(App.date.format(authored.to + 'T00:00'));
            }

            if (parts.length === 0) {
                return translations.filterAll;
            }

            return parts.join(' ');
        },

        /**
         * Returns the title for the published button.
         *
         * @param {String} workflowStage
         *
         * @return {String}
         */
        getPublishedTitle: function(workflowStage) {
            if (!workflowStage) {
                return translations.filterAll;
            }

            if (workflowStage === 'published') {
                return translations.published;
            }

            return translations.unpublished;
        },

        /**
         * Returns the title for the filter button.
         *
         * @param {Object} filter
         *
         * @return {String}
         */
        getFilterTitle: function(filter) {
            if (!filter) {
                return translations.filterAll;
            }

            switch (filter.filterKey) {
                case 'filterByAuthor':
                    return translations.filterByAuthor + ' ' + filter.contact.firstName + ' ' + filter.contact.lastName;
                case 'me':
                    return translations.filterMe;
                case 'filterByCategory':
                    return translations.filterByCategory + ' ' + filter.category.name;
                case 'filterByTag':
                    return translations.filterByTag + ' ' + filter.tag.name;
                case 'filterByPage':
                    return translations.filterByPage + ' ' + filter.page.title;
            }

            return translations.filterAll;
        },

        /**
         * Generates badge icon for given datagrid-item.
         *
         * @param {Object} item
         * @param {Object} badge
         *
         * @returns {Object}
         */
        generateWorkflowBadge: function(item, badge) {
            var icons = '',
                tooltip = translations.unpublished;

            if (!!item.published && !item.publishedState) {
                tooltip = translations.publishedWithDraft;
                icons += templates.publishedIcon({title: tooltip});
            }
            if (!item.publishedState) {
                icons += templates.draftIcon({title: tooltip});
            }

            badge.title = icons;
            badge.cssClass = 'badge-none';

            return badge;
        },

        /**
         * Generates badge for given datagrid-item.
         *
         * @param {Object} item
         * @param {Object} badge
         * @param {String} locale
         *
         * @returns {Object}
         */
        generateLocalizationBadge: function(item, badge, locale) {
            if (!!item.localizationState &&
                item.localizationState.state === 'ghost' &&
                item.localizationState.locale !== locale
            ) {
                badge.title = item.localizationState.locale;

                return badge;
            }

            if (!!item.localizationState &&
                item.localizationState.state === 'shadow'
            ) {
                badge.title = templates.shadowIcon({title: translations.shadowArticle});
                badge.cssClass = 'badge-none badge-color-black';

                return badge;
            }

            return false;
        }
    };
});
