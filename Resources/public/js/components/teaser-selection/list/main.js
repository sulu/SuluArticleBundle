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
define(['underscore', 'config', 'services/suluarticle/list-helper'], function(_, Config, listHelper) {

    'use strict';

    var defaults = {
            options: {
                locale: null,
                url: '',
                resultKey: null,
                searchFields: [],
                instanceName: 'teaser-selection',
                selectCallback: function(item) {
                }
            },
            templates: {
                skeleton: [
                    '<div class="teaser-selection-tabs"></div>',
                    '<div class="grid">',
                    '   <div class="grid-row search-row">',
                    '       <div class="grid-col-12 teaser-selection-search"/>',
                    '   </div>',
                    '   <div class="grid-row">',
                    '       <div class="grid-col-12 teaser-selection-list"/>',
                    '   </div>',
                    '</div>'
                ].join(''),
            },
            translations: {
                filterAll: 'sulu_article.list.filter.all',
                filterByTimescale: 'sulu_article.list.filter.by-timescale',
                published: 'public.published',
                unpublished: 'public.unpublished',
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

            var toolbar = this.retrieveListToolbarTemplate();
            this.sandbox.sulu.initListToolbarAndList.call(this,
                'article',
                '/admin/api/articles/fields',
                {
                    el: '.teaser-selection-search',
                    instanceName: this.options.instanceName,
                    template: toolbar
                },
                {
                    el: '.teaser-selection-list',
                    instanceName: this.options.instanceName,
                    url: this.getUrl(),
                    preselected: _.map(this.options.data, function(item) {
                        return item.id;
                    }),
                    resultKey: this.options.resultKey,
                    clickCallback: function(item) {
                        this.sandbox.emit('husky.datagrid.teaser-selection.toggle.item', item);
                    }.bind(this),
                    searchInstanceName: this.options.instanceName,
                    searchFields: this.options.searchFields,
                    paginationOptions: {
                        dropdown: {
                            limit: 20
                        }
                    },
                    viewOptions: {
                        table: {
                            actionIconColumn: 'title',
                            badges: [
                                {
                                    column: 'title',
                                    callback: function(item, badge) {
                                        return listHelper.generateLocalizationBadge(item, badge, this.options.locale);
                                    }.bind(this)
                                },
                                {
                                    column: 'title',
                                    callback: listHelper.generateWorkflowBadge
                                }
                            ]
                        }
                    },
                }
            );

            this.sandbox.start([
                {
                    name: 'tabs@husky',
                    options: {
                        el: '.teaser-selection-tabs',
                        data: getTabsData(),
                        callback: this.changeType.bind(this)
                    }
                },
                {
                    name: 'articles/list/authored-selection/form@suluarticle',
                    options: {
                        el: '.slide.authored-slide .overlay-content',
                        data: this.options.data,
                        selectCallback: this.closeAuthoredSelection.bind(this)
                    }
                }
            ]);

            this.bindCustomEvents();
        },

        bindCustomEvents: function() {
            this.sandbox.on('husky.datagrid.' + this.options.instanceName + '.item.select', function(id) {
                this.options.selectCallback({type: this.options.type, id: id});
            }.bind(this));
            this.sandbox.on('husky.datagrid.' + this.options.instanceName + '.item.deselect', function(id) {
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
        },

        /**
         * Generates list toolbar buttons.
         */
        retrieveListToolbarTemplate: function() {
            return this.sandbox.sulu.buttons.get({
                authoredDate: {
                    options: {
                        icon: 'calendar',
                        group: 2,
                        title: this.translations.filterAll,
                        showTitle: true,
                        dropdownOptions: {
                            idAttribute: 'id',
                            markSelected: false
                        },
                        dropdownItems: [
                            {
                                title: this.translations.filterAll,
                                callback: this.closeAuthoredSelection.bind(this)
                            },
                            {
                                id: 'timescale',
                                title: this.translations.filterByTimescale,
                                callback: this.openAuthoredSelection.bind(this)
                            }
                        ]
                    }
                },
                workflowStage: {
                    options: {
                        icon: 'circle-o',
                        group: 2,
                        title: listHelper.getPublishedTitle(),
                        showTitle: true,
                        dropdownOptions: {
                            idAttribute: 'id',
                            markSelected: true,
                            changeButton: true
                        },
                        dropdownItems: [
                            {
                                title: this.translations.filterAll,
                                marked: true,
                                callback: function() {
                                    this.setWorkflowStage(null);
                                }.bind(this)
                            },
                            {
                                id: 'published',
                                title: this.translations.published,
                                callback: function() {
                                    this.setWorkflowStage('published');
                                }.bind(this)
                            },
                            {
                                id: 'test',
                                title: this.translations.unpublished,
                                callback: function() {
                                    this.setWorkflowStage('test');
                                }.bind(this)
                            }
                        ]
                    }
                }
            });
        },

        openAuthoredSelection: function() {
            this.$el.parent().addClass('limited');
            this.sandbox.emit('husky.overlay.' + this.options.instanceName + '.slide-to', 1);

            this.sandbox.once('sulu_content.teaser-selection.' + this.options.instanceName + '.ok-button.clicked', function() {
                this.sandbox.emit('sulu_article.authored-selection.form.get');
            }.bind(this));
        },

        closeAuthoredSelection: function(data) {
            this.$el.parent().removeClass('limited');

            this.sandbox.emit('husky.datagrid.' + this.options.instanceName + '.url.update', {
                authoredFrom: data ? data.from : null,
                authoredTo: data ? data.to : null,
            });

            this.sandbox.emit(
                'husky.toolbar.' + this.options.instanceName + '.button.set',
                'authoredDate',
                {title: listHelper.getAuthoredTitle(data)}
            );

            this.sandbox.emit('husky.overlay.' + this.options.instanceName + '.slide-to', 0);
        },

        setWorkflowStage: function(workflowStage) {
            this.sandbox.emit('husky.datagrid.' + this.options.instanceName + '.url.update', {
                workflowStage: workflowStage,
            });
        }
    };
});
