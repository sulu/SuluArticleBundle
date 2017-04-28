/*
 * This file is part of the Sulu CMS.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

define(['text!./skeleton.html'], function(skeletonTemplate) {

    'use strict';

    var defaults = {
            options: {
                historyApi: null,
                historyResultKey: 'routes',
                historyPathKey: 'path'
            },
            templates: {
                skeleton: skeletonTemplate
            },
            translations: {
                showHistory: 'public.show-history'
            }
        },

        trimSlash = function(string) {
            return string.replace(/\/+$/g, '');
        },

        generatePath = function(pagePath, suffix) {
            return trimSlash(pagePath) + '/' + suffix;
        };

    return {
        defaults: defaults,

        initialize: function() {
            // FIXME this adds the required class to the label
            this.$el.closest('.form-group').find('label').addClass('required');

            this.bindCustomEvents();

            this.render();

            var data = this.getData(),
                page = data.page || {};

            this.$prefix.val(page.path || '');
            this.$suffix.val(data.suffix || '');
        },

        bindCustomEvents: function() {
        },

        render: function() {
            this.html(this.templates.skeleton({translations: this.translations, options: this.options}));

            this.$prefix = this.$el.find('.prefix');
            this.$suffix = this.$el.find('.suffix');
            this.$choose = this.$el.find('.choose');

            this.bindDomEvents();
        },

        bindDomEvents: function() {
            this.$suffix.on('change', function(event) {
                this.setSuffix($(event.currentTarget).val());
            }.bind(this));

            this.$choose.on('click', function() {
                this.pageSelectClicked();

                return false;
            }.bind(this));
        },

        pageSelectClicked: function() {
            var $container = $('<div/>'),
                data = this.getData();

            this.$el.append($container);

            this.sandbox.start(
                [
                    {
                        name: 'page-tree-route/page-select@suluarticle',
                        options: {
                            el: $container,
                            selected: (data.page || {}).uuid || null,
                            locale: this.options.locale,
                            selectCallback: this.setParentPage.bind(this)
                        }
                    }
                ]
            );
        },

        setParentPage: function(item) {
            var data = this.getData();
            data.page = {uuid: item.id, path: (item.url || '/')};

            data.path = null;
            if (!!data.suffix && data.suffix.length > 0) {
                data.path = generatePath(data.page.path, data.suffix);
            }

            this.setData(data);

            this.$prefix.val(data.page.path === '/' ? data.page.path : trimSlash(data.page.path)).trigger('change');
        },

        setSuffix: function(suffix) {
            var data = this.getData();
            data.suffix = suffix;

            data.path = null;
            if (!!data.page) {
                data.path = generatePath(data.page.path, data.suffix);
            }

            this.setData(data);
        },

        setData: function(data) {
            this.$el.data('value', data);
        },

        getData: function() {
            return this.$el.data('value') || {};
        }
    };
});
