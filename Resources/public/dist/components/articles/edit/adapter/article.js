define(["underscore","services/suluarticle/article-manager","services/sulupreview/preview"],function(a,b,c){"use strict";return{getCopyLocaleUrl:function(a,c,d,e){return b.getCopyLocaleUrl(a,d,e)},startPreview:function(a,b){var d=c.initialize({});return d.start("Sulu\\Bundle\\ArticleBundle\\Document\\ArticleDocument",a.options.id,a.options.locale,b),d},destroyPreview:function(a){c.destroy(a)},beforeFormCreate:function(a){},prepareData:function(a){return a},save:function(c,d,e){return d.template=c.template,a.each(d,function(a,b){c.data[b]=a}),b.save(c.data,c.data.id,c.options.locale,e)}}});