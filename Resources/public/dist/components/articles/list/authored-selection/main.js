define(["jquery","text!./form.html"],function(a,b){"use strict";var c={options:{data:{from:null,to:null},selectCallback:function(a){}},translations:{title:"sulu_article.authored",from:"sulu_article.authored-selection-overlay.from",to:"sulu_article.authored-selection-overlay.to",reset:"smart-content.choose-data-source.reset"},templates:{skeleton:b}};return{defaults:c,initialize:function(){this.$overlayContainer=a("<div/>"),this.$el.append(this.$overlayContainer),this.data=this.options.data,this.sandbox.start([{name:"overlay@husky",options:{el:this.$overlayContainer,instanceName:"authored-selection",openOnStart:!0,removeOnClose:!0,skin:"medium",slides:[{title:this.translations.title,data:a(this.templates.skeleton({translations:this.translations})),okCallback:this.okCallbackOverlay.bind(this),buttons:[{type:"cancel",align:"left"},{classes:"just-text",text:this.translations.reset,align:"center",callback:function(){this.options.selectCallback({from:null,to:null}),this.sandbox.stop()}.bind(this)},{type:"ok",align:"right"}]}]}}]),this.sandbox.once("husky.overlay.authored-selection.opened",function(){this.sandbox.form.create(this.$overlayContainer).initialized.then(function(){this.sandbox.form.setData(this.$overlayContainer,this.options.data).then(function(){this.sandbox.start(this.$overlayContainer)}.bind(this))}.bind(this))}.bind(this))},okCallbackOverlay:function(){this.sandbox.form.validate(this.$overlayContainer)&&(this.options.selectCallback(this.sandbox.form.getData(this.$overlayContainer)),this.sandbox.stop())}}});