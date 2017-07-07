/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

define(['jquery', 'text!/admin/api/tags/fields'], function($, fieldsResponse) {

    'use strict';

    var defaults = {
        options: {
            data: {
                tag: null
            },
            selectCallback: function(data) {
            },
            matchings: JSON.parse(fieldsResponse)
        },
        translations: {
            title: 'sulu_article.tag-selection-overlay.title'
        },
        templates: {
            skeleton: [
                '<div class="grid">',
                '   <div class="grid-row search-row">',
                '       <div class="grid-col-8"/>',
                '       <div class="grid-col-4 tag-selection-search"/>',
                '   </div>',
                '   <div class="grid-row">',
                '       <div class="grid-col-12 tag-selection-list"/>',
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
                    instanceName: 'tag-selection',
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

            // start search and datagrid
            this.sandbox.once('husky.overlay.tag-selection.opened', function() {
                this.sandbox.start([
                    {
                        name: 'search@husky',
                        options: {
                            el: $overlayContainer.find('.tag-selection-search'),
                            appearance: 'white small',
                            instanceName: 'tag-selection-search'
                        }
                    },
                    {
                        name: 'datagrid@husky',
                        options: {
                            el: $overlayContainer.find('.tag-selection-list'),
                            instanceName: 'tag-selection',
                            url: '/admin/api/tags?flat=true',
                            resultKey: 'tags',
                            sortable: false,
                            selectedCounter: false,
                            searchInstanceName: 'tag-selection-search',
                            searchFields: ['name'],
                            preselected: !!this.options.data.tag ? [this.options.data.tag.id] : [],
                            paginationOptions: {
                                dropdown: {
                                    limit: 20
                                }
                            },
                            viewOptions: {
                                table: {
                                    selectItem: {
                                        type: 'radio'
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
            this.sandbox.emit('husky.datagrid.tag-selection.items.get-selected', function(ids, items) {
                if (items.length > 0) {
                    this.data.tagId = ids[0];
                    this.data.tagItem = items[0];
                }

                this.options.selectCallback(this.data);
                this.sandbox.stop();
            }.bind(this), true);
        }
    };
});
