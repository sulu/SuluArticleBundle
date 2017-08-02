/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

define(['jquery', 'text!./form.html', function($, formTemplate) {

    'use strict';

    var defaults = {
        options: {
            data: {from: null, to: null},
            selectCallback: function(data) {
            }
        },
        translations: {
            title: 'sulu_article.authored',
            from: 'sulu_article.authored-selection-overlay.from',
            to: 'sulu_article.authored-selection-overlay.to',
            reset: 'smart-content.choose-data-source.reset'
        },
        templates: {
            skeleton: formTemplate
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
                            okCallback: this.okCallbackOverlay.bind(this),
                            buttons: [
                                {
                                    type: 'cancel',
                                    align: 'left'
                                },
                                {
                                    classes: 'just-text',
                                    text: this.translations.reset,
                                    align: 'center',
                                    callback: function() {
                                        this.options.selectCallback({from: null, to: null});
                                        this.sandbox.stop();
                                    }.bind(this)
                                },
                                {
                                    type: 'ok',
                                    align: 'right'
                                }
                            ]
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
