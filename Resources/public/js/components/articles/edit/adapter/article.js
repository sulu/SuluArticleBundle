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
        getCopyLocaleUrl: function(id, dataId, src, dest) {
            return ArticleManager.getCopyLocaleUrl(id, src, dest);
        },

        startPreview: function(component, data) {
            var preview = Preview.initialize({});
            preview.start(
                'Sulu\\Bundle\\ArticleBundle\\Document\\ArticleDocument',
                component.options.id,
                component.options.locale,
                data
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

            return ArticleManager.save(component.data, component.data.id, component.options.locale, action);
        }
    };
});
