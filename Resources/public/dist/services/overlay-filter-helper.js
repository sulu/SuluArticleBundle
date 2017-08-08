define(["services/suluarticle/list-helper","services/husky/mediator"],function(a,b){"use strict";function c(a,b,c,d){this.$el=a,this.instanceName=b,this.locale=c,this.okClickNamespace=d}var d=function(a,c){b.emit("husky.toolbar."+this.instanceName+".button.set",a,{title:c})},e=function(a,c){b.emit("husky.overlay."+this.instanceName+".slide-to",a),b.once(this.okClickNamespace+"."+this.instanceName+".ok-button.clicked",function(){b.emit("sulu_article."+c+"-selection.form.get")}.bind(this))},f=function(c){b.emit("husky.datagrid."+this.instanceName+".url.update",{authoredFrom:c?c.from:null,authoredTo:c?c.to:null}),d.call(this,"authoredDate",a.getAuthoredTitle(c)),b.emit("husky.overlay."+this.instanceName+".slide-to",0)},g=function(c,e){var f={filterKey:e||"filterByAuthor",contact:c.contactItem};d.call(this,"filter",a.getFilterTitle(f)),b.emit("husky.datagrid."+this.instanceName+".url.update",m(f)),b.emit("husky.overlay."+this.instanceName+".slide-to",0)},h=function(c){var e={filterKey:"filterByCategory",category:c.categoryItem};d.call(this,"filter",a.getFilterTitle(e)),b.emit("husky.datagrid."+this.instanceName+".url.update",m(e)),b.emit("husky.overlay."+this.instanceName+".slide-to",0)},i=function(c){var e={filterKey:"filterByTag",tag:c.tagItem};d.call(this,"filter",a.getFilterTitle(e)),b.emit("husky.datagrid."+this.instanceName+".url.update",m(e)),b.emit("husky.overlay."+this.instanceName+".slide-to",0)},j=function(c){var e={filterKey:"filterByPage",page:c.pageItem};d.call(this,"filter",a.getFilterTitle(e)),b.emit("husky.datagrid."+this.instanceName+".url.update",m(e)),b.emit("husky.overlay."+this.instanceName+".slide-to",0)},k=function(){d.call(this,"filter",a.getFilterTitle()),b.emit("husky.datagrid."+this.instanceName+".url.update",m({}))},l=function(a){b.emit("husky.datagrid."+this.instanceName+".url.update",{workflowStage:a})},m=function(a){return{contactId:a.contact?a.contact.id:null,categoryId:a.category?a.category.id:null,tagId:a.tag?a.tag.id:null,pageId:a.page?a.page.id:null}};return c.prototype.startFilterComponents=function(a){a.start([{name:"articles/list/authored-selection/form@suluarticle",options:{el:".slide.authored-slide .overlay-content",selectCallback:f.bind(this)}},{name:"articles/list/contact-selection/form@suluarticle",options:{el:".slide.contact-slide .overlay-content",selectCallback:g.bind(this)}},{name:"articles/list/category-selection/form@suluarticle",options:{el:".slide.category-slide .overlay-content",locale:this.locale,selectCallback:h.bind(this)}},{name:"articles/list/tag-selection/form@suluarticle",options:{el:".slide.tag-slide .overlay-content",selectCallback:i.bind(this)}},{name:"articles/list/page-selection/form@suluarticle",options:{el:".slide.page-slide .overlay-content",locale:this.locale,selectCallback:j.bind(this)}}])},c.prototype.createToolbarTemplate=function(b){return b.sulu.buttons.get({authoredDate:{options:{icon:"calendar",group:2,title:a.getAuthoredTitle(),showTitle:!0,dropdownOptions:{idAttribute:"id",markSelected:!1},dropdownItems:[{title:a.translations.filterAll,callback:f.bind(this)},{id:"timescale",title:a.translations.filterByTimescale,callback:e.bind(this,1,"authored")}]}},workflowStage:{options:{icon:"circle-o",group:2,title:a.getPublishedTitle(),showTitle:!0,dropdownOptions:{idAttribute:"id",markSelected:!0,changeButton:!0},dropdownItems:[{title:a.translations.filterAll,marked:!0,callback:function(){l.call(this,null)}.bind(this)},{id:"published",title:a.translations.published,callback:function(){l.call(this,"published")}.bind(this)},{id:"test",title:a.translations.unpublished,callback:function(){l.call(this,"test")}.bind(this)}]}},filter:{options:{icon:"filter",group:2,title:a.getFilterTitle(),showTitle:!0,dropdownOptions:{idAttribute:"id",markSelected:!0,changeButton:!1},dropdownItems:[{id:"all",title:a.translations.filterAll,marked:!0,callback:k.bind(this)},{id:"me",title:a.translations.filterMe,callback:function(){g.call(this,{contactItem:b.sulu.user.contact},"me")}.bind(this)},{id:"filterByAuthor",title:a.translations.filterByAuthor+" ...",callback:e.bind(this,2,"contact")},{divider:!0},{id:"filterByCategory",title:a.translations.filterByCategory+" ...",callback:e.bind(this,3,"category")},{id:"filterByTag",title:a.translations.filterByTag+" ...",callback:e.bind(this,4,"tag")},{id:"filterByPage",title:a.translations.filterByPage+" ...",callback:e.bind(this,5,"page")}]}}})},{create:function(a,b,d,e){return new c(a,b,d,e)}}});