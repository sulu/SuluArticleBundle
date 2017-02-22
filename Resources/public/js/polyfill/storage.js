/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

/**
 * Polyfill for storage service introduced in 1.5.
 */
define(function() {

    'use strict';

    function Storage() {
    }

    Storage.prototype.set = function(key, value) {
    };

    Storage.prototype.remove = function(key, value) {
    };

    Storage.prototype.has = function(key) {
        return false;
    };

    Storage.prototype.get = function(key) {
        throw 'Value for key "' + key + '" does not exist';
    };

    Storage.prototype.getWithDefault = function(key, defaultValue) {
        return defaultValue;
    };

    function StorageService() {
    }

    StorageService.prototype.get = function(type, instanceName) {
        return new Storage();
    };

    return new StorageService();
});
