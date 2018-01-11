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
            }
        },
        translations: {
            title: 'sulu_article.tag-selection-overlay.title'
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
                    instanceName: 'tag-selection',
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

            // start search and datagrid
            this.sandbox.once('husky.overlay.tag-selection.opened', function() {
                this.sandbox.start([{
                    name: 'articles/list/tag-selection/form@suluarticle',
                    options: {
                        el: $componentContainer,
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
            this.sandbox.emit('sulu_article.tag-selection.form.get');
        }
    };
});
