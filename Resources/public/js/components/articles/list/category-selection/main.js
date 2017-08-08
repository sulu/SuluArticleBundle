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
            locale: null,
            data: {
                category: null
            },
            selectCallback: function(data) {
            }
        },
        translations: {
            title: 'sulu_article.category-selection-overlay.title'
        }
    };

    return {

        defaults: defaults,

        initialize: function() {
            var $overlayContainer = $('<div/>');
            var $componentContainer = $('<div/>');
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
                            data: $componentContainer,
                            okCallback: this.okCallbackOverlay.bind(this)
                        }
                    ]
                }
            }]);

            this.sandbox.once('husky.overlay.category-selection.opened', function() {
                this.sandbox.start([{
                    name: 'articles/list/category-selection/form@suluarticle',
                    options: {
                        el: $componentContainer,
                        locale: this.options.locale,
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
            this.sandbox.emit('sulu_article.category-selection.form.get');
        }
    };
});
