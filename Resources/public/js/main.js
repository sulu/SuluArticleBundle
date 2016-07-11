/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

require.config({
    paths: {
        suluarticle: '../../suluarticle/js',
        suluarticlecss: '../../suluarticle/css'
    }
});

define(['underscore', 'config'], function(_, Config) {

    'use strict';

    return {

        name: 'Sulu Article Bundle',

        initialize: function(app) {
            app.components.addSource('suluarticle', '/bundles/suluarticle/js/components');

            // set config for this bundle
            var locales = Object.keys(Config.get('sulu-content').locales),
                config = {
                    defaultLocale: locales[0],
                    locales: locales,
                    languageChanger: _.map(locales, function(locale) {
                        return {id: locale, title: locale};
                    }),
                    settingsKey: 'articleLanguage',
                    types: Object.keys(Config.get('sulu_article.types'))
                };
            Config.set('sulu_article', config);

            /**
             * Returns current locale for articles.
             *
             * @returns {string}
             */
            var getLocale = function() {
                return app.sandbox.sulu.getUserSetting(config.settingsKey) || config.defaultLocale;
            };

            if (config.types.length === 1) {
                app.sandbox.mvc.routes.push({
                    route: 'articles',
                    callback: function() {
                        return app.sandbox.emit('sulu.router.navigate', 'articles/' + getLocale());
                    }
                });

                app.sandbox.mvc.routes.push({
                    route: 'articles/:locale',
                    callback: function(locale) {
                        return '<div data-aura-component="articles/list@suluarticle" data-aura-type="' + config.types[0] + '" data-aura-locale="' + locale + '" data-aura-config=\'' + JSON.stringify(config) + '\'/>';
                    }
                });

                app.sandbox.mvc.routes.push({
                    route: 'articles/:locale/add',
                    callback: function(locale, type) {
                        return '<div data-aura-component="articles/edit@suluarticle" data-aura-type="' + config.types[0] + '" data-aura-locale="' + locale + '" data-aura-config=\'' + JSON.stringify(config) + '\'/>';
                    }
                });
            } else {
                app.sandbox.mvc.routes.push({
                    route: 'articles',
                    callback: function() {
                        return app.sandbox.emit('sulu.router.navigate', 'articles:' + config.types[0] + '/' + getLocale());
                    }
                });

                app.sandbox.mvc.routes.push({
                    route: 'articles::type',
                    callback: function(type) {
                        return app.sandbox.emit('sulu.router.navigate', 'articles:' + type + '/' + getLocale());
                    }
                });

                app.sandbox.mvc.routes.push({
                    route: 'articles::type/:locale',
                    callback: function(type, locale) {
                        return '<div data-aura-component="articles/list@suluarticle" data-aura-locale="' + locale + '" data-aura-config=\'' + JSON.stringify(config) + '\' data-aura-type="' + type + '"/>';
                    }
                });

                app.sandbox.mvc.routes.push({
                    route: 'articles/:locale/add::type',
                    callback: function(locale, type) {
                        return '<div data-aura-component="articles/edit@suluarticle" data-aura-locale="' + locale + '" data-aura-config=\'' + JSON.stringify(config) + '\' data-aura-type="' + type + '"/>';
                    }
                });
            }

            app.sandbox.mvc.routes.push({
                route: 'articles/:locale/edit::id',
                callback: function(locale, id) {
                    return '<div data-aura-component="articles/edit@suluarticle" data-aura-locale="' + locale + '" data-aura-id="' + id + '" data-aura-config=\'' + JSON.stringify(config) + '\'/>';
                }
            });
        }
    }
});
