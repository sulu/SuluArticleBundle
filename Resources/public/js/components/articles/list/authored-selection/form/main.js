/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

define(['jquery', 'text!./form.html'], function($, formTemplate) {

    'use strict';

    var defaults = {
        options: {
            data: {from: null, to: null},
        },
        translations: {
            from: 'sulu_article.authored-selection-overlay.from',
            to: 'sulu_article.authored-selection-overlay.to'
        },
        templates: {
            skeleton: formTemplate
        }
    };

    return {

        defaults: defaults,

        initialize: function() {
            this.$container = $(this.templates.skeleton({translations: this.translations}));
            this.$el.append(this.$container);

            this.data = this.options.data;
            this.sandbox.form.create(this.$container).initialized.then(function() {
                this.sandbox.form.setData(this.$container, this.options.data).then(function() {
                    this.sandbox.start(this.$container);
                }.bind(this));
            }.bind(this));

            this.sandbox.on('sulu_article.authored-selection.form.get', this.getEvent.bind(this));
        },

        /**
         * OK callback of the overlay.
         */
        getEvent: function() {
            if (!this.sandbox.form.validate(this.$container)) {
                return;
            }

            this.options.selectCallback(this.sandbox.form.getData(this.$container));
        }
    };
});
