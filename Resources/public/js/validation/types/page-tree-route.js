/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

define(['type/default'], function(Default) {

    'use strict';

    return function($el, options) {
        var defaults = {},

            subType = {
                setValue: function(value) {
                    App.dom.data($el, 'value', value);
                },

                getValue: function() {
                    return App.dom.data($el, 'value');
                },

                needsValidation: function() {
                    return false;
                },

                validate: function() {
                    return true;
                }
            };

        return new Default($el, defaults, options, 'pageTreeRoute', subType);
    };
});
