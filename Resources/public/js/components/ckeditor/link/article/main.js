/*
 * This file is part of the Sulu CMS.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

/**
 * Selection for article-provider.
 *
 * @class ckeditor/link/article
 * @constructor
 */
define([
    'config',
    'services/suluarticle/article-manager',
    'text!/admin/api/articles/fields'
], function(Config, Manager, fieldsResponse) {

    'use strict';

    // TODO merge with user settings.
    // TODO column-options
    var fields = JSON.parse(fieldsResponse),

        config = Config.get('sulu_article');

    return {

        defaults: {
            options: {
                link: {},
                locale: null,
                webspace: null,
                setHref: function(id, title, published) {
                },
                selectCallback: function(id, title) {
                }
            },

            templates: {
                contentDatasource: [
                    '<div class="grid">',
                    '   <div class="grid-row search-row">',
                    '       <div class="grid-col-8"/>',
                    '       <div id="href-select-search" class="grid-col-4"/>',
                    '   </div>',
                    '   <div class="grid-row">',
                    '       <div id="href-select" class="grid-col-12" style="max-height: 500px; overflow: scroll;"/>',
                    '   </div>',
                    '</div>'
                ].join('')
            }
        },

        initialize: function() {
            this.bindCustomEvents();
            this.resolveHref();
            this.render();
        },

        bindCustomEvents: function() {
            this.sandbox.on('husky.tabs.overlayinternal-link.item.select', this.typeChange.bind(this));
        },

        resolveHref: function() {
            if (!this.options.link.href) {
                this.options.setHref();

                return;
            }

            Manager.load(this.options.link.href, this.options.locale).then(function(data) {
                this.options.setHref(data.id, data.title, true);
            }.bind(this));
        },

        render: function() {
            var $container = $(this.templates.contentDatasource());
            this.$el.append($container);

            var type = '';
            if (config.typeNames.length > 0) {
                this.type = config.typeNames[0];
                type = '&type=' + config.typeNames[0];
            }

            this.sandbox.start(
                [
                    {
                        name: 'search@husky',
                        options: {
                            el: '#href-select-search',
                            appearance: 'white small',
                            instanceName: 'article-link-search'
                        }
                    },
                    {
                        name: 'datagrid@husky',
                        options: {
                            el: '#href-select',
                            instanceName: 'article-link',
                            url: Manager.url({locale: this.options.locale}) + '&sortBy=authored&sortOrder=desc' + type,
                            resultKey: 'articles',
                            clickCallback: function(id, item) {
                                this.options.selectCallback(id, item.title, true);
                            }.bind(this),
                            selectedCounter: true,
                            searchInstanceName: 'article-link-search',
                            searchFields: ['id', 'title'],
                            paginationOptions: {
                                dropdown: {
                                    limit: 20
                                }
                            },
                            viewOptions: {
                                table: {
                                    selectItem: false
                                }
                            },
                            matchings: fields
                        }
                    }
                ]
            );
        },

        typeChange: function(item) {
            for (var type in config.types) {
                if (config.types.hasOwnProperty(type) && config.types[type].title === item.name && this.type !== type) {
                    this.type = type;
                    return this.sandbox.emit('husky.datagrid.article-link.url.update', {type: type});
                }
            }

            this.type = null;
            this.sandbox.emit('husky.datagrid.article-link.url.update', {type: null});
        }
    };
});
