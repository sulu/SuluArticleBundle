define(function(){"use strict";return{type:"seo-tab",parseData:function(a){return a.ext.seo},getUrl:function(){var a=this.options.data();return this.options.excerptUrlPrefix+a.route},save:function(a,b){var c=this.options.data();c.ext.seo=a,this.sandbox.util.save(this.options.url(),c.id?"PUT":"POST",c).then(function(a){this.data=a,this.sandbox.emit("sulu.tab.saved",a)}.bind(this))}}});