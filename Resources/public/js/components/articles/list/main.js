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

            this.sandbox.sulu.initListToolbarAndList.call(this,
                'article',
                '/admin/api/articles/fields',
                {
                    el: this.$find('.list-toolbar-container'),
                    instanceName: 'articles',
                    template: this.retrieveListToolbarTemplate()
                },
                {
                    el: this.sandbox.dom.find('.datagrid-container'),
                    url: '/admin/api/articles?sortBy=authored&sortOrder=desc&locale=' + this.options.locale + (this.options.type ? ('&type=' + this.options.type) : ''),
                    storageName: this.options.storageName,
                    searchInstanceName: 'articles',
                    searchFields: ['title'],
                    resultKey: 'articles',
                    idKey: 'uuid',
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
            this.sandbox.util.save('/admin/api/articles?ids=' + ids.join(','), 'DELETE').then(function() {
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
        },

        /**
         * Generates list toolbar buttons.
         */
        retrieveListToolbarTemplate: function() {
            return this.sandbox.sulu.buttons.get({
                contactIdFilter: {
                    options: {
                        icon: 'filter',
                        group: 2,
                        title: this.translations.filterAll,
                        showTitle: true,
                        dropdownOptions: {
                            preSelected: 'all',
                            idAttribute: 'id',
                            markSelected: true,
                            changeButton: true,
                            callback: function(item) {
                                this.applyFilterToList.call(this, item);
                            }.bind(this)
                        },
                        dropdownItems: [
                            {
                                id: 'me',
                                title: this.translations.filterMe
                            },
                            {
                                id: 'all',
                                title: this.translations.filterAll
                            }
                        ]
                    }
                }
            });
        },

        /**
         * Emits the url update event for the list.
         *
         * @param item {Object}
         */
        applyFilterToList: function(item) {
            var contactId = null;

            if (!!item.id) {
                switch(item.id) {
                    case 'me':
                        contactId = this.sandbox.sulu.user.id;
                        break;
                    default:
                        contactId = null;
                        break;
                }
            }

            this.sandbox.emit('husky.datagrid.articles.url.update', {contactId: contactId});
        }
    };
});
