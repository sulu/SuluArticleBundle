define(["underscore","config","services/suluarticle/article-manager","services/sulupreview/preview"],function(a,b,c,d){"use strict";var e=function(a,b){return!a||!b._embedded||b._embedded.pages.length<=a-2?{}:b._embedded.pages[a-2]};return{startPreview:function(a,c){var f=e(a.options.page,c);if(f.id){f.type&&"ghost"===f.type.name&&(f={id:f.id});var g=d.initialize(b.get("sulu_security.contexts")["sulu.modules.articles"]);return g.start("Sulu\\Bundle\\ArticleBundle\\Document\\ArticlePageDocument",f.id,a.options.locale,f),g}},destroyPreview:function(a){d.destroy(a)},beforeFormCreate:function(a){var b=a.getTitleProperty(),c=a.getRoutePathProperty();b&&b.$el.closest(".grid-row").remove(),c&&c.$el.closest(".grid-row").remove();var d=a.$el.find(".section-highlight");0===a.$el.find(".highlight-section *[data-mapper-property]").length&&(a.$el.find(".highlight-section").remove(),d=a.$el.find("#content-form")),d.prepend('<div class="sulu-title article-title"><div class="content-title underlined"><h2>'+a.data.title+"</h2></div></div>")},prepareData:function(a,b){return e(b.options.page,a)},save:function(a,b,d){var e=null,f=a.options.page-2;return a.data._embedded.pages[f]&&(e=a.data._embedded.pages[f].id),b.template=a.template,c.savePage(b,a.data.id,e,a.options.locale,d).then(function(){return c.loadSync(a.data.id,a.options.locale).responseJSON})}}});