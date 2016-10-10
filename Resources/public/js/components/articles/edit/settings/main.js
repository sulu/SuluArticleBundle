/*
 * This file is part of the Sulu CMS.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

define([
    'underscore',
    'jquery',
    'sulusecurity/components/users/models/user',
    'text!./form.html'
], function(_, $, User, form) {

    'use strict';

    var defaults = {
        templates: {
            form: form
        },
        translations: {
            authored: 'sulu_article.authored',
            authors: 'sulu_article.authors',
            changelog: 'sulu.content.form.settings.changelog',
            changed: 'sulu.content.form.settings.changelog.changed',
            created: 'sulu.content.form.settings.changelog.created',
            userNotFound: 'sulu.content.form.settings.changelog.user-not-found'
        }
    };

    return {

        type: 'form-tab',

        defaults: defaults,

        /**
         * This method function has to be overwritten by the implementation to convert the data from "options.data".
         *
         * @param {object} data
         */
        parseData: function(data) {
            return {
                authored: data.authored,
                authors: _.map(data.authors, function(item) {
                    return 'c' + item;
                }),
                creator: data.creator,
                changer: data.changer,
                created: data.created,
                changed: data.changed
            };
        },

        /**
         * This method function can be overwritten by the implementation to initialize the component.
         *
         * For best-practice the default implementation should be used.
         */
        tabInitialize: function() {
            this.sandbox.emit('sulu.tab.initialize', this.name);

            this.sandbox.on('sulu.tab.saved', this.saved.bind(this));
        },

        /**
         * Submit form data.
         *
         * @param {String} action
         */
        submit: function(action) {
            if (!this.sandbox.form.validate(this.formId)) {
                return;
            }

            var data = this.sandbox.form.getData(this.formId);
            _.each(data, function(value, key) {
                this.data[key] = value;
            }.bind(this));

            this.save(this.data, action);
        },

        /**
         * This method function has to be overwritten by the implementation to save the data.
         *
         * @param {object} data
         * @param {object} action
         */
        save: function(data, action) {
            data.authors = _.map(data.authors, function(item) {
                return item.substr(1);
            });

            this.sandbox.emit('sulu.article.save', data, action);
        },

        /**
         * This method function can be overwritten by the implementation to process the data which was returned
         * by the rest-api.
         *
         * For best-practice the default implementation should be used.
         *
         * @param {object} data
         */
        saved: function(data) {
            this.data = this.parseData(data);
        },

        /**
         * This method function has to be overwritten by the implementation to generate the form-template.
         */
        getTemplate: function() {
            return this.templates.form({translations: this.translations})
        },

        /**
         * This method function has to be overwritten by the implementation. It should return the id for the form.
         */
        getFormId: function() {
            return '#settings-form';
        },

        listenForChange: function() {
            this.sandbox.dom.on(this.formId, 'change keyup', this.setDirty.bind(this));
            this.sandbox.on('sulu.content.changed', this.setDirty.bind(this));
            this.sandbox.on('husky.ckeditor.changed', this.setDirty.bind(this));

            this.updateChangelog(this.data);
        },

        /**
         * Update changelog.
         *
         * @param data
         */
        updateChangelog: function(data) {
            var setCreator = function(fullName) {
                    this.sandbox.dom.text('#created .name', fullName);
                    creatorDef.resolve();
                },
                setChanger = function(fullName) {
                    this.sandbox.dom.text('#changed .name', fullName);
                    changerDef.resolve();
                },
                creatorDef = $.Deferred(),
                changerDef = $.Deferred();

            if (data.creator === data.changer) {
                this.loadUser(data.creator).done(function(fullName) {
                    setChanger.call(this, fullName);
                    setCreator.call(this, fullName);
                }.bind(this)).fail(function() {
                    setChanger.call(this, this.translations.userNotFound);
                    setCreator.call(this, this.translations.userNotFound);
                }.bind(this));
            } else {
                this.loadUser(data.creator).done(function(fullName) {
                    setCreator.call(this, fullName);
                }.bind(this)).fail(function() {
                    setCreator.call(this, this.translations.userNotFound);
                }.bind(this));
                this.loadUser(data.changer).done(function(fullName) {
                    setChanger.call(this, fullName);
                }.bind(this)).fail(function() {
                    setChanger.call(this, this.translations.userNotFound);
                }.bind(this));
            }

            this.sandbox.dom.text('#created .date', this.sandbox.date.format(data.created, true));
            this.sandbox.dom.text('#changed .date', this.sandbox.date.format(data.changed, true));

            this.sandbox.data.when([creatorDef, changerDef]).then(function() {
                this.sandbox.dom.show('#changelog-container');
            }.bind(this));
        },

        loadUser: function(id) {
            var deferred = $.Deferred(),
                user = new User({id: id});

            user.fetch({
                global: false,

                success: function(model) {
                    deferred.resolve(model.get('fullName'))
                }.bind(this),

                error: function() {
                    deferred.reject();
                }.bind(this)
            });

            return deferred;
        }
    };
});
