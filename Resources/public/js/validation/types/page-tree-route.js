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

    var dataChangedHandler = function(data, $el) {
        App.emit('sulu.preview.update', $el, data);
        App.emit('sulu.content.changed');
    };

    return function($el, options) {
        var defaults = {},

            subType = {
                initializeSub: function() {
                    var dataChangedEvent = 'sulu.page-tree-route.' + options.instanceName + '.data-changed';

                    App.off(dataChangedEvent, dataChangedHandler);
                    App.on(dataChangedEvent, dataChangedHandler);
                },

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
