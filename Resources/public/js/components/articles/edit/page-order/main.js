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

    return {

        defaults: {
            options: {
                pages: []
            },
            translations: {
                orderPage: 'sulu_article.edit.order-page'
            }
        },

        initialize: function() {
            this.$container = $('<div/>');
            this.$componentContainer = $('<div/>');

            this.$el.append(this.$container);

            this.sandbox.start(
                [
                    {
                        name: 'overlay@husky',
                        options: {
                            el: this.$container,
                            instanceName: 'page-order',
                            openOnStart: true,
                            removeOnClose: true,
                            skin: 'medium',
                            slides: [
                                {
                                    title: this.translations.orderPage,
                                    data: this.$componentContainer,
                                    okCallback: function() {
                                    }
                                }
                            ]
                        }
                    }
                ]
            );

            this.sandbox.once('husky.overlay.page-order.opened', function() {
                this.sandbox.start([{
                    name: 'articles/edit/page-order/page-grid@suluarticle',
                    options: {
                        el: this.$componentContainer,
                        pages: this.options.pages
                    }
                }]);
            }.bind(this));
        }
    }
});
