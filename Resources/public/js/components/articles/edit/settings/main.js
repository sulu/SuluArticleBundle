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
    'services/suluarticle/article-manager',
    'text!/admin/articles/template/settings.html'
], function(_, $, User, ArticleManager, form) {

    'use strict';

    var defaults = {
        templates: {
            form: form
        },
        translations: {
            authored: 'sulu_article.authored',
            authors: 'sulu_article.authors',
            changelog: 'sulu_article.form.settings.changelog',
            changed: 'sulu_article.form.settings.changelog.changed',
            changedOnly: 'sulu_article.form.settings.changelog.changed-only',
            created: 'sulu_article.form.settings.changelog.created',
            createdOnly: 'sulu_article.form.settings.changelog.created-only'
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
                id: data.id,
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

            ArticleManager.save(data, data.id, this.options.locale, action).then(function(response) {
                this.sandbox.emit('sulu.tab.saved', response.id, response);
            }.bind(this)).fail(function(xhr) {
                this.sandbox.emit('sulu.article.error', xhr.status, data);
            }.bind(this));
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
         * Sets text for created.
         *
         * @param {String} fullName
         * @param {Object} time
         */
        setCreationChangelog: function(fullName, time) {
            var creationText, formattedTime = this.sandbox.date.format(time, true);

            if (!!fullName) {
                creationText = this.sandbox.util.sprintf(
                    this.translations.created,
                    {
                        creator: fullName,
                        created: formattedTime
                    }
                );
            } else {
                creationText = this.sandbox.util.sprintf(
                    this.translations.createdOnly,
                    {
                        created: formattedTime
                    }
                )
            }

            this.sandbox.dom.text('#created', creationText);
        },

        /**
         * Sets text for changed.
         *
         * @param {String} fullName
         * @param {Object} time
         */
        setChangeChangelog: function(fullName, time) {
            var changedText, formattedTime = this.sandbox.date.format(time, true);

            if (!!fullName) {
                changedText = this.sandbox.util.sprintf(
                    this.translations.changed,
                    {
                        changer: fullName,
                        changed: formattedTime
                    }
                );
            } else {
                changedText = this.sandbox.util.sprintf(
                    this.translations.changedOnly,
                    {
                        changed: formattedTime
                    }
                )
            }

            this.sandbox.dom.text('#changed', changedText);
        },

        /**
         * Update changelog.
         *
         * @param data
         */
        updateChangelog: function(data) {
            var creatorDef = $.Deferred();
            var changerDef = $.Deferred();

            if (data.creator === data.changer) {
                this.loadUser(data.creator).done(function(model) {
                    creatorDef.resolve(model.get('fullName'), data.created);
                    changerDef.resolve(model.get('fullName'), data.changed);
                }.bind(this)).fail(function() {
                    creatorDef.resolve(null, data.created);
                    changerDef.resolve(null, data.changed);
                }.bind(this));
            } else {
                // load creator
                this.loadUser(data.creator).done(function(model) {
                    creatorDef.resolve(model.get('fullName'), data.created);
                }.bind(this)).fail(function() {
                    creatorDef.resolve(null, data.created);
                }.bind(this));
                // load changer
                this.loadUser(data.changer).done(function(model) {
                    changerDef.resolve(model.get('fullName'), data.changed);
                }.bind(this)).fail(function() {
                    changerDef.resolve(null, data.changed);
                }.bind(this));
            }

            this.sandbox.data.when(creatorDef, changerDef).then(function(creation, change) {
                this.setCreationChangelog(creation[0], creation[1]);
                this.setChangeChangelog(change[0], change[1]);
                this.sandbox.dom.show('#changelog-container');
            }.bind(this));
        },

        /**
         * Loads user.
         *
         * @param {String} id
         *
         * @return {*}
         */
        loadUser: function(id) {
            var deferred = $.Deferred(),
                user = new User({id: id});

            user.fetch({
                global: false,

                success: function(model) {
                    deferred.resolve(model)
                }.bind(this),

                error: function() {
                    deferred.reject();
                }.bind(this)
            });

            return deferred;
        }
    };
});
