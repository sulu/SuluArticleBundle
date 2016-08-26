/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

define(['underscore', 'jquery', 'config'], function(_, $, Config) {

    'use strict';

    return {

        layout: {
            extendExisting: true,

            content: {
                width: 'fixed',
                rightSpace: false,
                leftSpace: false
            }
        },

        initialize: function() {
            this.saved = true;

            this.render();

            this.bindCustomEvents();
            this.listenForChange();
        },

        bindCustomEvents: function() {
            this.sandbox.on('sulu.tab.template-change', function(item) {
                this.checkRenderTemplate(item.template);
            }, this);

            this.sandbox.on('sulu.content.contents.default-template', function(name) {
                this.template = name;
                this.sandbox.emit('sulu.header.toolbar.item.change', 'template', name);
            }.bind(this));

            this.sandbox.on('sulu.tab.save', this.save.bind(this));
        },

        listenForChange: function() {
            this.sandbox.dom.on(this.$el, 'keyup', _.debounce(this.setDirty.bind(this), 10), 'input, textarea');
            this.sandbox.dom.on(this.$el, 'change', _.debounce(this.setDirty.bind(this), 10), 'input[type="checkbox"], select');
            this.sandbox.on('sulu.content.changed', this.setDirty.bind(this));
        },

        setDirty: function() {
            this.saved = false;
            this.sandbox.emit('sulu.tab.dirty');
        },

        save: function(action) {
            if (!this.sandbox.form.validate(this.formId)) {
                return this.sandbox.emit('sulu.tab.dirty', true);
            }

            var data = this.sandbox.form.getData(this.formId);
            data.template = this.template;

            this.sandbox.emit('sulu.articles.save', data, action);
        },

        render: function() {
            this.checkRenderTemplate(this.data.template || null);
        },

        checkRenderTemplate: function(template) {
            if (!!template && this.template === template) {
                return this.sandbox.emit('sulu.header.toolbar.item.enable', 'template', false);
            }

            this.sandbox.emit('sulu.header.toolbar.item.loading', 'template');

            if (this.template !== '' && !this.saved) {
                this.showRenderTemplateDialog(template);
            } else {
                this.loadFormTemplate(template);
            }
        },

        showRenderTemplateDialog: function(template) {
            // show warning dialog
            this.sandbox.emit('sulu.overlay.show-warning',
                'sulu.overlay.be-careful',
                'content.template.dialog.content',
                function() {
                    // cancel callback
                    this.sandbox.emit('sulu.header.toolbar.item.enable', 'template', false);

                    if (!!this.template) {
                        this.sandbox.emit('sulu.header.toolbar.item.change', 'template', this.template, false);
                    }
                }.bind(this),
                function() {
                    // ok callback
                    this.loadFormTemplate(template);
                }.bind(this)
            );
        },

        loadFormTemplate: function(template) {
            if (!template) {
                template = this.options.config.types[(this.options.type || this.data.type)].default;
            }

            this.template = template;
            this.formId = '#content-form-container';
            this.$container = this.sandbox.dom.createElement('<div id="content-form-container"/>');
            this.html(this.$container);

            if (!!this.sandbox.form.getObject(this.formId)) {
                var data = this.data;
                this.data = this.sandbox.form.getData(this.formId);
                if (!!data.id) {
                    this.data.id = data.id;
                }

                this.data = this.sandbox.util.extend({}, data, this.data);
            }

            require([this.getTemplateUrl(template)], function(template) {
                this.renderFormTemplate(template);
            }.bind(this));
        },

        getTemplateUrl: function(template) {
            var url = 'text!/admin/content/template/form';
            if (!!template) {
                url += '/' + template + '.html';
            } else {
                url += '.html';
            }
            url += '?type=article&language=' + this.options.locale;

            if (!!this.data.id) {
                url += '&uuid=' + this.data.id;
            }

            return url;
        },

        renderFormTemplate: function(template) {
            this.sandbox.dom.html(this.formId, this.sandbox.util.template(template, {
                translate: this.sandbox.translate,
                content: this.data,
                options: this.options
            }));

            if (!this.data.id) {
                // route-path will be generator on post-request
                this.$find('#routePath').parent().remove();
            }

            this.createForm(this.data).then(function() {
                this.changeTemplateDropdownHandler();
            }.bind(this));
        },

        changeTemplateDropdownHandler: function() {
            if (!!this.template) {
                this.sandbox.emit('sulu.header.toolbar.item.change', 'template', this.template);
            }
            this.sandbox.emit('sulu.header.toolbar.item.enable', 'template', false);
        },

        createForm: function(data) {
            var formObject = this.sandbox.form.create(this.formId),
                deferred = this.sandbox.data.deferred();

            formObject.initialized.then(function() {
                this.sandbox.form.setData(this.formId, data).then(function() {
                    this.sandbox.start(this.$el, {reset: true}).then(function() {
                        this.initSortableBlock();
                        this.bindFormEvents();

                        deferred.resolve();
                    }.bind(this));
                }.bind(this));
            }.bind(this));

            return deferred.promise();
        },

        initSortableBlock: function() {
            var $sortable = this.sandbox.dom.find('.sortable', this.$el),
                sortable;

            if (!!$sortable && $sortable.length > 0) {
                this.sandbox.dom.sortable($sortable, 'destroy');
                sortable = this.sandbox.dom.sortable($sortable, {
                    handle: '.move',
                    forcePlaceholderSize: true
                });

                // (un)bind event listener
                this.sandbox.dom.unbind(sortable, 'sortupdate');

                sortable.bind('sortupdate', function(event) {
                    this.sandbox.emit('sulu.content.changed');
                }.bind(this));
            }
        },

        bindFormEvents: function() {
            this.sandbox.dom.on(this.formId, 'form-remove', function() {
                this.initSortableBlock();
                this.setDirty();
            }.bind(this));

            this.sandbox.dom.on(this.formId, 'form-add', function(e, propertyName, data, index) {
                this.createConfiguration(e.currentTarget);

                var $elements = this.sandbox.dom.children(this.$find('[data-mapper-property="' + propertyName + '"]')),
                    $element = (index !== undefined && $elements.length > index) ? $elements[index] : this.sandbox.dom.last($elements);

                // start new subcomponents
                this.sandbox.start($element);

                // enable save button
                this.setDirty();

                // reinit sorting
                this.initSortableBlock();
            }.bind(this));

            this.sandbox.dom.on(this.formId, 'init-sortable', function(e) {
                // reinit sorting
                this.initSortableBlock();
            }.bind(this));
        },

        loadComponentData: function() {
            var promise = $.Deferred();

            promise.resolve(this.options.data());

            return promise;
        }
    };
});
