/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

define([
    'underscore',
    'services/husky/storage',
    'sulucontent/components/copy-locale-overlay/main',
    'sulucontent/components/open-ghost-overlay/main',
    'services/suluarticle/article-manager',
    'services/suluarticle/article-router',
    'services/suluarticle/list-helper'
], function(_, storage, CopyLocale, OpenGhost, ArticleManager, ArticleRouter, listHelper) {

    'use strict';

    var defaults = {
        options: {
            config: {},
            storageName: 'articles'
        },

        templates: {
            list: [
                '<div class="content-title">',
                '    <h2><%= translations.headline %> <span class="type"><%= type %></span></h2>',
                '</div>',
                '<div class="list-toolbar-container"></div>',
                '<div class="list-info"></div>',
                '<div class="datagrid-container"></div>',
                '<div class="dialog"></div>'
            ].join(''),

            route: [
                'articles', '<% if (!!type) { %>:<%=type%><% } %>', '/<%=locale%>'
            ].join(''),

            brokenTemplate: [
                '<p><%= translations.brokenTemplateMessage %></p>',
                '<p>',
                '    <%= translations.brokenTemplateName %>: <%= item.originalStructureType %><br/>',
                '    <%= translations.brokenTemplateUuid %>: <%= item.id %>',
                '</p>'
            ].join('')
        },

        translations: {
            headline: 'sulu_article.list.title',
            published: 'public.published',
            unpublished: 'public.unpublished',
            publishedWithDraft: 'public.published-with-draft',
            filterMe: 'sulu_article.list.filter.me',
            filterAll: 'sulu_article.list.filter.all',
            filterByAuthor: 'sulu_article.list.filter.by-author',
            filterByCategory: 'sulu_article.list.filter.by-category',
            filterByTag: 'sulu_article.list.filter.by-tag',
            filterByPage: 'sulu_article.list.filter.by-page',
            filterByTimescale: 'sulu_article.list.filter.by-timescale',
            brokenTemplateTitle: 'sulu_content.broken-template.title',
            brokenTemplateMessage: 'sulu_article.broken-template.message',
            brokenTemplateName: 'sulu_content.broken-template.message.template-name',
            brokenTemplateUuid: 'sulu_content.broken-template.message.uuid',
            openGhostOverlay: {
                info: 'sulu_article.settings.open-ghost-overlay.info',
                new: 'sulu_article.settings.open-ghost-overlay.new',
                copy: 'sulu_article.settings.open-ghost-overlay.copy',
                ok: 'sulu_article.settings.open-ghost-overlay.ok'
            }
        }
    };

    return {

        defaults: defaults,

        data: {
            contactId: null
        },

        header: function() {
            this.storage = storage.get('sulu', this.options.storageName);

            var types = this.options.config.types,
                typeNames = this.options.config.typeNames,
                button = {
                    icon: 'plus-circle',
                    title: 'public.add-new'
                },
                tabs = false,
                tabItems,
                tabPreselect = null,
                preselectedType = this.options.type || this.storage.getWithDefault('type', null);

            if (1 === typeNames.length) {
                button.callback = function() {
                    this.toAdd(typeNames[0]);
                }.bind(this);
            } else {
                button.dropdownItems = _.map(typeNames, function(type) {
                    return {
                        title: types[type].title,
                        callback: function() {
                            this.toAdd(type);
                        }.bind(this)
                    };
                }.bind(this));

                tabItems = [];

                // add tab item 'all' if parameter is true
                if (this.options.config.displayTabAll === true) {
                    tabItems.push(
                        {
                            name: 'public.all',
                            key: null
                        }
                    );
                }

                // add tab item for each type
                _.each(typeNames, function(type) {
                    tabItems.push(
                        {
                            id: type,
                            name: types[type].title,
                            key: type
                        }
                    );

                    if (type === preselectedType) {
                        tabPreselect = types[type].title;
                    }
                }.bind(this));

                tabs = {
                    componentOptions: {
                        callback: this.typeChange.bind(this),
                        preselector: 'name',
                        preselect: tabPreselect
                    },
                    data: tabItems
                };
            }

            return {
                noBack: true,

                tabs: tabs,

                toolbar: {
                    buttons: {
                        addArticle: {options: button},
                        deleteSelected: {}
                    },

                    languageChanger: {
                        data: this.options.config.languageChanger,
                        preSelected: this.options.locale
                    }
                }
            };
        },

        layout: {
            content: {
                width: 'max'
            }
        },

        initialize: function() {
            if (!!this.options.type) {
                this.storage.set('type', this.options.type);
            } else if (this.storage.has('type')) {
                ArticleRouter.toList(this.options.locale, this.storage.get('type'));
                this.options.type = this.storage.get('type');
            }

            this.render();

            this.bindCustomEvents();
        },

        render: function() {
            var type = this.options.config.types[this.options.type];

            this.$el.html(this.templates.list({
                translations: this.translations,
                type: type ? this.sandbox.translate(type.title) : ''
            }));

            var urlArticleApi = '/admin/api/articles?sortBy=authored&sortOrder=desc&locale=' + this.options.locale + (this.options.type ? ('&type=' + this.options.type) : '');
            var toolbar = this.retrieveListToolbarTemplate(this.loadFilterFromStorage());

            this.sandbox.sulu.initListToolbarAndList.call(this,
                'article',
                '/admin/api/articles/fields',
                {
                    el: this.$find('.list-toolbar-container'),
                    instanceName: 'articles',
                    template: toolbar
                },
                {
                    el: this.sandbox.dom.find('.datagrid-container'),
                    url: urlArticleApi,
                    storageName: this.options.storageName,
                    searchInstanceName: 'articles',
                    searchFields: ['title', 'route_path', 'changer_full_name', 'creator_full_name', 'author_full_name'],
                    resultKey: 'articles',
                    instanceName: 'articles',
                    actionCallback: this.actionCallback.bind(this),
                    viewOptions: {
                        table: {
                            actionIconColumn: 'title',
                            actionIcon: function(item) {
                                if (item.broken) {
                                    return 'info';
                                }

                                return 'pencil';
                            },
                            cssClasses: [
                                {
                                    column: 'title',
                                    callback: function(item) {
                                        if (item.broken) {
                                            return 'article-broken';
                                        }
                                    }.bind(this)
                                }
                            ],
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
                    }
                }
            );
        },

        /**
         * Handles click for datagrid.
         *
         * For broken article: Show brocken info
         * For ghost articles: Open ghost overlay
         * Else: Goto edit
         *
         * @param {String} id
         * @param {Object} article
         *
         * @returns {*}
         */
        actionCallback: function(id, article) {
            if (article.broken) {
                return this.showBrokenInfo(article);
            }

            if ('ghost' !== article.localizationState.state) {
                return this.toEdit(id);
            }

            ArticleManager.load(id, this.options.locale).then(function(response) {
                OpenGhost.openGhost.call(
                    this,
                    response,
                    this.translations.openGhostOverlay
                ).then(
                    function(copy, src) {
                        if (!!copy) {
                            CopyLocale.copyLocale.call(
                                this,
                                id,
                                src,
                                [this.options.locale],
                                function() {
                                    this.toEdit(id);
                                }.bind(this)
                            );
                        } else {
                            this.toEdit(id);
                        }
                    }.bind(this)
                );
            }.bind(this)).fail(function(xhr) {
                this.sandbox.emit('sulu.article.error', xhr.status, xhr.responseJSON.code || 0, data);
            }.bind(this));
        },

        toEdit: function(id, locale) {
            ArticleRouter.toEdit(id, (locale || this.options.locale));
        },

        toAdd: function(type, locale) {
            ArticleRouter.toAdd((locale || this.options.locale), type);
        },

        toList: function(locale) {
            ArticleRouter.toList((locale || this.options.locale), this.options.type);
        },

        deleteItems: function(ids) {
            this.sandbox.sulu.showDeleteDialog(function(wasConfirmed) {
                if (!wasConfirmed) {
                    return;
                }

                this.sandbox.emit('sulu.header.toolbar.item.loading', 'deleteSelected');
                ArticleManager.remove(ids, this.options.locale).then(function() {
                    _.each(ids, function(id) {
                        this.sandbox.emit('husky.datagrid.articles.record.remove', id);
                    }.bind(this));
                    this.sandbox.emit('sulu.header.toolbar.item.enable', 'deleteSelected', false);
                }.bind(this)).fail(function() {
                    this.sandbox.emit('sulu.header.toolbar.item.enable', 'deleteSelected', false);
                }.bind(this));
            }.bind(this));
        },

        typeChange: function(item) {
            // Save the tab key. Can be removed when issue #72 is solved:
            // https://github.com/sulu/SuluArticleBundle/issues/72
            this.options.type = item.key;

            this.sandbox.emit('husky.datagrid.articles.url.update', {page: 1, type: this.options.type});
            ArticleRouter.toList(this.options.locale, this.options.type, false, false);
            this.storage.set('type', this.options.type);

            this.setTypeName(item.key ? item.name : '');
        },

        /**
         * Returns copy article from a given locale to a array of other locales url.
         *
         * @param {string} id
         * @param {string} src
         * @param {string[]} dest
         *
         * @returns {string}
         */
        getCopyLocaleUrl: function(id, src, dest) {
            return ArticleManager.getCopyLocaleUrl(id, src, dest);
        },

        bindCustomEvents: function() {
            this.sandbox.on('husky.datagrid.articles.number.selections', function(number) {
                var postfix = number > 0 ? 'enable' : 'disable';
                this.sandbox.emit('sulu.header.toolbar.item.' + postfix, 'deleteSelected', false);
            }.bind(this));

            this.sandbox.on('sulu.toolbar.delete', function() {
                this.sandbox.emit('husky.datagrid.articles.items.get-selected', this.deleteItems.bind(this));
            }.bind(this));

            this.sandbox.on('sulu.header.language-changed', function(item) {
                if (item.id === this.options.locale) {
                    return;
                }

                this.sandbox.sulu.saveUserSetting(this.options.config.settingsKey, item.id);
                this.toList(item.id);
            }.bind(this));

            this.sandbox.on('husky.toolbar.articles.initialized', function() {
                this.sandbox.emit('husky.toolbar.articles.item.mark', this.loadFilterFromStorage().filterKey);
            }.bind(this));
        },

        /**
         * Generates list toolbar buttons.
         *
         * @param {Object} filter
         */
        retrieveListToolbarTemplate: function(filter) {
            return this.sandbox.sulu.buttons.get({
                settings: {
                    options: {
                        dropdownItems: [
                            {
                                type: 'columnOptions'
                            }
                        ]
                    }
                },
                authoredDate: {
                    options: {
                        icon: 'calendar',
                        group: 2,
                        title: listHelper.getAuthoredTitle(filter.authored),
                        showTitle: true,
                        dropdownOptions: {
                            idAttribute: 'id',
                            markSelected: true
                        },
                        dropdownItems: [
                            {
                                title: this.translations.filterAll,
                                marked: true,
                                callback: function() {
                                    var filter = this.appendFilter('authored', {from: null, to: null});
                                    this.sandbox.emit(
                                        'husky.toolbar.articles.button.set',
                                        'authoredDate',
                                        {title: listHelper.getAuthoredTitle(filter.authored)}
                                    );
                                }.bind(this)
                            },
                            {
                                id: 'timescale',
                                title: this.translations.filterByTimescale,
                                callback: this.openAuthoredSelectionOverlay.bind(this)
                            }
                        ]
                    }
                },
                workflowStage: {
                    options: {
                        icon: 'circle-o',
                        group: 2,
                        title: listHelper.getPublishedTitle(filter.workflowStage),
                        showTitle: true,
                        dropdownOptions: {
                            idAttribute: 'id',
                            markSelected: true,
                            changeButton: true
                        },
                        dropdownItems: [
                            {
                                title: this.translations.filterAll,
                                marked: !filter.workflowStage,
                                callback: function() {
                                    this.appendFilter('workflowStage', null);
                                }.bind(this)
                            },
                            {
                                id: 'published',
                                title: this.translations.published,
                                marked: filter.workflowStage === 'published',
                                callback: function() {
                                    this.appendFilter('workflowStage', 'published');
                                }.bind(this)
                            },
                            {
                                id: 'test',
                                title: this.translations.unpublished,
                                marked: filter.workflowStage === 'test',
                                callback: function() {
                                    this.appendFilter('workflowStage', 'test');
                                }.bind(this)
                            }
                        ]
                    }
                },
                filter: {
                    options: {
                        icon: 'filter',
                        group: 2,
                        title: listHelper.getFilterTitle(filter),
                        showTitle: true,
                        dropdownOptions: {
                            idAttribute: 'id',
                            markSelected: true,
                            changeButton: false,
                            preSelected: filter.filterKey
                        },
                        dropdownItems: [
                            {
                                id: 'all',
                                title: this.translations.filterAll,
                                marked: filter.filterKey === 'all',
                                callback: function() {
                                    this.replaceFilter('all');
                                }.bind(this)
                            },
                            {
                                id: 'me',
                                title: this.translations.filterMe,
                                marked: filter.filterKey === 'me',
                                callback: function() {
                                    this.replaceFilter('contact', this.sandbox.sulu.user.contact, 'me');
                                }.bind(this)
                            },
                            {
                                id: 'filterByAuthor',
                                title: this.translations.filterByAuthor + ' ...',
                                marked: filter.filterKey === 'filterByAuthor',
                                callback: this.openContactSelectionOverlay.bind(this)
                            },
                            {
                                divider: true
                            },
                            {
                                id: 'filterByCategory',
                                title: this.translations.filterByCategory + ' ...',
                                marked: filter.filterKey === 'filterByCategory',
                                callback: this.openCategorySelectionOverlay.bind(this)
                            },
                            {
                                id: 'filterByTag',
                                title: this.translations.filterByTag + ' ...',
                                marked: filter.filterKey === 'filterByTag',
                                callback: this.openTagSelectionOverlay.bind(this)
                            },
                            {
                                id: 'filterByPage',
                                title: this.translations.filterByPage + ' ...',
                                marked: filter.filterKey === 'filterByPage',
                                callback: this.openPageSelectionOverlay.bind(this)
                            }
                        ]
                    }
                }
            });
        },

        /**
         * Opens contact selection overlay.
         */
        openContactSelectionOverlay: function() {
            var $container = $('<div/>');

            this.$el.append($container);

            this.sandbox.start([{
                name: 'articles/list/contact-selection@suluarticle',
                options: {
                    el: $container,
                    locale: this.options.locale,
                    data: {
                        contact: this.loadFilterFromStorage().contact
                    },
                    selectCallback: function(data) {
                        this.replaceFilter('contact', data.contactItem, 'filterByAuthor');
                    }.bind(this)
                }
            }]);
        },

        /**
         * Opens category selection overlay.
         */
        openCategorySelectionOverlay: function() {
            var $container = $('<div/>');

            this.$el.append($container);

            this.sandbox.start([{
                name: 'articles/list/category-selection@suluarticle',
                options: {
                    el: $container,
                    locale: this.options.locale,
                    data: {
                        category: this.loadFilterFromStorage().category
                    },
                    selectCallback: function(data) {
                        this.replaceFilter('category', data.categoryItem, 'filterByCategory');
                    }.bind(this)
                }
            }]);
        },

        /**
         * Opens tag selection overlay.
         */
        openTagSelectionOverlay: function() {
            var $container = $('<div/>');

            this.$el.append($container);

            this.sandbox.start([{
                name: 'articles/list/tag-selection@suluarticle',
                options: {
                    el: $container,
                    locale: this.options.locale,
                    data: {
                        tag: this.loadFilterFromStorage().tag
                    },
                    selectCallback: function(data) {
                        this.replaceFilter('tag', data.tagItem, 'filterByTag');
                    }.bind(this)
                }
            }]);
        },

        /**
         * Opens page selection overlay.
         */
        openPageSelectionOverlay: function() {
            var $container = $('<div/>');

            this.$el.append($container);

            this.sandbox.start([{
                name: 'page-tree-route/page-select@suluarticle',
                options: {
                    el: $container,
                    locale: this.options.locale,
                    data: this.loadFilterFromStorage().page,
                    translations: {
                        overlayTitle: 'sulu_article.page-selection-overlay.title'
                    },
                    selectCallback: function(data) {
                        this.replaceFilter('page', data, 'filterByPage');
                    }.bind(this)
                }
            }]);
        },

        /**
         * Opens authored selection overlay.
         */
        openAuthoredSelectionOverlay: function() {
            var $container = $('<div/>');

            this.$el.append($container);

            this.sandbox.start([{
                name: 'articles/list/authored-selection@suluarticle',
                options: {
                    el: $container,
                    locale: this.options.locale,
                    data: this.loadFilterFromStorage().authored,
                    selectCallback: function(data) {
                        var filter = this.appendFilter('authored', data);
                        this.sandbox.emit(
                            'husky.toolbar.articles.button.set',
                            'authoredDate',
                            {title: listHelper.getAuthoredTitle(filter.authored)}
                        );
                    }.bind(this)
                }
            }]);
        },

        /**
         * Replace given filter.
         *
         * @param {String} key
         * @param {Object|String} value
         * @param {String} filterKey
         *
         * @return {Object}
         */
        replaceFilter: function(key, value, filterKey) {
            var filter = this.loadFilterFromStorage();

            filter.category = null;
            filter.contact = null;
            filter.tag = null;
            filter.page = null;
            filter.filterKey = filterKey || key;

            if (value) {
                filter[key] = value;
            }

            return this.applyFilterToList(filter);
        },

        /**
         * Append given filter.
         *
         * @param {String} key
         * @param {Object|String} value
         *
         * @return {Object}
         */
        appendFilter: function(key, value) {
            var filter = this.loadFilterFromStorage();
            filter[key] = value;

            return this.applyFilterToList(filter);
        },

        /**
         * Emits the url update event for the list, changes the title of the filter button
         * and saves the selected filters in the storage.
         *
         * @param {Object} filter
         *
         * @return {Object}
         */
        applyFilterToList: function(filter) {
            var update = {
                contactId: filter.contact ? filter.contact.id : null,
                categoryId: filter.category ? filter.category.id : null,
                tagId: filter.tag ? filter.tag.id : null,
                pageId: filter.page ? filter.page.id : null,
                authoredFrom: filter.authored ? filter.authored.from : null,
                authoredTo: filter.authored ? filter.authored.to : null,
                workflowStage: filter.workflowStage ? filter.workflowStage : null
            };

            this.saveFilterToStorage(filter);

            this.sandbox.emit('husky.datagrid.articles.url.update', update);
            this.sandbox.emit('husky.toolbar.articles.button.set', 'filter', {title: listHelper.getFilterTitle(filter)});

            return filter;
        },

        /**
         * Retrieves the filter from the storage.
         *
         * @returns {Object}
         */
        loadFilterFromStorage: function() {
            return this.storage.getWithDefault('filter', {
                filterKey: 'all',
                contact: null,
                category: null,
                tag: null,
                authored: {from: null, to: null}
            });
        },

        /**
         * Save the filter in the storage.
         *
         * @param {Object} filter
         */
        saveFilterToStorage: function(filter) {
            this.storage.set('filter', filter);
        },

        setTypeName: function(name) {
            this.$el.find('.type').text(this.sandbox.translate(name));
        },

        showBrokenInfo: function(item) {
            var $element = this.sandbox.dom.createElement('<div/>');
            this.sandbox.dom.append(this.$el, $element);

            this.sandbox.start([
                {
                    name: 'overlay@husky',
                    options: {
                        el: $element,
                        type: 'alert',
                        slides: [
                            {
                                title: this.translations.brokenTemplateTitle,
                                message: this.templates.brokenTemplate({
                                    translations: this.translations,
                                    item: item
                                }),
                                contentSpacing: false,
                                type: 'alert',
                                buttons: [
                                    {
                                        type: 'ok',
                                        align: 'center'
                                    }
                                ]
                            }
                        ]
                    }
                }
            ]);
        }
    };
});
