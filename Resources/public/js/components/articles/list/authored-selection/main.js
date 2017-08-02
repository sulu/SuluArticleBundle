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
            title: 'sulu_article.authored',
            reset: 'smart-content.choose-data-source.reset'
        },
    };

    return {

        defaults: defaults,

        initialize: function() {
            this.$overlayContainer = $('<div/>');
            this.$componentContainer = $('<div/>');
            this.$el.append(this.$overlayContainer);

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
                            data: this.$componentContainer,
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
                this.sandbox.start([{
                    name: 'articles/list/authored-selection/form@suluarticle',
                    options: {
                        el: this.$componentContainer,
                        data: this.options.data,
                        selectCallback: function(data) {
                            this.options.selectCallback(data);
                            this.sandbox.stop();
                        }.bind(this)
                    }
                }]);
            }.bind(this));
        },

        /**
         * OK callback of the overlay.
         */
        okCallbackOverlay: function() {
            this.sandbox.emit('sulu_article.authored-selection.form.get');
        }
    };
});
