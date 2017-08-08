/*
 * This file is part of the Sulu CMS.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

define([
    'services/suluarticle/list-helper',
    'services/husky/mediator',
], function(listHelper, mediator) {

    'use strict';

    var
        /**
         * Set button-title.
         *
         * @param {String} button
         * @param {String} title
         */
        setButtonTitle = function(button, title) {
            mediator.emit('husky.toolbar.' + this.instanceName + '.button.set', button, {title: title});
        },

        /**
         * Goto given slide and wait for ok-button identified thru given alias.
         *
         * @param {Number} slide
         * @param {String} alias
         */
        gotoSlide = function(slide, alias) {
            mediator.emit('husky.overlay.' + this.instanceName + '.slide-to', slide);

            mediator.once(this.okClickNamespace + '.' + this.instanceName + '.ok-button.clicked', function() {
                mediator.emit('sulu_article.' + alias + '-selection.form.get');
            }.bind(this));
        },

        /**
         * Close the authored selection and goto first-slide.
         *
         * @param {Object} data
         */
        closeAuthoredSelection = function(data) {
            mediator.emit('husky.datagrid.' + this.instanceName + '.url.update', {
                authoredFrom: data ? data.from : null,
                authoredTo: data ? data.to : null,
            });

            setButtonTitle.call(this, 'authoredDate', listHelper.getAuthoredTitle(data));

            mediator.emit('husky.overlay.' + this.instanceName + '.slide-to', 0);
        },

        /**
         * Close the contact selection and goto first-slide.
         *
         * @param {Object} data
         * @param {String} filterKey
         */
        closeContactSelection = function(data, filterKey) {
            var filter = {filterKey: filterKey || 'filterByAuthor', contact: data.contactItem};

            setButtonTitle.call(this, 'filter', listHelper.getFilterTitle(filter));
            mediator.emit('husky.datagrid.' + this.instanceName + '.url.update', getUrlParameter(filter));

            mediator.emit('husky.overlay.' + this.instanceName + '.slide-to', 0);
        },

        /**
         * Close the category selection and goto first-slide.
         *
         * @param {Object} data
         */
        closeCategorySelection = function(data) {
            var filter = {filterKey: 'filterByCategory', category: data.categoryItem};

            setButtonTitle.call(this, 'filter', listHelper.getFilterTitle(filter));
            mediator.emit('husky.datagrid.' + this.instanceName + '.url.update', getUrlParameter(filter));

            mediator.emit('husky.overlay.' + this.instanceName + '.slide-to', 0);
        },

        /**
         * Close the tag selection and goto first-slide.
         *
         * @param {Object} data
         */
        closeTagSelection = function(data) {
            var filter = {filterKey: 'filterByTag', tag: data.tagItem};

            setButtonTitle.call(this, 'filter', listHelper.getFilterTitle(filter));
            mediator.emit('husky.datagrid.' + this.instanceName + '.url.update', getUrlParameter(filter));

            mediator.emit('husky.overlay.' + this.instanceName + '.slide-to', 0);
        },

        /**
         * Close the page selection and goto first-slide.
         *
         * @param {Object} data
         */
        closePageSelection = function(data) {
            var filter = {filterKey: 'filterByPage', page: data.pageItem};

            setButtonTitle.call(this, 'filter', listHelper.getFilterTitle(filter));
            mediator.emit('husky.datagrid.' + this.instanceName + '.url.update', getUrlParameter(filter));

            mediator.emit('husky.overlay.' + this.instanceName + '.slide-to', 0);
        },

        /**
         * Remove all filter from datagrid.
         */
        removeFilter = function() {
            setButtonTitle.call(this, 'filter', listHelper.getFilterTitle());

            mediator.emit('husky.datagrid.' + this.instanceName + '.url.update', getUrlParameter({}));
        },

        /**
         * Set workflow-stage.
         *
         * @param {String} workflowStage
         */
        setWorkflowStage = function(workflowStage) {
            mediator.emit('husky.datagrid.' + this.instanceName + '.url.update', {
                workflowStage: workflowStage,
            });
        },

        /**
         * Create url-parameter from given filter.
         *
         * @param {Object} filter
         *
         * @returns {{contactId: null, categoryId: null, tagId: null, pageId: null}}
         */
        getUrlParameter = function(filter) {
            return {
                contactId: filter.contact ? filter.contact.id : null,
                categoryId: filter.category ? filter.category.id : null,
                tagId: filter.tag ? filter.tag.id : null,
                pageId: filter.page ? filter.page.id : null,
            };
        };

    function FilterState($el, instanceName, locale, okClickNamespace) {
        this.$el = $el;
        this.instanceName = instanceName;
        this.locale = locale;
        this.okClickNamespace = okClickNamespace;
    }

    /**
     * Start filter components.
     *
     * @param {{start}} sandbox
     */
    FilterState.prototype.startFilterComponents = function(sandbox) {
        sandbox.start([
            {
                name: 'articles/list/authored-selection/form@suluarticle',
                options: {
                    el: '.slide.authored-slide .overlay-content',
                    selectCallback: closeAuthoredSelection.bind(this)
                }
            },
            {
                name: 'articles/list/contact-selection/form@suluarticle',
                options: {
                    el: '.slide.contact-slide .overlay-content',
                    selectCallback: closeContactSelection.bind(this)
                }
            },
            {
                name: 'articles/list/category-selection/form@suluarticle',
                options: {
                    el: '.slide.category-slide .overlay-content',
                    locale: this.locale,
                    selectCallback: closeCategorySelection.bind(this)
                }
            },
            {
                name: 'articles/list/tag-selection/form@suluarticle',
                options: {
                    el: '.slide.tag-slide .overlay-content',
                    selectCallback: closeTagSelection.bind(this)
                }
            },
            {
                name: 'articles/list/page-selection/form@suluarticle',
                options: {
                    el: '.slide.page-slide .overlay-content',
                    locale: this.locale,
                    selectCallback: closePageSelection.bind(this)
                }
            }
        ]);
    };

    /**
     * Create toolbar-template.
     *
     * @param {{sulu}} sandbox
     */
    FilterState.prototype.createToolbarTemplate = function(sandbox) {
        return sandbox.sulu.buttons.get({
            authoredDate: {
                options: {
                    icon: 'calendar',
                    group: 2,
                    title: listHelper.getAuthoredTitle(),
                    showTitle: true,
                    dropdownOptions: {
                        idAttribute: 'id',
                        markSelected: false
                    },
                    dropdownItems: [
                        {
                            title: listHelper.translations.filterAll,
                            callback: closeAuthoredSelection.bind(this)
                        },
                        {
                            id: 'timescale',
                            title: listHelper.translations.filterByTimescale,
                            callback: gotoSlide.bind(this, 1, 'authored')
                        }
                    ]
                }
            },
            workflowStage: {
                options: {
                    icon: 'circle-o',
                    group: 2,
                    title: listHelper.getPublishedTitle(),
                    showTitle: true,
                    dropdownOptions: {
                        idAttribute: 'id',
                        markSelected: true,
                        changeButton: true
                    },
                    dropdownItems: [
                        {
                            title: listHelper.translations.filterAll,
                            marked: true,
                            callback: function() {
                                setWorkflowStage.call(this, null);
                            }.bind(this)
                        },
                        {
                            id: 'published',
                            title: listHelper.translations.published,
                            callback: function() {
                                setWorkflowStage.call(this, 'published');
                            }.bind(this)
                        },
                        {
                            id: 'test',
                            title: listHelper.translations.unpublished,
                            callback: function() {
                                setWorkflowStage.call(this, 'test');
                            }.bind(this)
                        }
                    ]
                }
            },
            filter: {
                options: {
                    icon: 'filter',
                    group: 2,
                    title: listHelper.getFilterTitle(),
                    showTitle: true,
                    dropdownOptions: {
                        idAttribute: 'id',
                        markSelected: true,
                        changeButton: false
                    },
                    dropdownItems: [
                        {
                            id: 'all',
                            title: listHelper.translations.filterAll,
                            marked: true,
                            callback: removeFilter.bind(this)
                        },
                        {
                            id: 'me',
                            title: listHelper.translations.filterMe,
                            callback: function() {
                                closeContactSelection.call(this, {contactItem: this.sandbox.sulu.user.contact}, 'me');
                            }.bind(this)
                        },
                        {
                            id: 'filterByAuthor',
                            title: listHelper.translations.filterByAuthor + ' ...',
                            callback: gotoSlide.bind(this, 2, 'contact')
                        },
                        {
                            divider: true
                        },
                        {
                            id: 'filterByCategory',
                            title: listHelper.translations.filterByCategory + ' ...',
                            callback: gotoSlide.bind(this, 3, 'category')
                        },
                        {
                            id: 'filterByTag',
                            title: listHelper.translations.filterByTag + ' ...',
                            callback: gotoSlide.bind(this, 4, 'tag')
                        },
                        {
                            id: 'filterByPage',
                            title: listHelper.translations.filterByPage + ' ...',
                            callback: gotoSlide.bind(this, 5, 'page')
                        }
                    ]
                }
            }
        });
    };

    return {
        /**
         * Create new filter-state instance.
         *
         * @param {Node} $el
         * @param {String} instanceName
         * @param {String} locale
         * @param {String} okClickNamespace
         * @returns {FilterState}
         */
        create: function($el, instanceName, locale, okClickNamespace) {
            return new FilterState($el, instanceName, locale, okClickNamespace);
        }
    };
});
