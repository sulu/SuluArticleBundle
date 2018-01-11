/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

define(['jquery', 'text!/admin/api/tags/fields', 'text!./form.html'], function($, fieldsResponse, formTemplate) {

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
        templates: {
            skeleton: formTemplate
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
                    name: 'search@husky',
                    options: {
                        el: $container.find('.tag-selection-search'),
                        appearance: 'white small',
                        instanceName: 'tag-selection-search'
                    }
                },
                {
                    name: 'datagrid@husky',
                    options: {
                        el: $container.find('.tag-selection-list'),
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

            this.sandbox.on('sulu_article.tag-selection.form.get', this.getEvent.bind(this));
        },

        /**
         * Callback for get-event.
         */
        getEvent: function() {
            this.sandbox.emit('husky.datagrid.tag-selection.items.get-selected', function(ids, items) {
                var data = this.options.data;
                if (items.length > 0) {
                    data.tagId = ids[0];
                    data.tagItem = items[0];
                }

                this.options.selectCallback(data);
            }.bind(this), true);
        }
    };
});
