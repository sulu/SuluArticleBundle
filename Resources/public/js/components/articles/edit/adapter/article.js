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

    return {
        startPreview: function(component, data) {
            var pageData = data;
            if (!!pageData.type && pageData.type.name === 'ghost') {
                pageData = {id: pageData.id};
            }

            var preview = Preview.initialize({});
            preview.start(
                'Sulu\\Bundle\\ArticleBundle\\Document\\ArticleDocument',
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
            // do nothing
        },

        prepareData: function(data) {
            return data;
        },

        save: function(component, data, action) {
            data.template = component.template;

            _.each(data, function(value, key) {
                component.data[key] = value;
            });

            if (!!component.data.type && component.data.type.name === 'ghost') {
                component.data['_hash'] = null;
            }

            return ArticleManager.save(component.data, component.data.id, component.options.locale, action);
        }
    };
});
