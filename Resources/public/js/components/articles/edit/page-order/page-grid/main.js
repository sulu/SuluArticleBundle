/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

define(['jquery', 'text!./grid.html'], function($, gridTemplate) {

    'use strict';

    var compare = function(id, $a, $b) {
            var aValue = parseInt($a.find('input').val()),
                bValue = parseInt($b.find('input').val()),
                aId = $a.data('id'),
                bId = $b.data('id');

            if (aValue < bValue) {
                return -1;
            } else if (aValue > bValue || aId === id || bId === id) {
                return 1;
            }

            return 0;
        },

        highlight = function($item) {
            $item.addClass('highlight-animation');
            $item.one('animationend webkitAnimationEnd oanimationend MSAnimationEnd', function() {
                $item.removeClass('highlight-animation');
            });
        },

        focus = function($item) {
            $item.find('input').focus();
        };

    return {

        defaults: {
            options: {
                pages: []
            },
            templates: {
                grid: gridTemplate
            },
            translations: {
                title: 'public.title'
            }
        },

        initialize: function() {
            this.render();

            this.bindDomEvents();
        },

        render: function() {
            this.$el.append(this.templates.grid({translations: this.translations, pages: this.options.pages}));
        },

        bindDomEvents: function() {
            this.$el.find('input').on('change', function(e) {
                this.orderTable($(e.currentTarget).parents('tr'));
            }.bind(this));
        },

        orderTable: function($item) {
            var id = $item.data('id'),
                rows = this.$el.find('tbody tr').get();

            rows.sort(function(a, b) {
                return compare(id, $(a), $(b));
            });

            $.each(rows, function(index, row) {
                $(row).find('input').val(index + 1);

                this.$el.find('table').children('tbody').append(row);
            }.bind(this));

            highlight($item);
            focus($item);
        }
    }
});
