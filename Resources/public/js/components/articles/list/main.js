/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

define(function() {

    'use strict';

    var defaults = {
        options: {
            config: {}
        },

        templates: {
            list: [
                '<div class="list-toolbar-container"></div>',
                '<div class="list-info"></div>',
                '<div class="datagrid-container"></div>',
                '<div class="dialog"></div>'
            ].join('')
        },

        translations: {
            headline: 'sulu_article.list.title'
        }
    };

    return {

        defaults: defaults,

        header: function() {
            return {
                title: this.translations.headline,
                underline: false,

                noBack: true,

                toolbar: {
                    buttons: {
                        add: {},
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
                    template: this.sandbox.sulu.buttons.get({
                        settings: {
                            options: {
                                dropdownItems: [
                                    {
                                        type: 'columnOptions'
                                    }
                                ]
                            }
                        }
                    })
                },
                {
                    el: this.sandbox.dom.find('.datagrid-container'),
                    url: '/admin/api/articles?locale=' + this.options.locale,
                    searchInstanceName: 'articles',
                    searchFields: ['title'],
                    resultKey: 'articles',
                    instanceName: 'articles',
                    actionCallback: function(id) {
                        this.toEdit(id);
                    }.bind(this),
                    viewOptions: {
                        table: {
                            actionIconColumn: 'title'
                        }
                    }
                }
            );
        },

        toEdit: function(id, locale) {
            this.sandbox.emit('sulu.router.navigate', 'articles/' + (locale || this.options.locale) + '/edit:' + id);
        },

        toAdd: function(locale) {
            this.sandbox.emit('sulu.router.navigate', 'articles/' + (locale || this.options.locale) + '/add');
        },

        toList: function(locale) {
            this.sandbox.emit('sulu.router.navigate', 'articles/' + (locale || this.options.locale));
        },

        deleteItems: function(ids) {
            for (var i = 0, length = ids.length; i < length; i++) {
                this.deleteItem(ids[i]);
            }
        },

        deleteItem: function(id) {
            this.sandbox.util.save('/admin/api/articles/' + id, 'DELETE').then(function() {
                this.sandbox.emit('husky.datagrid.news.record.remove', id);
            }.bind(this));
        },

        bindCustomEvents: function() {
            this.sandbox.on('sulu.toolbar.add', this.toAdd.bind(this));

            this.sandbox.on('husky.datagrid.news.number.selections', function(number) {
                var postfix = number > 0 ? 'enable' : 'disable';
                this.sandbox.emit('sulu.header.toolbar.item.' + postfix, 'deleteSelected', false);
            }.bind(this));

            this.sandbox.on('sulu.toolbar.delete', function() {
                this.sandbox.emit('husky.datagrid.news.items.get-selected', this.deleteItems.bind(this));
            }.bind(this));

            this.sandbox.on('sulu.header.language-changed', function(item) {
                this.sandbox.sulu.saveUserSetting(this.options.config.settingsKey, item.id);
                this.toList(item.id);
            }.bind(this));
        }
    };
});
