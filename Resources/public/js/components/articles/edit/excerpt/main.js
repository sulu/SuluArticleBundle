define([], function() {

    'use strict';

    return {

        type: 'excerpt-tab',

        parseData: function(data) {
            return data.ext.excerpt;
        },

        getTemplate: function() {
            return 'text!/admin/content/template/form/excerpt.html?language=' + this.options.locale;
        },

        save: function(data, action) {
            var content = this.options.data();
            content.ext.excerpt = data;

            this.sandbox.util.save(this.options.url(), !content.id ? 'POST' : 'PUT', content).then(function(data) {
                this.data = data;
                this.sandbox.emit('sulu.tab.saved', data);
            }.bind(this));
        }
    };
});
