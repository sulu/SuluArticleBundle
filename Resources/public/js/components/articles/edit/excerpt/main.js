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

            this.sandbox.emit('sulu.articles.save', data, action);
        }
    };
});
