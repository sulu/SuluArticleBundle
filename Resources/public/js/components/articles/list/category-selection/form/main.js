/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

define(['jquery', 'text!./form.html'], function($, formTemplate) {

    'use strict';

    var defaults = {
        options: {
            locale: null,
            data: {
                category: null
            },
            selectCallback: function(data) {
            },
            matchings: [
                {'name': 'name', 'content': 'Name'},
                {'name': 'id', 'disabled': true},
                {'name': 'children', 'disabled': true},
                {'name': 'parent', 'disabled': true}
            ]
        },
        templates: {
            skeleton: formTemplate
        },
        translations: {
            noItems: 'sulu.category.no-categories-available'
        }
    };

    return {

        defaults: defaults,

        initialize: function() {
            var $container = $(this.templates.skeleton({
                translations: this.translations
            }));
            this.$el.append($container);

            this.sandbox.start([
                {
                    name: 'datagrid@husky',
                    options: {
                        el: $container,
                        instanceName: 'category-selection',
                        url: '/admin/api/categories?locale=' + this.options.locale + '&flat=true&sortBy=name&sortOrder=asc',
                        resultKey: 'categories',
                        sortable: false,
                        selectedCounter: false,
                        preselected: !!this.options.data.category ? [this.options.data.category.id] : [],
                        paginationOptions: {
                            dropdown: {
                                limit: 20
                            }
                        },
                        childrenPropertyName: 'hasChildren',
                        viewOptions: {
                            table: {
                                cropContents: false,
                                noItemsText: this.translations.noItems,
                                showHead: false,
                                cssClass: 'white-box',
                                selectItem: {
                                    type: 'radio',
                                    inFirstCell: true
                                }
                            }
                        },
                        matchings: this.options.matchings
                    }
                }
            ]);

            this.sandbox.on('sulu_article.category-selection.form.get', this.getEvent.bind(this));
        },

        /**
         * Callback for get-event.
         */
        getEvent: function() {
            this.sandbox.emit('husky.datagrid.category-selection.items.get-selected', function(ids, items) {
                var data = this.options.data;
                if (items.length > 0) {
                    data.categoryId = ids[0];
                    data.categoryItem = items[0];
                }

                this.options.selectCallback(data);
            }.bind(this), true);
        }
    };
});
