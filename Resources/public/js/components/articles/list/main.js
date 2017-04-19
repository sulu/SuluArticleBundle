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
    'services/suluarticle/article-manager'
], function(_, storage, CopyLocale, OpenGhost, ArticleManager) {

    'use strict';

    var defaults = {
        options: {
            config: {},
            storageName: 'articles'
        },

        templates: {
            list: [
                '<div class="list-toolbar-container"></div>',
                '<div class="list-info"></div>',
                '<div class="datagrid-container"></div>',
                '<div class="dialog"></div>'
            ].join(''),
            draftIcon: '<span class="draft-icon" title="<%= title %>"/>',
            publishedIcon: '<span class="published-icon" title="<%= title %>"/>',
            route: [
                'articles',
                '<% if (!!type) { %>:<%=type%><% } %>',
                '/<%=locale%>'
            ].join('')
        },

        translations: {
            headline: 'sulu_article.list.title',
            unpublished: 'public.unpublished',
            publishedWithDraft: 'public.published-with-draft',
            filterMe: 'sulu_article.list.filter.me',
            filterAll: 'sulu_article.list.filter.all',
            filterBy: 'sulu_article.list.filter.by',
            filterByCategory: 'sulu_article.list.filter.by-category',
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
                var url = this.templates.route({type: this.storage.get('type'), locale: this.options.locale});
                this.sandbox.emit('sulu.router.navigate', url, false, false);
                this.options.type = this.storage.get('type');
            }

            this.render();

            this.bindCustomEvents();
        },

        render: function() {
            this.$el.html(this.templates.list());

            var urlArticleApi = '/admin/api/articles?sortBy=authored&sortOrder=desc&locale=' + this.options.locale + (this.options.type ? ('&type=' + this.options.type) : '');
            var filter = this.getFilterFromStorage();
            var filterTitle = this.getFilterTitle(filter.filterKey, filter.contact, filter.category);

            if (!!filter.contact) {
                urlArticleApi += '&contactId=' + filter.contact.id;
            }
            if (!!filter.category) {
                urlArticleApi += '&categoryId=' + filter.category.id;
            }

            this.sandbox.sulu.initListToolbarAndList.call(this,
                'article',
                '/admin/api/articles/fields',
                {
                    el: this.$find('.list-toolbar-container'),
                    instanceName: 'articles',
                    template: this.retrieveListToolbarTemplate(filterTitle)
                },
                {
                    el: this.sandbox.dom.find('.datagrid-container'),
                    url: urlArticleApi,
                    storageName: this.options.storageName,
                    searchInstanceName: 'articles',
                    searchFields: ['title'],
                    resultKey: 'articles',
                    instanceName: 'articles',
                    actionCallback: function(id, article) {
                        if ('ghost' === article.localizationState.state) {
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
                                this.sandbox.emit('sulu.article.error', xhr.status, data);
                            }.bind(this));
                        } else {
                            this.toEdit(id);
                        }
                    }.bind(this),
                    viewOptions: {
                        table: {
                            actionIconColumn: 'title',
                            badges: [
                                {
                                    column: 'title',
                                    callback: function(item, badge) {
                                        if (!!item.localizationState &&
                                            item.localizationState.state === 'ghost' &&
                                            item.localizationState.locale !== this.options.locale
                                        ) {
                                            badge.title = item.localizationState.locale;

                                            return badge;
                                        }

                                        return false;
                                    }.bind(this)
                                },
                                {
                                    column: 'title',
                                    callback: function(item, badge) {
                                        var icons = '',
                                            tooltip = this.translations.unpublished;

                                        if (!!item.published && !item.publishedState) {
                                            tooltip = this.translations.publishedWithDraft;
                                            icons += this.templates.publishedIcon({title: tooltip});
                                        }
                                        if (!item.publishedState) {
                                            icons += this.templates.draftIcon({title: tooltip});
                                        }

                                        badge.title = icons;
                                        badge.cssClass = 'badge-none';

                                        return badge;
                                    }.bind(this)
                                }
                            ]
                        }
                    }
                }
            );
        },

        toEdit: function(id, locale) {
            this.sandbox.emit('sulu.router.navigate', 'articles/' + (locale || this.options.locale) + '/edit:' + id + '/details');
        },

        toAdd: function(type, locale) {
            this.sandbox.emit('sulu.router.navigate', 'articles/' + (locale || this.options.locale) + '/add' + (this.options.config.typeNames.length > 1 ? (':' + type) : ''));
        },

        toList: function(locale) {
            if (this.options.config.typeNames.length === 1 || !this.options.type) {
                this.sandbox.emit('sulu.router.navigate', 'articles/' + (locale || this.options.locale));
            } else {
                this.sandbox.emit('sulu.router.navigate', 'articles:' + (this.options.type) + '/' + (locale || this.options.locale));
            }
        },

        deleteItems: function(ids) {
            ArticleManager.remove(ids, this.options.locale).then(function() {
                _.each(ids, function(id) {
                    this.sandbox.emit('husky.datagrid.articles.record.remove', id);
                }.bind(this));
            }.bind(this));
        },

        typeChange: function(item) {
            var url = this.templates.route({type: item.key, locale: this.options.locale});
            // Save the tab key. Can be removed when issue #72 is solved:
            // https://github.com/sulu/SuluArticleBundle/issues/72
            this.options.type = item.key;

            this.sandbox.emit('husky.datagrid.articles.url.update', {page: 1, type: item.key});
            this.sandbox.emit('sulu.router.navigate', url, false, false);

            this.storage.set('type', item.key);
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
                this.sandbox.emit('husky.toolbar.articles.item.mark', this.getFilterFromStorage().filterKey);
            }.bind(this));
        },

        /**
         * Generates list toolbar buttons.
         *
         * @param {String} title
         */
        retrieveListToolbarTemplate: function(title) {
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
                filter: {
                    options: {
                        icon: 'filter',
                        group: 2,
                        title: title,
                        showTitle: true,
                        dropdownOptions: {
                            idAttribute: 'id',
                            markSelected: true,
                            changeButton: false
                        },
                        dropdownItems: [
                            {
                                id: 'all',
                                title: this.translations.filterAll,
                                callback: function() {
                                    this.applyFilterToList.call(
                                        this,
                                        'all',
                                        null
                                    );
                                }.bind(this)
                            },
                            {
                                id: 'me',
                                title: this.translations.filterMe,
                                callback: function() {
                                    this.applyFilterToList.call(
                                        this,
                                        'me',
                                        this.sandbox.sulu.user.contact
                                    );
                                }.bind(this)
                            },
                            {
                                id: 'filterBy',
                                title: this.translations.filterBy + '...',
                                callback: this.openContactSelectionOverlay.bind(this)
                            },
                            {
                                divider: true
                            },
                            {
                                id: 'filterByCategory',
                                title: this.translations.filterByCategory,
                                callback: this.openCategorySelectionOverlay.bind(this)
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
                        contact: this.getFilterFromStorage().contact
                    },
                    selectCallback: function(data) {
                        this.applyFilterToList.call(
                            this,
                            'filterBy',
                            data.contactItem
                        );
                    }.bind(this)
                }
            }]);
        },

        /**
         * Opens contact selection overlay.
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
                        category: this.getFilterFromStorage().category
                    },
                    selectCallback: function(data) {
                        this.applyFilterToList.call(
                            this,
                            'category',
                            null,
                            data.categoryItem
                        );
                    }.bind(this)
                }
            }]);
        },

        /**
         * Emits the url update event for the list, changes the title of the filter button
         * and saves the selected contact id in the storage.
         *
         * @param {string} filterKey
         * @param {Object} contact
         * @param {Object} category
         */
        applyFilterToList: function(filterKey, contact, category) {
            this.storage.set('filter', {
                contact: contact,
                category: category,
                filterKey: filterKey
            });

            var update = {
                contactId: contact ? contact.id : null,
                categoryId: category ? category.id : null
            };

            this.sandbox.emit('husky.datagrid.articles.url.update', update);
            this.sandbox.emit('husky.toolbar.articles.button.set', 'filter', {
                title: this.getFilterTitle(filterKey, contact, category)
            });
        },

        /**
         * Retrieves the contact filter from the storage.
         *
         * @returns {Object}
         */
        getFilterFromStorage: function() {
            return this.storage.getWithDefault('filter', {filterKey: 'all', contact: null, category: null});
        },

        /**
         * Returns the title for the contact filter button.
         *
         * @param {String} filterKey
         * @param {Object} contact
         * @param {Object} category
         * @return {String}
         */
        getFilterTitle: function(filterKey, contact, category) {
            var title = '';

            switch(filterKey) {
                case 'all':
                    title = this.translations.filterAll;
                    break;
                case 'filterBy':
                    title = this.translations.filterBy + ' ' + contact.firstName + ' ' + contact.lastName;
                    break;
                case 'me':
                    title = this.translations.filterMe;
                    break;
                case 'category':
                    title = this.translations.filterByCategory + ' ' + category.name;
                    break;
            }

            return title;
        }
    };
});
