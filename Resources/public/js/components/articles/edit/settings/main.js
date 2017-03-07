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
    'sulucontact/models/contact',
    'services/suluarticle/article-manager',
    'text!/admin/articles/template/settings.html'
], function(_, $, User, Contact, ArticleManager, form) {

    'use strict';

    var defaults = {
        templates: {
            form: form
        },
        translations: {
            author: 'sulu_article.author',
            authored: 'sulu_article.form.settings.changelog.authored',
            authoredOnly: 'sulu_article.form.settings.changelog.authored-only',
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

        authorFullname: null,

        /**
         * This method function has to be overwritten by the implementation to convert the data from "options.data".
         *
         * @param {object} data
         */
        parseData: function(data) {
            return {
                id: data.id,
                author: data.author,
                authored: data.authored,
                creator: data.creator,
                changer: data.changer,
                created: data.created,
                changed: data.changed
            };
        },

        rendered: function() {
            this.updateChangelog(this.data);
            this.bindDomEvents();
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
            ArticleManager.save(data, data.id, this.options.locale, action).then(function(response) {
                this.saved(data);
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
        },

        /**
         * Sets text for author.
         *
         * @param {String} fullName
         * @param {Object} date
         */
        setAuthorChangelog: function(fullName, date) {
            var authoredText, formattedDate = this.sandbox.date.format(date);

            fullName = fullName || this.authorFullname;

            if (!!fullName) {
                this.authorFullname = fullName;
                authoredText = this.sandbox.util.sprintf(
                    this.translations.authored,
                    {
                        author: fullName,
                        authored: formattedDate
                    }
                );
            } else {
                authoredText = this.sandbox.util.sprintf(
                    this.translations.authoredOnly,
                    {
                        authored: formattedDate
                    }
                )
            }

            this.sandbox.dom.text('#author', authoredText);
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
            var authorDef = $.Deferred();

            if (data.creator && data.changer && data.creator === data.changer) {
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

            if (!!data.author) {
                // load author
                this.loadContact(data.author).done(function(model) {
                    authorDef.resolve(model.get('fullName'), new Date(data.authored));
                }.bind(this)).fail(function() {
                    authorDef.resolve(null, new Date(data.authored));
                }.bind(this));
            } else {
                authorDef.resolve(null, new Date(data.authored));
            }

            this.sandbox.data.when(creatorDef, changerDef, authorDef).then(function(creation, change, author) {
                this.setCreationChangelog(creation[0], creation[1]);
                this.setChangeChangelog(change[0], change[1]);
                this.setAuthorChangelog(author[0], author[1]);
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
            var deferred = $.Deferred();

            // when no id set return here
            if (!id) {
                deferred.reject();

                return deferred;
            }

            var user = new User({id: id});
            user.fetch({
                global: false,

                success: function(model) {
                    deferred.resolve(model)
                }.bind(this),

                error: function() {
                    deferred.reject();
                }.bind(this)
            });
        },

        /**
         * Loads contact.
         *
         * @param {String} id
         *
         * @return {*}
         */
        loadContact: function(id) {
            var deferred = $.Deferred(),
                contact = new Contact({id: id});

            contact.fetch({
                global: false,

                success: function(model) {
                    deferred.resolve(model)
                }.bind(this),

                error: function() {
                    deferred.reject();
                }.bind(this)
            });

            return deferred;
        },

        bindDomEvents: function() {
            this.sandbox.dom.on('#change-author', 'click', function() {
                this.openAuthorSelection();
            }.bind(this));
        },

        openAuthorSelection: function() {
            var $overlayContainer = $('<div/>'),
                $componentContainer = $('<div/>');

            this.$el.append($overlayContainer);

            this.sandbox.start([{
                name: 'overlay@husky',
                options: {
                    el: $overlayContainer,
                    instanceName: 'author-selection',
                    openOnStart: true,
                    removeOnClose: true,
                    skin: 'medium',
                    slides: [
                        {
                            title: this.translations.author,
                            okCallback: function() {
                                this.sandbox.emit('sulu_article.get-author');
                            }.bind(this),
                            data: $componentContainer
                        }
                    ]
                }
            }]);

            this.sandbox.once('husky.overlay.author-selection.initialized', function() {
                this.sandbox.start([
                    {
                        name: 'articles/edit/settings/author-selection@suluarticle',
                        options: {
                            el: $componentContainer,
                            locale: this.options.locale,
                            data: {author: this.data.author, authored: this.data.authored},
                            selectCallback: function(data) {
                                this.setAuthor(data);

                                this.sandbox.emit('husky.overlay.author-selection.close');
                            }.bind(this)
                        }
                    }
                ]);
            }.bind(this));
        },

        setAuthor: function(data) {
            this.setDirty();

            this.data.authored = data.authored;
            if (!data.authorItem) {
                this.setAuthorChangelog(null, new Date(data.authored));

                return;
            }

            this.setAuthorChangelog(data.authorItem.firstName + ' ' + data.authorItem.lastName, new Date(data.authored));
            this.data.author = data.author;
        }
    };
});
