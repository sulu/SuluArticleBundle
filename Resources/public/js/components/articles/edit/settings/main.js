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
    'config',
    'sulusecurity/components/users/models/user',
    'sulucontact/models/contact',
    'services/suluarticle/article-manager',
    'services/suluarticle/article-router',
    'text!/admin/articles/template/settings.html'
], function(_, $, Config, User, Contact, ArticleManager, ArticleRouter, form) {

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
            createdOnly: 'sulu_article.form.settings.changelog.created-only',
            shadowArticle: 'sulu_article.shadow_article',
            shadowEnable: 'sulu_article.form.settings.shadow.enable',
            publishedShadow: 'sulu_article.published-shadow',
            shadowBaseLanguage: 'sulu_article.form.settings.shadow.base_language',
            webspaceSettings: 'sulu_article.form.settings.webspace_settings',
            webspaceSettingsCustomizeEnable: 'sulu_article.form.settings.webspace_settings.customize_enable',
            webspaceSettingsMainWebspace: 'sulu_article.form.settings.webspace_settings.main_webspace',
            webspaceSettingsAdditionalWebspaces: 'sulu_article.form.settings.webspace_settings.additional_webspaces',
        }
    },

    isShadow = function() {
        return this.sandbox.dom.prop('#shadow_on_checkbox', 'checked');
    },

    hasCustomWebspaceSettings = function() {
        return this.sandbox.dom.prop('#customize_on_checkbox', 'checked');
    };

    return {

        type: 'form-tab',

        defaults: defaults,

        authorFullname: null,

        disableWebspaceSettingsForm: false,

        /**
         * This method function has to be overwritten by the implementation to convert the data from "options.data".
         *
         * @param {object} data
         */
        parseData: function(data) {
            // check if route type is page tree
            if (data.routePath && typeof data.routePath === 'object' && data.routePath.hasOwnProperty('page')) {
                this.disableWebspaceSettingsForm = true;
            }

            return {
                id: data.id,
                author: data.author,
                authored: data.authored,
                creator: data.creator,
                changer: data.changer,
                created: data.created,
                changed: data.changed,
                concreteLanguages: data.concreteLanguages,
                enabledShadowLanguages: data.enabledShadowLanguages,
                shadowOn: data.shadowOn,
                shadowBaseLanguage: data.shadowBaseLanguage,
                mainWebspace: data.mainWebspace,
                additionalWebspaces: data.additionalWebspaces,
            };
        },

        render: function(data) {
            this.data = data;
            this.$el.html(this.getTemplate());

            this.createForm(data);

            if (Config.get('sulu-content')['versioning']['enabled']) {
                this.sandbox.start([
                    {
                        name: 'datagrid@husky',
                        options: {
                            el: '#versions',
                            instanceName: 'versions',
                            url: ArticleManager.getVersionsUrl(data.id, this.options.locale),
                            resultKey: 'versions',
                            actionCallback: this.restoreVersion.bind(this),
                            viewOptions: {
                                table: {
                                    actionIcon: 'history',
                                    actionColumn: 'authored',
                                    selectItem: false
                                }
                            },
                            matchings: [
                                {
                                    name: 'authored',
                                    attribute: 'authored',
                                    content: this.sandbox.translate('sulu-document-manager.version.authored'),
                                    type: 'datetime'
                                },
                                {
                                    name: 'author',
                                    attribute: 'author',
                                    content: this.sandbox.translate('sulu-document-manager.version.author')
                                }
                            ]
                        }
                    }
                ]);
            }

            if (Config.get('sulu_article').showWebspaceSettings) {
                // init/start webspace settings section
                this.updateWebspaceSettingsComponents(true);
                this.updateVisibilityForWebspaceSettingsCustomizeCheckbox(true);
            }

            // init/start shadow section
            this.startShadowSelect();
            this.updateVisibilityForShadowCheckbox(true);

            this.rendered();
        },

        startShadowSelect: function() {
            var shadowsForSelect = [],
                existingShadowForCurrentLanguage = null,
                selectedLanguage;

            if (this.data.enabledShadowLanguages[this.options.locale] !== undefined) {
                existingShadowForCurrentLanguage = this.data.enabledShadowLanguages[this.options.locale];
            }

            this.sandbox.util.each(this.data.concreteLanguages, function(i, language) {
                if (this.options.locale === language) {
                    return;
                }

                var disabled = false;
                if (existingShadowForCurrentLanguage === language) {
                    disabled = true;
                }
                shadowsForSelect.push({
                    id: language,
                    name: language,
                    disabled: disabled
                });
            }.bind(this));

            selectedLanguage = this.data.shadowBaseLanguage;

            // show at least a message
            if (shadowsForSelect.length === 0) {
                shadowsForSelect = [
                    {
                        id: -1,
                        name: this.sandbox.translate('sulu.content.form.settings.shadow.no_base_language'),
                        disabled: true
                    }
                ];
            }

            this.sandbox.start([
                {
                    name: 'select@husky',
                    options: {
                        el: '#shadow_base_language_select',
                        instanceName: 'settings',
                        multipleSelect: false,
                        defaultLabel: this.sandbox.translate('sulu.content.form.settings.shadow.select_base_language'),
                        data: shadowsForSelect,
                        preSelectedElements: [selectedLanguage]
                    }
                }
            ]);

            if (this.data.shadowOn) {
                this.sandbox.dom.attr('#shadow_on_checkbox', 'checked', true);
            }
        },

        updateVisibilityForShadowCheckbox: function(isInitial) {
            var shadow = isShadow.call(this);
            var $shadowDescription = this.sandbox.dom.find('#shadow-container .input-description');

            if (!!shadow) {
                this.sandbox.emit('sulu.article.show-save-items', 'shadow');
                $shadowDescription.show();
            } else {
                this.sandbox.emit('sulu.article.show-save-items', 'content');
                $shadowDescription.hide();
            }

            if (!isInitial) {
                this.sandbox.emit('sulu.tab.dirty');
            }
        },

        getSelectedMainWebspace: function() {
            var value = undefined;

            this.sandbox.emit('husky.select.main_webspace_select.get-checked', function(selected) {
                value = selected[0];
            }.bind(this));

            return value;
        },

        getSelectedAdditionalWebspace: function() {
            var value = undefined;

            this.sandbox.emit('husky.select.additional_webspace_multi_select.get-checked', function(selected) {
                value = selected;
            }.bind(this));

            return value;
        },

        updateWebspaceSettingsComponents: function(isInitial) {
            var mainWebspace;
            var additionalWebspaces;
            var config = Config.get('sulu_article');

            if (isInitial) {
                mainWebspace = this.data.mainWebspace ? this.data.mainWebspace : config.webspaceSettings[this.options.locale].defaultMainWebspace;
                additionalWebspaces = this.data.additionalWebspaces ? this.data.additionalWebspaces : config.webspaceSettings[this.options.locale].defaultAdditionalWebspaces;
            } else {
                mainWebspace = this.getSelectedMainWebspace();
                additionalWebspaces = this.getSelectedAdditionalWebspace();
            }

            var availableMainWebspaces = [];
            var availableAdditionalWebspaces = [];
            var selectedMainWebspace = [];
            var selectedAdditionalWebspaces = [];

            this.sandbox.util.each(config.webspaces, function(i, webspace) {
                availableMainWebspaces.push(webspace);

                if (mainWebspace === webspace.id) {
                    selectedMainWebspace.push(webspace.id);

                    return;
                }

                availableAdditionalWebspaces.push(webspace);

                if (additionalWebspaces.indexOf(webspace.id) > -1) {
                    selectedAdditionalWebspaces.push(webspace.id);
                }
            }.bind(this));

            if (!isInitial) {
                // update values of selects

                this.sandbox.emit(
                    'husky.select.main_webspace_select.update',
                    availableMainWebspaces,
                    selectedMainWebspace,
                    false
                );

                this.sandbox.emit(
                    'husky.select.additional_webspace_multi_select.update',
                    availableAdditionalWebspaces,
                    selectedAdditionalWebspaces,
                    false
                );

                return;
            }

            if (this.disableWebspaceSettingsForm) {
                // disable customize checkbox
                $('#customize_on_checkbox').attr('disabled', 'disabled');
            }

            // start selects
            this.sandbox.start([
                {
                    name: 'select@husky',
                    options: {
                        disabled: this.disableWebspaceSettingsForm,
                        el: '#main_webspace_select',
                        instanceName: 'main_webspace_select',
                        multipleSelect: false,
                        data: availableMainWebspaces,
                        preSelectedElements: [selectedMainWebspace],
                        defaultLabel: this.sandbox.translate('dropdown.please-choose'),
                    }
                }
            ]);

            this.sandbox.start([
                {
                    name: 'select@husky',
                    options: {
                        disabled: this.disableWebspaceSettingsForm,
                        el: '#additional_webspace_multi_select',
                        instanceName: 'additional_webspace_multi_select',
                        multipleSelect: true,
                        data: availableAdditionalWebspaces,
                        preSelectedElements: selectedAdditionalWebspaces,
                        defaultLabel: this.sandbox.translate('dropdown.please-choose'),
                        checkedAllLabel: this.sandbox.translate('public.all'),
                    }
                }
            ]);

            if (this.data.mainWebspace) {
                this.sandbox.dom.attr('#customize_on_checkbox', 'checked', true);
            }
        },

        updateVisibilityForWebspaceSettingsCustomizeCheckbox: function(isInitial) {
            var webspaceSettingsCustom = hasCustomWebspaceSettings.call(this);
            var $webspaceSettingsInfoDefault = this.sandbox.dom.find('#webspace-settings-info-default');
            var $webspaceSettingsCustomizeForm = this.sandbox.dom.find('#webspace-settings-customize-form');

            if (webspaceSettingsCustom) {
                $webspaceSettingsInfoDefault.hide();
                $webspaceSettingsCustomizeForm.show();
            } else {
                $webspaceSettingsCustomizeForm.hide();
                $webspaceSettingsInfoDefault.show();
            }

            if (!isInitial) {
                this.sandbox.emit('sulu.tab.dirty');
            }
        },

        rendered: function() {
            this.updateChangelog(this.data);
            this.bindDomEvents();
            this.bindEvents();
            this.listenForChange();
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
            var baseLanguages = this.sandbox.dom.data('#shadow_base_language_select', 'selectionValues');

            data.mainWebspace = null;
            data.additionalWebspaces = null;
            if (hasCustomWebspaceSettings.call(this)) {
                data.mainWebspace = this.getSelectedMainWebspace();
                data.additionalWebspaces = this.getSelectedAdditionalWebspace();
            }

            data.shadowOn = isShadow.call(this);
            data.shadowBaseLanguage = null;
            if (!!data.shadowOn && !!baseLanguages && baseLanguages.length > 0) {
                data.shadowBaseLanguage = baseLanguages[0];
            }

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
                this.sandbox.emit('sulu.article.error', xhr.status, xhr.responseJSON.code || 0, data);
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
            return this.templates.form(
                {
                    translations: this.translations,
                    config: Config.get('sulu_article'),
                    locale: this.options.locale,
                }
            );
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
         * @param {Boolean} remove
         */
        setAuthorChangelog: function(fullName, date, remove) {
            var authoredText, formattedDate = this.sandbox.date.format(date, true);

            if (!fullName && !remove) {
                fullName = this.authorFullname;
            }

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
                this.authorFullname = null;
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
                );
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
                );
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

            return deferred;
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

            this.sandbox.dom.on('#shadow_on_checkbox', 'click', function() {
                this.updateVisibilityForShadowCheckbox(false);
            }.bind(this));

            this.sandbox.dom.on('#customize_on_checkbox', 'click', function() {
                this.updateVisibilityForWebspaceSettingsCustomizeCheckbox(false);
            }.bind(this));
        },

        bindEvents: function() {
            this.sandbox.on('husky.select.main_webspace_select.selected.item', function() {
                this.updateWebspaceSettingsComponents();
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
                                this.sandbox.emit('sulu.content.contents.get-author');
                            }.bind(this),
                            data: $componentContainer
                        }
                    ]
                }
            }]);

            this.sandbox.once('husky.overlay.author-selection.initialized', function() {
                this.sandbox.start([
                    {
                        name: 'content/settings/author-selection@sulucontent',
                        options: {
                            el: $componentContainer,
                            locale: this.options.locale,
                            data: {author: this.data.author, authored: this.data.authored},
                            nullableAuthor: !this.options.config.defaultAuthor,
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
            this.data.author = data.author;
            if (!data.authorItem) {
                this.setAuthorChangelog(null, new Date(data.authored), true);

                return;
            }

            this.setAuthorChangelog(data.authorItem.firstName + ' ' + data.authorItem.lastName, new Date(data.authored));
        },

        restoreVersion: function(versionId, version) {
            this.sandbox.sulu.showConfirmationDialog({
                callback: function(wasConfirmed) {
                    if (!wasConfirmed) {
                        return;
                    }

                    this.sandbox.emit('husky.overlay.alert.show-loader');
                    ArticleManager.restoreVersion(this.options.id, versionId, version.locale)
                        .always(function() {
                            this.sandbox.emit('husky.overlay.alert.hide-loader');
                        }.bind(this))
                        .then(function() {
                            this.sandbox.emit('husky.overlay.alert.close');
                            ArticleRouter.toEditForce(this.data.id, this.options.locale);
                        }.bind(this))
                        .fail(function() {
                            this.sandbox.emit(
                                'sulu.labels.error.show',
                                'sulu.content.restore-error-description',
                                'sulu.content.restore-error-title'
                            );
                        }.bind(this));

                    return false;
                }.bind(this),
                title: this.sandbox.translate('sulu-document-manager.restore-confirmation-title'),
                description: this.sandbox.translate('sulu-document-manager.restore-confirmation-description')
            });
        }
    };
});
