/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

define(['jquery', 'text!/admin/api/contacts/fields', 'text!./form.html'], function($, fieldsResponse, formTemplate) {

    'use strict';

    var defaults = {
        options: {
            data: {
                contact: null
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
                        el: $container.find('.contact-selection-search'),
                        appearance: 'white small',
                        instanceName: 'contact-selection-search'
                    }
                },
                {
                    name: 'datagrid@husky',
                    options: {
                        el: $container.find('.contact-selection-list'),
                        instanceName: 'contact-selection',
                        url: '/admin/api/contacts?flat=true',
                        resultKey: 'contacts',
                        sortable: false,
                        selectedCounter: false,
                        searchInstanceName: 'contact-selection-search',
                        searchFields: ['fullName', 'mainEmail'],
                        preselected: !!this.options.data.contact ? [this.options.data.contact.id] : [],
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

            this.sandbox.on('sulu_article.contact-selection.form.get', this.getEvent.bind(this));
        },

        /**
         * Callback for get-event.
         */
        getEvent: function() {
            this.sandbox.emit('husky.datagrid.contact-selection.items.get-selected', function(ids, items) {
                var data = this.options.data;
                if (items.length > 0) {
                    data.contactId = ids[0];
                    data.contactItem = items[0];
                }

                this.options.selectCallback(data);
            }.bind(this), true);
        }
    };
});
