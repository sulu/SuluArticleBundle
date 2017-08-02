/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

define(['jquery'], function($) {

    'use strict';

    var defaults = {
        options: {
            locale: null,
            data: {
                page: null
            },
            selectCallback: function(data) {
            },
        },
    };

    return {

        defaults: defaults,

        initialize: function() {
            var $container = $('<div/>');
            this.$el.append($container);

            this.id = this.options.data.id;

            this.sandbox.start([
                {
                    name: 'page-tree-route/content-datasource@suluarticle',
                    options: {
                        el: $container,
                        selected: this.id,
                        locale: this.options.locale,
                        selectedUrl: '/admin/api/nodes/{datasource}?tree=true&language={locale}&fields=title,order,published,url&webspace-nodes=all',
                        rootUrl: '/admin/api/nodes?language={locale}&fields=title,order,published&webspace-nodes=all',
                        resultKey: 'nodes',
                        instanceName: 'internal-link',
                        instanceNamePrefix: '',
                        showStatus: true,
                        selectCallback: function(id, path, title, item) {
                            this.item = item;
                            this.id = id;
                        }.bind(this)
                    }
                }
            ]);

            this.sandbox.on('sulu_article.page-selection.form.get', this.getEvent.bind(this));
        },

        /**
         * Callback for get-event.
         */
        getEvent: function() {
            var data = this.options.data;
            data.pageId = this.id;
            data.pageItem = this.item;

            this.options.selectCallback(data);
        }
    };
});
