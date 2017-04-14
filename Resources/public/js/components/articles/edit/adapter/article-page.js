/*
 * This file is part of the Sulu CMS.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

define([
    'underscore',
    'services/suluarticle/article-manager',
    'services/sulupreview/preview'
], function(_, ArticleManager, Preview) {

    'use strict';

    var getPage = function(page, data) {
        if (!page || !data._embedded || data._embedded.pages.length <= page - 2) {
            return {};
        }

        return data._embedded.pages[page - 2];
    };

    return {
        getCopyLocaleUrl: function(id, dataId, src, dest) {
            return ArticleManager.getCopyPageLocaleUrl(id, dataId, src, dest);
        },

        startPreview: function(component, data) {
            var pageData = getPage(component.options.page, data);
            if (!pageData.id) {
                return;
            }

            if (!!pageData.type && pageData.type.name === 'ghost') {
                pageData = {id: pageData.id};
            }

            var preview = Preview.initialize({});
            preview.start(
                'Sulu\\Bundle\\ArticleBundle\\Document\\ArticlePageDocument',
                pageData.id,
                component.options.locale,
                pageData
            );

            return preview;
        },

        destroyPreview: function(preview) {
            Preview.destroy(preview);
        },

        beforeFormCreate: function(component) {
            var titleProperty = component.getTitleProperty(),
                routePathProperty = component.getRoutePathProperty();

            if (titleProperty) {
                titleProperty.$el.closest('.grid-row').remove();
            }
            if (routePathProperty) {
                routePathProperty.$el.closest('.grid-row').remove();
            }

            var $titleComponent = component.$el.find('.section-highlight');
            if (0 === component.$el.find('.highlight-section *[data-mapper-property]').length) {
                component.$el.find('.highlight-section').remove();

                $titleComponent = component.$el.find('#content-form');
            }

            $titleComponent.prepend(
                '<div class="sulu-title article-title"><div class="content-title underlined"><h2>' + component.data.title + '</h2></div></div>'
            );
        },

        prepareData: function(data, component) {
            return getPage(component.options.page, data);
        },

        save: function(component, data, action) {
            var pageId = null,
                pageIndex = component.options.page - 2;

            if (!!component.data._embedded.pages[pageIndex]) {
                pageId = component.data._embedded.pages[pageIndex].id;
            }

            data.template = component.template;
            return ArticleManager.savePage(data, component.data.id, pageId, component.options.locale, action)
                .then(function() {
                    return ArticleManager.loadSync(component.data.id, component.options.locale).responseJSON;
                });
        }
    };
});
