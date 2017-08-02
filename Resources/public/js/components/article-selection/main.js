/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

/**
 * Article-Selection content type.
 *
 * Allows selection of multiple articles.
 */
define([
    'underscore',
    'config',
    'services/suluwebsite/reference-store',
    'text!./overlay.html',
    'text!./contentItem.html',
    'text!/admin/api/articles/fields'
], function(_, Config, referenceStore, overlayTemplate, contentItemTemplate, fieldsResponse) {

    'use strict';

    var fields = JSON.parse(fieldsResponse),

        config = Config.get('sulu_article'),

        defaults = {
            options: {
                url: '/admin/api/articles',
                eventNamespace: 'sulu.article-selection',
                resultKey: 'articles',
                dataAttribute: 'article-selection',
                dataDefault: [],
                hidePositionElement: true,
                hideConfigButton: true,
                navigateEvent: 'sulu.router.navigate',
                translations: {
                    noContentSelected: 'sulu_article.selection.no-result'
                }
            },

            templates: {
                overlay: overlayTemplate,
                contentItem: contentItemTemplate
            },

            translations: {
                title: 'public.title',
                overlayTitle: 'sulu_article.title'
            }
        },

        /**
         * Handles aura-events.
         */
        bindCustomEvents = function() {
            this.sandbox.on(
                'husky.overlay.article-selection.' + this.options.instanceName + '.add.initialized',
                initializeList.bind(this)
            );

            this.sandbox.on(
                'husky.overlay.article-selection.' + this.options.instanceName + '.add.opened',
                updateList.bind(this)
            );

            // adjust position of overlay after column-navigation has initialized
            this.sandbox.on('husky.datagrid.article.view.rendered', function() {
                this.sandbox.emit('husky.overlay.article-selection.' + this.options.instanceName + '.add.set-position');
            }.bind(this));

            this.sandbox.on('husky.tabs.overlayarticle-selection.' + this.options.instanceName + '.add.item.select', typeChange.bind(this));
        },

        /**
         * Initializes list in the overlay
         */
        initializeList = function() {
            this.sandbox.start([
                {
                    name: 'search@husky',
                    options: {
                        appearance: 'white small',
                        instanceName: this.options.instanceName + '-article-search',
                        el: '#article-selection-' + this.options.instanceName + '-search'
                    }
                },
                {
                    name: 'datagrid@husky',
                    options: {
                        el: '#article-selection-' + this.options.instanceName + '-list',
                        instanceName: this.options.instanceName,
                        url: this.url,
                        preselected: this.getData() || [],
                        resultKey: this.options.resultKey,
                        sortable: false,
                        columnOptionsInstanceName: '',
                        clickCallback: function(item) {
                            this.sandbox.emit('husky.datagrid.' + this.options.instanceName + '.toggle.item', item);
                        }.bind(this),
                        selectedCounter: true,
                        searchInstanceName: this.options.instanceName + '-article-search',
                        searchFields: ['id', 'title'],
                        paginationOptions: {
                            dropdown: {
                                limit: 20
                            }
                        },
                        matchings: fields
                    }
                }
            ]);
        },

        /**
         * Updates the datagrid when opening the overlay again
         */
        updateList = function() {
            var data = this.getData() || [];

            this.sandbox.emit('husky.datagrid.' + this.options.instanceName + '.selected.update', data);
        },

        /**
         * Handle dom events
         */
        bindDomEvents = function() {
            this.sandbox.dom.on(this.$el, 'click', function() {
                return false;
            }.bind(this), '.search-icon');

            this.sandbox.dom.on(this.$el, 'keydown', function(e) {
                if (event.keyCode === 13) {
                    e.preventDefault();
                    e.stopPropagation();

                    return false;
                }
            }.bind(this), '.search-input');
        },

        /**
         * Starts the overlay component
         */
        startOverlay = function() {
            var $element = this.sandbox.dom.createElement('<div/>'),
                $data = $(this.templates.overlay({instanceName: this.options.instanceName})),
                types = config.types,
                typeNames = config.typeNames,
                tabs = false;
            this.sandbox.dom.append(this.$el, $element);

            // load all for default
            var delimiter = (this.options.url.indexOf('?') === -1) ? '?' : '&';
            this.url = this.options.url + delimiter + 'locale=' + this.options.locale;

            if (1 !== typeNames.length) {
                tabs = [];
                if (config.displayTabAll === true) {
                    tabs.push(
                        {
                            title: 'public.all',
                            key: null,
                            data: $data
                        }
                    );
                } else {
                    // if not all tab is first load only for the first type
                    this.url = this.options.url + delimiter + 'locale=' + this.options.locale + '&type=' + typeNames[0];
                }

                // add tab item for each type
                _.each(typeNames, function(type) {
                    tabs.push(
                        {
                            title: types[type].title,
                            data: $data
                        }
                    );
                }.bind(this));

                $data = null;
            }

            this.sandbox.start([
                {
                    name: 'overlay@husky',
                    options: {
                        triggerEl: this.$addButton,
                        cssClass: 'article-content-overlay',
                        el: $element,
                        removeOnClose: false,
                        container: this.$el,
                        instanceName: 'article-selection.' + this.options.instanceName + '.add',
                        skin: 'large',
                        okCallback: getAddOverlayData.bind(this),
                        title: this.translations.overlayTitle,
                        tabs: tabs,
                        data: $data
                    }
                }
            ]);
        },

        /**
         * Retrieve data from datagrid and keep sorting of ids.
         */
        getAddOverlayData = function() {
            var data = [],
                oldData = this.getData();

            this.sandbox.emit('husky.datagrid.' + this.options.instanceName + '.items.get-selected', function(selected) {
                this.sandbox.util.foreach(selected, function(item) {
                    var index = oldData.indexOf(item);

                    if (index !== -1) {
                        data[index] = item;
                    } else {
                        data.push(item);
                    }
                }.bind(this));
            }.bind(this));

            var keys = Object.keys(data),
                result = [],
                i, len = keys.length;

            for (i = 0; i < len; i++) {
                result.push(data[keys[i]]);
            }

            this.setData(result);
        },

        typeChange = function(item) {
            this.type = null;

            if (item.name) {
                for (var type in config.types) {
                    if (config.types.hasOwnProperty(type) && config.types[type].title === item.name) {
                        this.type = type;

                        break;
                    }
                }
            }

            this.sandbox.emit('husky.datagrid.' + this.options.instanceName + '.url.update', {type: this.type});
        };

    return {

        defaults: defaults,

        type: 'itembox',

        initialize: function() {
            // sandbox event handling
            bindCustomEvents.call(this);
            this.prefillReferenceStore();

            this.render();

            // init overlays
            startOverlay.call(this);

            // handle dom events
            bindDomEvents.call(this);
        },

        getUrl: function(data) {
            var delimiter = (this.options.url.indexOf('?') === -1) ? '?' : '&';

            return [
                this.options.url,
                delimiter,
                'locale=' + this.options.locale,
                '&', this.options.idsParameter, '=', (data || []).join(',')
            ].join('');
        },

        getItemContent: function(item) {
            return this.templates.contentItem({item: item, locale: this.options.locale});
        },

        sortHandler: function(ids) {
            this.setData(ids, false);
        },

        removeHandler: function(id) {
            var data = this.getData();
            for (var i = -1, length = data.length; ++i < length;) {
                if (id === data[i]) {
                    data.splice(i, 1);
                    break;
                }
            }

            this.setData(data, false);
        },

        prefillReferenceStore: function() {
            var data = this.getData();
            for (var key in data) {
                if (data.hasOwnProperty(key)) {
                    referenceStore.add('article', data[key]);
                }
            }
        }
    };
});
