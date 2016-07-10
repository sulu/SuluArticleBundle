/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

define(['jquery', 'underscore'], function($, _) {

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
                headline: 'sulu_article.edit.title'
            }
        },

        header: function() {
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
                        config: this.options.config
                    },
                    componentOptions: {
                        values: this.data
                    }
                },

                toolbar: {
                    buttons: {
                        save: {
                            parent: 'saveWithOptions'
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
                    }
                }
            };
        },

        initialize: function() {
            this.saveState = 'disabled';

            this.bindCustomEvents();
        },

        bindCustomEvents: function() {
            this.sandbox.on('sulu.header.back', this.toList.bind(this));
            this.sandbox.on('sulu.tab.dirty', this.enableSave.bind(this));
            this.sandbox.on('sulu.toolbar.save', this.save.bind(this));
            this.sandbox.on('sulu.tab.data-changed', this.setData.bind(this));

            this.sandbox.on('sulu.header.language-changed', function(item) {
                this.sandbox.sulu.saveUserSetting(this.options.config.settingsKey, item.id);
                this.toEdit(item.id);
            }.bind(this));
        },

        toEdit: function(locale, id) {
            this.sandbox.emit('sulu.router.navigate', 'articles/' + (locale || this.options.locale) + '/edit:' + (id || this.options.id), true, true);
        },

        toList: function() {
            if (this.options.config.types.length === 1) {
                this.sandbox.emit('sulu.router.navigate', 'articles/' + this.options.locale);
            } else {
                this.sandbox.emit('sulu.router.navigate', 'articles:' + (this.options.type || this.data.type) + '/' + this.options.locale);
            }
        },

        toAdd: function() {
            if (this.options.config.types.length === 1) {
                this.sandbox.emit('sulu.router.navigate', 'articles/' + this.options.locale + '/add', true, true);
            } else {
                this.sandbox.emit('sulu.router.navigate', 'articles/' + this.options.locale + '/add:' + (this.options.type || this.data.type), true, true);
            }
        },

        save: function(action) {
            this.loadingSave();

            this.saveTab().then(function(data) {
                this.afterSave(action, data);
            }.bind(this));
        },

        setData: function(data) {
            this.data = data;
        },

        saveTab: function() {
            var promise = $.Deferred();

            this.sandbox.once('sulu.tab.saved', function(savedData) {
                this.setData(savedData);

                promise.resolve(savedData);
            }.bind(this));

            this.sandbox.emit('sulu.tab.save');

            return promise;
        },

        enableSave: function(force) {
            if (!force && this.saveState === 'loading') {
                return;
            }

            this.saveState = 'enabled';
            this.sandbox.emit('sulu.header.toolbar.item.enable', 'save', false);
        },

        disableSave: function(force) {
            if (!force && this.saveState === 'loading') {
                return;
            }

            this.saveState = 'disabled';
            this.sandbox.emit('sulu.header.toolbar.item.disable', 'save', true);
        },

        loadingSave: function() {
            this.saveState = 'loading';
            this.sandbox.emit('sulu.header.toolbar.item.loading', 'save');
        },

        afterSave: function(action, data) {
            this.disableSave(true);
            this.sandbox.emit('sulu.header.saved', data);

            if (action === 'back') {
                this.toList();
            } else if (action === 'new') {
                this.toAdd();
            } else if (!this.options.id) {
                this.toEdit(this.options.locale, data.id);
            }
        },

        loadComponentData: function() {
            var promise = $.Deferred();

            if (!this.options.id) {
                promise.resolve({});

                return promise;
            }

            this.sandbox.util.load(_.template(this.defaults.templates.url, {
                id: this.options.id,
                locale: this.options.locale
            })).done(function(data) {
                promise.resolve(data);
            });

            return promise;
        }
    };
});
