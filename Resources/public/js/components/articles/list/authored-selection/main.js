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
            data: {from: null, to: null},
            selectCallback: function(data) {
            }
        },
        translations: {
            title: 'sulu_article.authored-selection-overlay.title',
            from: 'sulu_article.authored-selection-overlay.from',
            to: 'sulu_article.authored-selection-overlay.to'
        },
        templates: {
            skeleton: [
                '<div class="grid">',
                '   <div class="grid-row">',
                '       <div class="grid-col-6 form-group">',
                '            <label for="authored-from"><%= translations.from %></label>',
                '           <div class="form-element"',
                '                data-aura-component="input@husky"',
                '                data-aura-skin="date"',
                '                data-aura-instance-name="from"',
                '                data-aura-inpu-id="authored-from"',
                '                data-type="husky-input"',
                '                data-form="true"',
                '                data-mapper-property="from"/>',
                '       </div>',
                '       <div class="grid-col-6 form-group">',
                '            <label for="authored-to"><%= translations.to %></label>',
                '           <div class="form-element"',
                '                data-aura-component="input@husky"',
                '                data-aura-skin="date"',
                '                data-aura-instance-name="to"',
                '                data-aura-inpu-id="authored-to"',
                '                data-type="husky-input"',
                '                data-form="true"',
                '                data-mapper-property="to"/>',
                '       </div>',
                '   </div>',
                '</div>'
            ].join('')
        }
    };

    return {

        defaults: defaults,

        initialize: function() {
            this.$overlayContainer = $('<div/>');
            this.$el.append(this.$overlayContainer);

            this.data = this.options.data;

            // start overlay
            this.sandbox.start([{
                name: 'overlay@husky',
                options: {
                    el: this.$overlayContainer,
                    instanceName: 'authored-selection',
                    openOnStart: true,
                    removeOnClose: true,
                    skin: 'medium',
                    slides: [
                        {
                            title: this.translations.title,
                            data: $(this.templates.skeleton({translations: this.translations})),
                            okCallback: this.okCallbackOverlay.bind(this)
                        }
                    ]
                }
            }]);

            // start search and datagrid
            this.sandbox.once('husky.overlay.authored-selection.opened', function() {
                this.sandbox.form.create(this.$overlayContainer).initialized.then(function() {
                    this.sandbox.form.setData(this.$overlayContainer, this.options.data).then(function() {
                        this.sandbox.start(this.$overlayContainer);
                    }.bind(this));
                }.bind(this));
            }.bind(this));
        },

        /**
         * OK callback of the overlay.
         */
        okCallbackOverlay: function() {
            if (!this.sandbox.form.validate(this.$overlayContainer)) {
                return;
            }

            this.options.selectCallback(this.sandbox.form.getData(this.$overlayContainer));
            this.sandbox.stop();
        }
    };
});
