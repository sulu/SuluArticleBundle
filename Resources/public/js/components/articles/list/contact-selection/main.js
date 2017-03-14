/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

define(['jquery', 'text!/admin/api/contacts/fields'], function($, fieldsResponse) {

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
        translations: {
            title: 'sulu_article.contact-selection-overlay.title'
        },
        templates: {
            skeleton: [
                '<div class="grid">',
                '   <div class="grid-row search-row">',
                '       <div class="grid-col-8"/>',
                '       <div class="grid-col-4 contact-selection-search"/>',
                '   </div>',
                '   <div class="grid-row">',
                '       <div class="grid-col-12 contact-selection-list"/>',
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
                    instanceName: 'contact-selection',
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
            this.sandbox.once('husky.overlay.contact-selection.opened', function() {
                this.sandbox.start([
                    {
                        name: 'search@husky',
                        options: {
                            el: '.contact-selection-search',
                            appearance: 'white small',
                            instanceName: 'contact-selection-search'
                        }
                    },
                    {
                        name: 'datagrid@husky',
                        options: {
                            el: '.contact-selection-list',
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
            }.bind(this));
        },

        /**
         * OK callback of the overlay.
         */
        okCallbackOverlay: function() {
            this.sandbox.emit('husky.datagrid.contact-selection.items.get-selected', function(ids, items) {
                if (items.length > 0) {
                    this.data.contactId = ids[0];
                    this.data.contactItem = items[0];
                }

                this.options.selectCallback(this.data);
                this.sandbox.stop();
            }.bind(this), true);
        }
    };
});
