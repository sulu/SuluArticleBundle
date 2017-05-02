/*
 * This file is part of the Sulu CMS.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

/**
 * Overlay to select parent page.
 *
 * @class page-tree-route/page-select
 * @constructor
 */
define(function() {
    return {

        defaults: {
            options: {
                selected: null,
                locale: null,
                selectCallback: function(item) {
                },
                cancelCallback: function() {
                }
            },
            translations: {
                overlayTitle: 'public.choose'
            }
        },

        initialize: function() {
            this.id = this.options.selected;

            var $container = $('<div/>');
            var $componentContainer = $('<div/>');
            this.$el.append($container);

            this.sandbox.start([
                {
                    name: 'overlay@husky',
                    options: {
                        el: $container,
                        openOnStart: true,
                        removeOnClose: true,
                        skin: 'medium',
                        instanceName: 'page-select',
                        slides: [
                            {
                                title: this.translations.overlayTitle,
                                data: $componentContainer,
                                cssClass: 'data-source-slide',
                                contentSpacing: false,
                                okCallback: function() {
                                    if (this.item) {
                                        this.options.selectCallback(this.item);
                                    }
                                }.bind(this),
                                cancelCallback: function() {
                                    this.options.cancelCallback();
                                }.bind(this)
                            }
                        ]
                    }
                }
            ]).then(this.startContentDatasource.bind(this, $componentContainer));
        },

        startContentDatasource: function($componentContainer) {
            this.sandbox.start(
                [
                    {
                        name: 'content-datasource@sulucontent',
                        options: {
                            el: $componentContainer,
                            selected: this.options.selected,
                            locale: this.options.locale,
                            selectedUrl: '/admin/api/nodes/{datasource}?tree=true&language={locale}&fields=title,order,published,url&webspace-nodes=all',
                            rootUrl: '/admin/api/nodes?language={locale}&fields=title,order,published&webspace-nodes=all',
                            resultKey: 'nodes',
                            instanceName: 'internal-link',
                            instanceNamePrefix: '',
                            showStatus: true,
                            selectCallback: function(id, path, title, item) {
                                if (this.id === id) {
                                    return this.setItem(null, null);
                                }

                                this.setItem(item, id);
                            }.bind(this)
                        }
                    }
                ]
            );
        },

        setItem: function(item, id) {
            this.item = item;
            this.id = id;

            var action = 'activate';
            if (!item) {
                action = 'deactivate';
            }

            this.sandbox.emit('husky.overlay.page-select.okbutton.' + action);
        }
    };
});
