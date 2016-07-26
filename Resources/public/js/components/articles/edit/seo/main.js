/*
 * This file is part of the Sulu CMS.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

define(function() {

    'use strict';

    return {

        type: 'seo-tab',

        parseData: function(data) {
            return data.ext.seo;
        },

        getUrl: function() {
            var content = this.options.data();

            return this.options.excerptUrlPrefix + content.route;
        },

        save: function(data, action) {
            var content = this.options.data();
            content.ext.seo = data;

            this.sandbox.emit('sulu.articles.save', content, action);
        }
    };
});
