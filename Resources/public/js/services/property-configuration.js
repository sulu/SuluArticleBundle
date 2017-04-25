/*
 * This file is part of the Sulu CMS.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

define(['jquery', 'underscore'], function($, _) {

    'use strict';

    var denormalizeTag = function(property, tag, tags) {
        if (!tags[tag.name]) {
            tags[tag.name] = {
                properties: {},
                highestProperty: property,
                highestPriority: tag.priority,
                lowestProperty: property,
                lowestPriority: tag.priority
            };
            tags[tag.name].properties[tag.priority] = [property];

            return;
        }

        if (!tags[tag.name].properties[tag.priority]) {
            tags[tag.name].properties[tag.priority] = [property];
        } else {
            tags[tag.name].properties[tag.priority].push(property);
        }

        // replace highest if priority is higher
        if (tags[tag.name].highestPriority < tag.priority) {
            tags[tag.name].highestProperty = property;
            tags[tag.name].highestPriority = tag.priority;
        }

        // replace lowest if priority is lower
        if (tags[tag.name].lowestPriority > tag.priority) {
            tags[tag.name].lowestProperty = property;
            tags[tag.name].lowestPriority = tag.priority;
        }
    };

    return {
        generate: function($form) {
            var $items = $form.find('*[data-property]');

            if ($items.length === 0) {
                return {};
            }

            var propertyConfiguration = {
                tags: {}
            };

            $items.each(function() {
                var $this = $(this),
                    property = $this.data('property');

                property.$el = $this;

                // remove property from dom
                $this.data('property', null);
                $this.removeAttr('data-property');

                propertyConfiguration[property.name] = property;

                _.each(property.tags, function(tag) {
                    denormalizeTag(property, tag, propertyConfiguration.tags);
                });

                return propertyConfiguration;
            });

            return propertyConfiguration;
        }
    };
});
