/*
 * This file is part of the Sulu CMS.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

define([
    'services/suluarticle/article-manager'
], function(ArticleManager) {

    'use strict';

    return {

        type: 'excerpt-tab',

        parseData: function(data) {
            return data.ext.excerpt;
        },

        getTemplate: function() {
            return 'text!/admin/content/template/form/excerpt.html?language=' + this.options.locale;
        },

        save: function(data, action) {
            var content = this.options.data();
            content.ext.excerpt = data;

            ArticleManager.save(content, content.id, this.options.locale, action).then(function(response) {
                this.sandbox.emit('sulu.tab.saved', response.id, response);
            }.bind(this)).fail(function(xhr) {
                this.sandbox.emit('sulu.article.error', xhr.status, xhr.responseJSON.code || 0, data);
            }.bind(this));
        }
    };
});
