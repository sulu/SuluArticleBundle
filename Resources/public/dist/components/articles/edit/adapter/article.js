define(["underscore","config","services/suluarticle/article-manager","services/sulupreview/preview"],function(a,b,c,d){"use strict";var e=b.get("sulu_article");return{startPreview:function(a,c){var f=c;f.type&&"ghost"===f.type.name&&(f={id:f.id});var g=d.initialize(b.get("sulu_security.contexts")["sulu.modules.articles"]);return g.start(e.classes.article,f.id,a.options.locale,f),g},destroyPreview:function(a){d.destroy(a)},beforeFormCreate:function(a){},prepareData:function(a){return a},save:function(b,d,e){return d.template=b.template,a.each(d,function(a,c){b.data[c]=a}),b.data.type&&"ghost"===b.data.type.name&&delete b.data._hash,c.save(b.data,b.data.id,b.options.locale,e)}}});