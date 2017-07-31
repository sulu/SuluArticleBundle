/*
 * This file is part of the Sulu CMS.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

/**
 * Handles articles for teaser-selection.
 *
 * @class ListArticleTeaser
 * @constructor
 */
define(['underscore', 'config'], function(_, Config) {

    'use strict';

    var defaults = {
            options: {
                locale: null,
                url: '',
                resultKey: null,
                searchFields: [],
                matchings: [],
                instanceName: 'teaser-selection',
                selectCallback: function(item) {
                }
            },
            templates: {
                skeleton: [
                    '<div class="teaser-selection-tabs"></div>',
                    '<div class="grid">',
                    '   <div class="grid-row search-row">',
                    '       <div class="grid-col-8"/>',
                    '       <div class="grid-col-4 teaser-selection-search"/>',
                    '   </div>',
                    '   <div class="grid-row">',
                    '       <div class="grid-col-12 teaser-selection-list"/>',
                    '   </div>',
                    '</div>'
                ].join('')
            }
        },

        config = Config.get('sulu_article'),

        getTabsData = function() {
            if (1 === config.typeNames.length) {
                return [];
            }

            var tabsData = [];

            if (config.displayTabAll === true) {
                tabsData.push(
                    {
                        id: 'all',
                        name: 'public.all',
                    }
                );
            }

            // add tab item for each type
            _.each(config.typeNames, function(type) {
                tabsData.push(
                    {
                        id: type,
                        name: config.types[type].title,
                    }
                );
            }.bind(this));

            return tabsData;
        };

    return {
        defaults: defaults,

        initialize: function() {
            this.$el.parent().removeClass('content-spacing');
            this.$el.parent().addClass('article-teaser-selection');

            var $container = $(this.templates.skeleton());
            this.$el.append($container);

            this.sandbox.start([
                {
                    name: 'search@husky',
                    options: {
                        el: '.teaser-selection-search',
                        appearance: 'white small',
                        instanceName: this.options.instanceName + '-teaser-search'
                    }
                },
                {
                    name: 'datagrid@husky',
                    options: {
                        el: '.teaser-selection-list',
                        instanceName: this.options.instanceName,
                        url: this.getUrl(),
                        preselected: _.map(this.options.data, function(item) {
                            return item.id;
                        }),
                        resultKey: this.options.resultKey,
                        sortable: false,
                        columnOptionsInstanceName: '',
                        clickCallback: function(item) {
                            this.sandbox.emit('husky.datagrid.teaser-selection.toggle.item', item);
                        }.bind(this),
                        selectedCounter: true,
                        searchInstanceName: this.options.instanceName + '-teaser-search',
                        searchFields: this.options.searchFields,
                        paginationOptions: {
                            dropdown: {
                                limit: 20
                            }
                        },
                        matchings: this.options.matchings,
                    }
                },
                {
                    name: 'tabs@husky',
                    options: {
                        el: '.teaser-selection-tabs',
                        data: getTabsData(),
                        callback: this.changeType.bind(this)
                    }
                }
            ]);

            this.bindCustomEvents();
        },

        bindCustomEvents: function() {
            this.sandbox.on('husky.datagrid.teaser-selection.item.select', function(id) {
                this.options.selectCallback({type: this.options.type, id: id});
            }.bind(this));
            this.sandbox.on('husky.datagrid.teaser-selection.item.deselect', function(id) {
                this.options.deselectCallback({type: this.options.type, id: id});
            }.bind(this));
        },

        getUrl: function() {
            return this.options.url.replace('{locale}', this.options.locale);
        },

        changeType: function(item) {
            var type = item.id;
            if (item.id === 'all') {
                type = null;
            }

            this.sandbox.emit('husky.datagrid.' + this.options.instanceName + '.url.update', {type: type});
        }
    };
});
