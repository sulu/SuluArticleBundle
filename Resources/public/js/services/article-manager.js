/*
 * This file is part of the Sulu CMS.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

define(['jquery', 'services/husky/util'], function($, Util) {
    'use strict';

    var baseUrl = '/admin/api/articles';

    return {
        save: function(data, locale, action) {
            var method = 'POST', url = baseUrl, requestParameters = [];

            if (!!data.id) {
                method = 'PUT';
                url += '/' + data.id;
            }

            requestParameters.push('locale=' + locale);

            if (!!action) {
                requestParameters.push('action=' + action);
            }

            return Util.save(
                url + '?' + requestParameters.join('&'),
                method,
                data
            );
        },

        delete: function(id) {
            return Util.save(
                [baseUrl, '/', id].join(''),
                'DELETE'
            );
        },

        unpublish: function(id, locale) {
            return Util.save(
                [baseUrl, '/', id, '?action=unpublish&locale=' + locale].join(''),
                'POST'
            );
        },

        removeDraft: function(id, locale) {
            return Util.save(
                [baseUrl, '/', id, '?action=remove-draft&locale=' + locale].join(''),
                'POST'
            );
        }
    }
});
