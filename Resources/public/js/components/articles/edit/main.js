/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

define([
    'jquery',
    'underscore',
    'sulusecurity/services/user-manager',
    'services/sulupreview/preview'
], function($, _, UserManager, Preview) {

    'use strict';

    return {

        defaults: {
            options: {
                config: {}
            },

            templates: {
                url: '/admin/api/articles<% if (!!id) { %>/<%= id %><% } %>?locale=<%= locale %>'
            },

            translations: {
                headline: 'sulu_article.edit.title',
                draftLabel: 'sulu-document-manager.draft-label'
            }
        },

        layout: function(){
            return {
                navigation: {
                    collapsed: true
                },
                content: {
                    shrinkable: !!this.options.id
                },
                sidebar: (!!this.options.id) ? 'max' : false
            };
        },

        header: function() {
            var buttons = {
                save: {
                    parent: 'saveWithDraft'
                },
                template: {
                    options: {
                        dropdownOptions: {
                            url: '/admin/articles/templates?type=' + (this.options.type || this.data.type),
                            callback: function(item) {
                                this.template = item.template;
                                this.sandbox.emit('sulu.tab.template-change', item);
                            }.bind(this)
                        }
                    }
                }
            };

            if (!!this.options.id) {
                buttons.delete = {};
            }

            return {
                tabs: {
                    url: '/admin/content-navigations?alias=article',
                    options: {
                        data: function() {
                            return this.sandbox.util.deepCopy(this.data);
                        }.bind(this),
                        url: function() {
                            return this.templates.url({id: this.options.id, locale: this.options.locale});
                        }.bind(this),
                        config: this.options.config,
                        preview: this.preview
                    },
                    componentOptions: {
                        values: this.data
                    }
                },

                toolbar: {
                    buttons: buttons
                }
            };
        },

        initialize: function() {
            this.bindCustomEvents();
            this.showDraftLabel();
        },

        bindCustomEvents: function() {
            this.sandbox.on('sulu.header.back', this.toList.bind(this));
            this.sandbox.on('sulu.tab.dirty', this.setHeaderBar.bind(this));
            this.sandbox.on('sulu.toolbar.save', this.save.bind(this));
            this.sandbox.on('sulu.toolbar.delete', this.deleteItem.bind(this));
            this.sandbox.on('sulu.tab.data-changed', this.setData.bind(this));
            this.sandbox.on('sulu.articles.save', this.saveArticle.bind(this));
            this.sandbox.on('sulu.tab.saved', this.showDraftLabel.bind(this));

            this.sandbox.on('sulu.header.language-changed', function(item) {
                this.sandbox.sulu.saveUserSetting(this.options.config.settingsKey, item.id);
                this.toEdit(item.id);
            }.bind(this));
        },

        deleteItem: function() {
            this.sandbox.util.save('/admin/api/articles/' + this.options.id, 'DELETE').then(function() {
                this.toList();
            }.bind(this));
        },

        toEdit: function(locale, id) {
            this.sandbox.emit('sulu.router.navigate', 'articles/' + (locale || this.options.locale) + '/edit:' + (id || this.options.id) + '/details', true, true);
        },

        toList: function() {
            if (this.options.config.typeNames.length === 1) {
                this.sandbox.emit('sulu.router.navigate', 'articles/' + this.options.locale);
            } else {
                this.sandbox.emit('sulu.router.navigate', 'articles:' + (this.options.type || this.data.type) + '/' + this.options.locale);
            }
        },

        toAdd: function() {
            if (this.options.config.typeNames.length === 1) {
                this.sandbox.emit('sulu.router.navigate', 'articles/' + this.options.locale + '/add', true, true);
            } else {
                this.sandbox.emit('sulu.router.navigate', 'articles/' + this.options.locale + '/add:' + (this.options.type || this.data.type), true, true);
            }
        },

        save: function(action) {
            this.loadingSave();

            this.saveTab(action).then(function(data) {
                this.afterSave(action, data);
            }.bind(this));
        },

        setData: function(data) {
            this.data = data;
        },

        saveTab: function(action) {
            var promise = $.Deferred();

            this.sandbox.once('sulu.tab.saved', function(savedData) {
                this.setData(savedData);

                promise.resolve(savedData);
            }.bind(this));

            this.sandbox.emit('sulu.tab.save', action);

            return promise;
        },

        saveArticle: function(data, action) {
            return this.sandbox.util.save(this.getUrl(action), !this.data.id ? 'POST' : 'PUT', data).then(function(data) {
                this.data = data;
                this.sandbox.emit('sulu.tab.saved', data);
            }.bind(this));
        },

        setHeaderBar: function(saved) {
            var saveDraft = !saved,
                savePublish = !saved,
                publish = !!saved && !this.data.publishedState;

            this.setSaveToolbarItems.call(this, 'saveDraft', saveDraft);
            this.setSaveToolbarItems.call(this, 'savePublish', savePublish);
            this.setSaveToolbarItems.call(this, 'publish', publish);
            this.setSaveToolbarItems.call(this, 'save', (!!saveDraft || !!savePublish || !!publish));

            this.saved = saved;
        },

        setSaveToolbarItems: function(item, value) {
            this.sandbox.emit('sulu.header.toolbar.item.' + (!!value ? 'enable' : 'disable'), item, false);
        },

        loadingSave: function() {
            this.sandbox.emit('sulu.header.toolbar.item.loading', 'save');
        },

        afterSave: function(action, data) {
            this.setHeaderBar(true);
            this.sandbox.emit('sulu.header.saved', data);

            if (action === 'back') {
                this.toList();
            } else if (action === 'new') {
                this.toAdd();
            } else if (!this.options.id) {
                this.toEdit(this.options.locale, data.id);
            }
        },

        showDraftLabel: function() {
            this.sandbox.emit('sulu.header.tabs.label.hide');

            if (!this.data.id || !!this.data.publishedState) {
                return;
            }

            this.setHeaderBar(true);

            UserManager.find(this.data.changer).then(function(response) {
                this.sandbox.emit(
                    'sulu.header.tabs.label.show',
                    this.sandbox.util.sprintf(
                        this.sandbox.translate(this.translations.draftLabel),
                        {
                            changed: this.sandbox.date.format(this.data.changed, true),
                            user: response.username
                        }
                    )
                );
            }.bind(this));
        },

        getUrl: function(action) {
            var url = _.template(this.defaults.templates.url, {
                id: this.options.id,
                locale: this.options.locale
            });

            if (action) {
                url += '&action=' + action;
            }

            return url;
        },

        loadComponentData: function() {
            var promise = $.Deferred();

            if (!this.options.id) {
                promise.resolve({});

                return promise;
            }

            this.sandbox.util.load(this.getUrl()).done(function(data) {
                this.preview = Preview.initialize({});
                this.preview.start(
                    'Sulu\\Bundle\\ArticleBundle\\Document\\ArticleDocument',
                    this.options.id,
                    this.options.locale,
                    data
                );

                promise.resolve(data);
            }.bind(this));

            return promise;
        },

        destroy: function() {
            if (!!this.preview) {
                Preview.destroy(this.preview);
            }
        }
    };
});
