/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

define(['jquery'], function($) {

    'use strict';

    var defaults = {
        options: {
            data: {
                contact: null
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
        translations: {
            title: 'sulu_article.category-selection-overlay.title'
        },
        templates: {
            skeleton: [
                '<div class="grid">',
                '   <div class="grid-row">',
                '       <div class="grid-col-12 category-selection-list"/>',
                '   </div>',
                '</div>'
            ].join('')
        }
    };

    return {

        defaults: defaults,

        initialize: function() {
            var $overlayContainer = $('<div/>');
            this.$el.append($overlayContainer);

            this.data = this.options.data;

            // start overlay
            this.sandbox.start([{
                name: 'overlay@husky',
                options: {
                    el: $overlayContainer,
                    instanceName: 'category-selection',
                    openOnStart: true,
                    removeOnClose: true,
                    skin: 'medium',
                    slides: [
                        {
                            title: this.translations.title,
                            data: $(this.templates.skeleton({
                                translations: this.translations
                            })),
                            okCallback: this.okCallbackOverlay.bind(this)
                        }
                    ]
                }
            }]);

            this.sandbox.once('husky.overlay.category-selection.opened', function() {
                this.sandbox.start([
                    {
                        name: 'datagrid@husky',
                        options: {
                            el: $overlayContainer.find('.category-selection-list'),
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
                                    noItemsText: 'sulu.category.no-categories-available',
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
            }.bind(this));
        },

        /**
         * OK callback of the overlay.
         */
        okCallbackOverlay: function() {
            this.sandbox.emit('husky.datagrid.category-selection.items.get-selected', function(ids, items) {
                if (items.length > 0) {
                    this.data.categoryId = ids[0];
                    this.data.categoryItem = items[0];
                }

                this.options.selectCallback(this.data);
                this.sandbox.stop();
            }.bind(this), true);
        }
    };
});
