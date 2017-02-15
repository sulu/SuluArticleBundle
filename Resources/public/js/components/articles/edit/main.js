/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

define([
    'jquery',
    'underscore',
    'config',
    'services/suluarticle/article-manager',
    'sulusecurity/services/user-manager',
    'services/sulupreview/preview',
    'sulusecurity/services/security-checker',
    'sulucontent/components/copy-locale-overlay/main',
    'sulucontent/components/open-ghost-overlay/main'
], function($, _, config, ArticleManager, UserManager, Preview, SecurityChecker, CopyLocale, OpenGhost) {

    'use strict';

    return {

        defaults: {
            options: {
                config: {}
            },

            templates: {
                url: '/admin/api/articles<% if (!!id) { %>/<%= id %><% } %>?locale=<%= locale %>'
            },

            translations: {
                headline: 'sulu_article.edit.title',
                draftLabel: 'sulu-document-manager.draft-label',
                removeDraft: 'sulu-content.delete-draft',
                unpublish: 'sulu-document-manager.unpublish',
                unpublishConfirmTextNoDraft: 'sulu-content.unpublish-confirm-text-no-draft',
                unpublishConfirmTextWithDraft: 'sulu-content.unpublish-confirm-text-with-draft',
                unpublishConfirmTitle: 'sulu-content.unpublish-confirm-title',
                deleteDraftConfirmTitle: 'sulu-content.delete-draft-confirm-title',
                deleteDraftConfirmText: 'sulu-content.delete-draft-confirm-text'
            }
        },

        layout: function(){
            return {
                navigation: {
                    collapsed: true
                },
                content: {
                    shrinkable: !!this.options.id
                },
                sidebar: (!!this.options.id) ? 'max' : false
            };
        },

        header: function() {
            var buttons = {}, editDropdown = {}, saveDropdown = {};

            if (SecurityChecker.hasPermission(this.data, 'edit')) {
                saveDropdown.saveDraft = {};

                if (SecurityChecker.hasPermission(this.data, 'live')) {
                    saveDropdown.savePublish = {};
                    saveDropdown.publish = {};
                }

                if (!!config.has('sulu_automation.enabled')) {
                    saveDropdown.automationInfo = {
                        options: {
                            entityId: this.options.id,
                            entityClass: 'Sulu\\Bundle\\ArticleBundle\\Document\\ArticleDocument',
                            handlerClass: [
                                'Sulu\\Bundle\\ContentBundle\\Automation\\DocumentPublishHandler',
                                'Sulu\\Bundle\\ContentBundle\\Automation\\DocumentUnpublishHandler'
                            ]
                        }
                    };
                }

                buttons.save = {
                    parent: 'saveWithDraft',
                    options: {
                        callback: function() {
                            this.sandbox.emit('sulu.toolbar.save', 'publish');
                        }.bind(this),
                        dropdownItems: saveDropdown
                    }
                };

                buttons.template = {
                    options: {
                        dropdownOptions: {
                            url: '/admin/articles/templates?type=' + (this.options.type || this.data.articleType),
                            callback: function(item) {
                                this.template = item.template;
                                this.sandbox.emit('sulu.tab.template-change', item);
                            }.bind(this)
                        }
                    }
                }
            }

            if (SecurityChecker.hasPermission(this.data, 'live')) {
                editDropdown.unpublish = {
                    options: {
                        title: this.translations.unpublish,
                        disabled: !this.data.published,
                        callback: this.unpublish.bind(this)
                    }
                };

                editDropdown.divider = {
                    options: {
                        divider: true
                    }
                };
            }

            if (SecurityChecker.hasPermission(this.data, 'delete')) {
                editDropdown.delete = {
                    options: {
                        disabled: !this.options.id,
                        callback: this.deleteArticle.bind(this)
                    }
                };
            }

            editDropdown.copyLocale = {
                options: {
                    title: this.sandbox.translate('toolbar.copy-locale'),
                        callback: function() {
                        CopyLocale.startCopyLocalesOverlay.call(this).then(function(newLocales) {
                            // reload form when the current locale is in newLocales
                            if (_.contains(newLocales, this.options.locale)) {
                                this.toEdit(this.options.locale);

                                return;
                            }

                            // save new created locales to data and show success label
                            this.data.concreteLanguages = _.uniq(this.data.concreteLanguages.concat(newLocales));
                            this.sandbox.emit('sulu.labels.success.show', 'labels.success.copy-locale-desc', 'labels.success');
                        }.bind(this));
                    }.bind(this)
                }
            };

            if (!this.sandbox.util.isEmpty(editDropdown)) {
                buttons.edit = {
                    options: {
                        dropdownItems: editDropdown
                    }
                };
            }

            buttons.statePublished = {};
            buttons.stateTest = {};

            return {
                tabs: {
                    url: '/admin/content-navigations?alias=article&id=' + this.options.id + '&locale=' + this.options.locale,
                    options: {
                        data: function() {
                            return this.sandbox.util.deepCopy(this.data);
                        }.bind(this),
                        url: function() {
                            return this.templates.url({id: this.options.id, locale: this.options.locale});
                        }.bind(this),
                        config: this.options.config,
                        preview: this.preview
                    },
                    componentOptions: {
                        values: _.defaults(this.data, {type: null})
                    }
                },

                toolbar: {
                    buttons: buttons,
                    languageChanger: {
                        data: this.options.config.languageChanger,
                        preSelected: this.options.locale
                    }
                }
            };
        },

        initialize: function() {
            this.bindCustomEvents();
            this.showDraftLabel();
            this.setHeaderBar(true);
            this.loadLocalizations();

            // the open ghost overlay component needs the current locale in `this.options.language`
            this.options.language = this.options.locale;
        },

        bindCustomEvents: function() {
            this.sandbox.on('sulu.header.back', this.toList.bind(this));
            this.sandbox.on('sulu.tab.dirty', this.setHeaderBar.bind(this));
            this.sandbox.on('sulu.toolbar.save', this.save.bind(this));
            this.sandbox.on('sulu.tab.data-changed', this.setData.bind(this));
            this.sandbox.on('sulu.article.error', this.handleError.bind(this));
            this.sandbox.on('husky.tabs.header.item.select', this.tabChanged.bind(this));
            this.sandbox.on('sulu.header.language-changed', this.languageChanged.bind(this));
        },

        /**
         * Language changed event.
         *
         * @param {Object} item
         */
        languageChanged: function(item) {
            if (item.id === this.options.locale) {
                return;
            }

            this.sandbox.sulu.saveUserSetting(this.options.config.settingsKey, item.id);

            if (-1 === _(this.data.concreteLanguages).indexOf(item.id)) {
                OpenGhost.openGhost.call(this, this.data).then(function(copy, src) {
                    if (!!copy) {
                        CopyLocale.copyLocale.call(
                            this,
                            this.data.id,
                            src,
                            [item.id],
                            function() {
                                this.toEdit(item.id);
                            }.bind(this)
                        );
                    } else {
                        // new article will be created
                        this.toEdit(item.id);
                    }
                }.bind(this)).fail(function() {
                    // the open-ghost page got canceled, so reset the language changer
                    this.sandbox.emit('sulu.header.change-language', this.options.language);
                }.bind(this));
            } else {
                this.toEdit(item.id);
            }
        },

        /**
         * Tab changed event, save the new tab id to `this.options.content`.
         * Can be removed when issue #72 is solved: https://github.com/sulu/SuluArticleBundle/issues/72
         *
         * @param {Object} item
         */
        tabChanged: function(item) {
            this.options.content = item.id;
        },

        /**
         * Handles the error based on its error code.
         *
         * @param {Number} errorCode
         * @param {Object} data
         * @param {string} action
         */
        handleError: function(errorCode, data, action) {
            switch (errorCode) {
                default:
                    this.sandbox.emit('sulu.labels.error.show', 'labels.error.content-save-desc', 'labels.error');
                    this.sandbox.emit('sulu.header.toolbar.item.enable', 'save');
            }
        },

        deleteArticle: function() {
            this.sandbox.sulu.showDeleteDialog(function(wasConfirmed) {
                if (wasConfirmed) {
                    ArticleManager.remove(this.options.id, this.options.locale).then(function() {
                        this.toList();
                    }.bind(this));
                }
            }.bind(this));
        },

        toEdit: function(locale, id) {
            this.sandbox.emit('sulu.router.navigate', 'articles/' + (locale || this.options.locale) + '/edit:' + (id || this.options.id) + '/' + (this.options.content  || 'details'), true, true);
        },

        toList: function() {
            if (this.options.config.typeNames.length === 1) {
                this.sandbox.emit('sulu.router.navigate', 'articles/' + this.options.locale);
            } else {
                this.sandbox.emit('sulu.router.navigate', 'articles:' + (this.options.type || this.data.articleType) + '/' + this.options.locale);
            }
        },

        toAdd: function() {
            if (this.options.config.typeNames.length === 1) {
                this.sandbox.emit('sulu.router.navigate', 'articles/' + this.options.locale + '/add', true, true);
            } else {
                this.sandbox.emit('sulu.router.navigate', 'articles/' + this.options.locale + '/add:' + (this.options.type || this.data.articleType), true, true);
            }
        },

        save: function(action) {
            this.loadingSave();

            this.saveTab(action).then(function(data) {
                this.saved(data.id, data, action);
            }.bind(this));
        },

        setData: function(data) {
            this.data = data;
        },

        saveTab: function(action) {
            var promise = $.Deferred();

            // Display loading animation.
            this.sandbox.emit('sulu.header.toolbar.item.loading', 'save');

            this.sandbox.once('sulu.tab.saved', function(id, data) {
                promise.resolve(_.defaults(data, {type: null}));
            }.bind(this));

            this.sandbox.emit('sulu.tab.save', action);

            return promise;
        },

        setHeaderBar: function(saved) {
            var saveDraft = !saved,
                savePublish = !saved,
                publish = !!saved && !this.data.publishedState;

            this.setSaveToolbarItems.call(this, 'saveDraft', saveDraft);
            this.setSaveToolbarItems.call(this, 'savePublish', savePublish);
            this.setSaveToolbarItems.call(this, 'publish', publish);
            this.setSaveToolbarItems.call(this, 'unpublish', !!this.data.published);

            if (!!saveDraft || !!savePublish || !!publish) {
                this.sandbox.emit('sulu.header.toolbar.item.enable', 'save', false);
            } else {
                this.sandbox.emit('sulu.header.toolbar.item.disable', 'save', false);
            }

            this.showState(!!this.data.published);
        },

        setSaveToolbarItems: function(item, value) {
            this.sandbox.emit('sulu.header.toolbar.item.' + (!!value ? 'enable' : 'disable'), item, false);
        },

        loadingSave: function() {
            this.sandbox.emit('sulu.header.toolbar.item.loading', 'save');
        },

        /**
         * Routes either to the list, article-add or article-edit, depending on the passed parameter.
         *
         * @param action {String} 'new', 'add' or null
         * @param toEdit {Boolean} if true and no action has been passed the method routes to 'edit'
         */
        afterSaveAction: function(action, toEdit) {
            if (action === 'back') {
                this.toList();
            } else if (action === 'new') {
                this.toAdd();
            } else if (toEdit) {
                this.toEdit(this.options.locale, this.data.id);
            }
        },

        showDraftLabel: function() {
            this.sandbox.emit('sulu.header.tabs.label.hide');

            if (this.hasDraft(this.data)) {
                return;
            }

            UserManager.find(this.data.changer).then(function(response) {
                this.sandbox.emit(
                    'sulu.header.tabs.label.show',
                    this.sandbox.util.sprintf(
                        this.translations.draftLabel,
                        {
                            changed: this.sandbox.date.format(this.data.changed, true),
                            user: response.username
                        }
                    ),
                    [
                        {
                            id: 'delete-draft',
                            title: this.translations.removeDraft,
                            skin: 'critical',
                            onClick: this.deleteDraft.bind(this)
                        }
                    ]
                );
            }.bind(this));
        },

        deleteDraft: function() {
            this.sandbox.sulu.showDeleteDialog(
                function(wasConfirmed) {
                    if (!wasConfirmed) {
                        return;
                    }

                    this.sandbox.emit('husky.label.header.loading');

                    ArticleManager.removeDraft(this.data.id, this.options.locale).always(function() {
                        this.sandbox.emit('sulu.header.toolbar.item.enable', 'edit');
                    }.bind(this)).then(function(response) {
                        this.sandbox.emit(
                            'sulu.router.navigate',
                            this.sandbox.mvc.history.fragment,
                            true,
                            true
                        );
                        this.saved(response.id, response);
                    }.bind(this)).fail(function() {
                        this.sandbox.emit('husky.label.header.reset');
                        this.sandbox.emit(
                            'sulu.labels.error.show',
                            'labels.error.remove-draft-desc',
                            'labels.error'
                        );
                    }.bind(this));
                }.bind(this),
                this.translations.deleteDraftConfirmTitle,
                this.translations.deleteDraftConfirmText
            )
        },

        hasDraft: function(data) {
            return !data.id || !!data.publishedState || !data.published;
        },

        getUrl: function(action) {
            var url = _.template(this.defaults.templates.url, {
                id: this.options.id,
                locale: this.options.locale
            });

            if (action) {
                url += '&action=' + action;
            }

            return url;
        },

        loadComponentData: function() {
            var promise = $.Deferred();

            if (!this.options.id) {
                promise.resolve({});

                return promise;
            }

            this.sandbox.util.load(this.getUrl()).done(function(data) {
                this.preview = Preview.initialize({});
                this.preview.start(
                    'Sulu\\Bundle\\ArticleBundle\\Document\\ArticleDocument',
                    this.options.id,
                    this.options.locale,
                    data
                );

                promise.resolve(data);
            }.bind(this));

            return promise;
        },

        destroy: function() {
            if (!!this.preview) {
                Preview.destroy(this.preview);
            }
        },

        showState: function(published) {
            if (!!published) {
                this.sandbox.emit('sulu.header.toolbar.item.hide', 'stateTest');
                this.sandbox.emit('sulu.header.toolbar.item.show', 'statePublished');
            } else {
                this.sandbox.emit('sulu.header.toolbar.item.hide', 'statePublished');
                this.sandbox.emit('sulu.header.toolbar.item.show', 'stateTest');
            }
        },

        unpublish: function() {
            this.sandbox.sulu.showConfirmationDialog({
                callback: function(wasConfirmed) {
                    if (!wasConfirmed) {
                        return;
                    }

                    this.sandbox.emit('sulu.header.toolbar.item.loading', 'edit');

                    ArticleManager.unpublish(this.data.id, this.options.locale).always(function() {
                        this.sandbox.emit('sulu.header.toolbar.item.enable', 'edit');
                    }.bind(this)).then(function(response) {
                        this.sandbox.emit(
                            'sulu.labels.success.show',
                            'labels.success.content-unpublish-desc',
                            'labels.success'
                        );
                        this.saved(response.id, response);
                    }.bind(this)).fail(function() {
                        this.sandbox.emit(
                            'sulu.labels.error.show',
                            'labels.error.content-unpublish-desc',
                            'labels.error'
                        );
                    }.bind(this));
                }.bind(this),
                title: this.translations.unpublishConfirmTitle,
                description: !!this.hasDraft(this.data) ?
                    this.translations.unpublishConfirmTextNoDraft :
                    this.translations.unpublishConfirmTextWithDraft
            });
        },

        saved: function(id, data, action) {
            this.setData(data);

            if (!this.options.id) {
                this.sandbox.sulu.viewStates.justSaved = true;
            } else {
                this.setHeaderBar(true);
                this.showDraftLabel();

                this.sandbox.emit('sulu.header.saved', data);
                this.sandbox.emit('sulu.labels.success.show', 'labels.success.content-save-desc', 'labels.success');
            }

            this.afterSaveAction(action, !this.options.id);
        },

        loadLocalizations: function() {
            this.sandbox.util.load('/admin/api/localizations').then(function(data) {
                this.localizations = data._embedded.localizations.map(function(localization) {
                    return {
                        id: localization.localization,
                        title: localization.localization
                    };
                });
            }.bind(this));
        },

        /**
         * Returns copy article from a given locale to a array of other locales url.
         *
         * @param {string} id
         * @param {string} src
         * @param {string[]} dest
         *
         * @returns {string}
         */
        getCopyLocaleUrl: function(id, src, dest) {
            return ArticleManager.getCopyLocaleUrl(id, src, dest);
        }
    }
});
