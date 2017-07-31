define(["underscore","services/husky/storage","sulucontent/components/copy-locale-overlay/main","sulucontent/components/open-ghost-overlay/main","services/suluarticle/article-manager","services/suluarticle/article-router"],function(a,b,c,d,e,f){"use strict";var g={options:{config:{},storageName:"articles"},templates:{list:['<div class="list-toolbar-container"></div>','<div class="list-info"></div>','<div class="datagrid-container"></div>','<div class="dialog"></div>'].join(""),draftIcon:'<span class="draft-icon" title="<%= title %>"/>',publishedIcon:'<span class="published-icon" title="<%= title %>"/>',route:["articles","<% if (!!type) { %>:<%=type%><% } %>","/<%=locale%>"].join("")},translations:{headline:"sulu_article.list.title",published:"public.published",unpublished:"public.unpublished",publishedWithDraft:"public.published-with-draft",filterMe:"sulu_article.list.filter.me",filterAll:"sulu_article.list.filter.all",filterByAuthor:"sulu_article.list.filter.by-author",filterByCategory:"sulu_article.list.filter.by-category",filterByTag:"sulu_article.list.filter.by-tag",filterByPage:"sulu_article.list.filter.by-page",filterByTimescale:"sulu_article.list.filter.by-timescale",from:"sulu_article.authored-selection-overlay.from",to:"sulu_article.authored-selection-overlay.to",openGhostOverlay:{info:"sulu_article.settings.open-ghost-overlay.info","new":"sulu_article.settings.open-ghost-overlay.new",copy:"sulu_article.settings.open-ghost-overlay.copy",ok:"sulu_article.settings.open-ghost-overlay.ok"}}};return{defaults:g,data:{contactId:null},header:function(){this.storage=b.get("sulu",this.options.storageName);var c,d=this.options.config.types,e=this.options.config.typeNames,f={icon:"plus-circle",title:"public.add-new"},g=!1,h=null,i=this.options.type||this.storage.getWithDefault("type",null);return 1===e.length?f.callback=function(){this.toAdd(e[0])}.bind(this):(f.dropdownItems=a.map(e,function(a){return{title:d[a].title,callback:function(){this.toAdd(a)}.bind(this)}}.bind(this)),c=[],this.options.config.displayTabAll===!0&&c.push({name:"public.all",key:null}),a.each(e,function(a){c.push({id:a,name:d[a].title,key:a}),a===i&&(h=d[a].title)}.bind(this)),g={componentOptions:{callback:this.typeChange.bind(this),preselector:"name",preselect:h},data:c}),{noBack:!0,tabs:g,toolbar:{buttons:{addArticle:{options:f},deleteSelected:{}},languageChanger:{data:this.options.config.languageChanger,preSelected:this.options.locale}}}},layout:{content:{width:"max"}},initialize:function(){this.options.type?this.storage.set("type",this.options.type):this.storage.has("type")&&(f.toList(this.options.locale,this.storage.get("type")),this.options.type=this.storage.get("type")),this.render(),this.bindCustomEvents()},render:function(){this.$el.html(this.templates.list());var a="/admin/api/articles?sortBy=authored&sortOrder=desc&locale="+this.options.locale+(this.options.type?"&type="+this.options.type:""),b=this.retrieveListToolbarTemplate(this.loadFilterFromStorage());this.sandbox.sulu.initListToolbarAndList.call(this,"article","/admin/api/articles/fields",{el:this.$find(".list-toolbar-container"),instanceName:"articles",template:b},{el:this.sandbox.dom.find(".datagrid-container"),url:a,storageName:this.options.storageName,searchInstanceName:"articles",searchFields:["title","route_path","changer_full_name","creator_full_name","pages.title"],resultKey:"articles",instanceName:"articles",actionCallback:function(a,b){"ghost"===b.localizationState.state?e.load(a,this.options.locale).then(function(b){d.openGhost.call(this,b,this.translations.openGhostOverlay).then(function(b,d){b?c.copyLocale.call(this,a,d,[this.options.locale],function(){this.toEdit(a)}.bind(this)):this.toEdit(a)}.bind(this))}.bind(this)).fail(function(a){this.sandbox.emit("sulu.article.error",a.status,a.responseJSON.code||0,data)}.bind(this)):this.toEdit(a)}.bind(this),viewOptions:{table:{actionIconColumn:"title",badges:[{column:"title",callback:function(a,b){return!(!a.localizationState||"ghost"!==a.localizationState.state||a.localizationState.locale===this.options.locale)&&(b.title=a.localizationState.locale,b)}.bind(this)},{column:"title",callback:function(a,b){var c="",d=this.translations.unpublished;return a.published&&!a.publishedState&&(d=this.translations.publishedWithDraft,c+=this.templates.publishedIcon({title:d})),a.publishedState||(c+=this.templates.draftIcon({title:d})),b.title=c,b.cssClass="badge-none",b}.bind(this)}]}}})},toEdit:function(a,b){f.toEdit(a,b||this.options.locale)},toAdd:function(a,b){f.toAdd(b||this.options.locale,a)},toList:function(a){f.toList(a||this.options.locale,this.options.type)},deleteItems:function(b){e.remove(b,this.options.locale).then(function(){a.each(b,function(a){this.sandbox.emit("husky.datagrid.articles.record.remove",a)}.bind(this))}.bind(this))},typeChange:function(a){this.options.type=a.key,this.sandbox.emit("husky.datagrid.articles.url.update",{page:1,type:this.options.type}),f.toList(this.options.locale,this.options.type,!1,!1),this.storage.set("type",this.options.type)},getCopyLocaleUrl:function(a,b,c){return e.getCopyLocaleUrl(a,b,c)},bindCustomEvents:function(){this.sandbox.on("husky.datagrid.articles.number.selections",function(a){var b=a>0?"enable":"disable";this.sandbox.emit("sulu.header.toolbar.item."+b,"deleteSelected",!1)}.bind(this)),this.sandbox.on("sulu.toolbar.delete",function(){this.sandbox.emit("husky.datagrid.articles.items.get-selected",this.deleteItems.bind(this))}.bind(this)),this.sandbox.on("sulu.header.language-changed",function(a){a.id!==this.options.locale&&(this.sandbox.sulu.saveUserSetting(this.options.config.settingsKey,a.id),this.toList(a.id))}.bind(this)),this.sandbox.on("husky.toolbar.articles.initialized",function(){this.sandbox.emit("husky.toolbar.articles.item.mark",this.loadFilterFromStorage().filterKey)}.bind(this))},retrieveListToolbarTemplate:function(a){return this.sandbox.sulu.buttons.get({settings:{options:{dropdownItems:[{type:"columnOptions"}]}},authoredDate:{options:{icon:"calendar",group:2,title:this.getAuthoredTitle(a),showTitle:!0,dropdownOptions:{idAttribute:"id",markSelected:!1},dropdownItems:[{title:this.translations.filterAll,callback:function(){var a=this.appendFilter("authored",{from:null,to:null});this.sandbox.emit("husky.toolbar.articles.button.set","authoredDate",{title:this.getAuthoredTitle(a)})}.bind(this)},{id:"timescale",title:this.translations.filterByTimescale,callback:this.openAuthoredSelectionOverlay.bind(this)}]}},workflowStage:{options:{icon:"circle-o",group:2,title:this.getPublishedTitle(a),showTitle:!0,dropdownOptions:{idAttribute:"id",markSelected:!0,changeButton:!0},dropdownItems:[{title:this.translations.filterAll,marked:!a.workflowStage,callback:function(){this.appendFilter("workflowStage",null)}.bind(this)},{id:"published",title:this.translations.published,marked:"published"===a.workflowStage,callback:function(){this.appendFilter("workflowStage","published")}.bind(this)},{id:"test",title:this.translations.unpublished,marked:"test"===a.workflowStage,callback:function(){this.appendFilter("workflowStage","test")}.bind(this)}]}},filter:{options:{icon:"filter",group:2,title:this.getFilterTitle(a),showTitle:!0,dropdownOptions:{idAttribute:"id",markSelected:!0,changeButton:!1,preSelected:a.filterKey},dropdownItems:[{id:"all",title:this.translations.filterAll,marked:"all"===a.filterKey,callback:function(){this.replaceFilter("all")}.bind(this)},{id:"me",title:this.translations.filterMe,marked:"me"===a.filterKey,callback:function(){this.replaceFilter("contact",this.sandbox.sulu.user.contact,"me")}.bind(this)},{id:"filterByAuthor",title:this.translations.filterByAuthor+" ...",marked:"filterByAuthor"===a.filterKey,callback:this.openContactSelectionOverlay.bind(this)},{divider:!0},{id:"filterByCategory",title:this.translations.filterByCategory+" ...",marked:"filterByCategory"===a.filterKey,callback:this.openCategorySelectionOverlay.bind(this)},{id:"filterByTag",title:this.translations.filterByTag+" ...",marked:"filterByTag"===a.filterKey,callback:this.openTagSelectionOverlay.bind(this)},{id:"filterByPage",title:this.translations.filterByPage+" ...",marked:"filterByPage"===a.filterKey,callback:this.openPageSelectionOverlay.bind(this)}]}}})},openContactSelectionOverlay:function(){var a=$("<div/>");this.$el.append(a),this.sandbox.start([{name:"articles/list/contact-selection@suluarticle",options:{el:a,locale:this.options.locale,data:{contact:this.loadFilterFromStorage().contact},selectCallback:function(a){this.replaceFilter("contact",a.contactItem,"filterByAuthor")}.bind(this)}}])},openCategorySelectionOverlay:function(){var a=$("<div/>");this.$el.append(a),this.sandbox.start([{name:"articles/list/category-selection@suluarticle",options:{el:a,locale:this.options.locale,data:{category:this.loadFilterFromStorage().category},selectCallback:function(a){this.replaceFilter("category",a.categoryItem,"filterByCategory")}.bind(this)}}])},openTagSelectionOverlay:function(){var a=$("<div/>");this.$el.append(a),this.sandbox.start([{name:"articles/list/tag-selection@suluarticle",options:{el:a,locale:this.options.locale,data:{tag:this.loadFilterFromStorage().tag},selectCallback:function(a){this.replaceFilter("tag",a.tagItem,"filterByTag")}.bind(this)}}])},openPageSelectionOverlay:function(){var a=$("<div/>");this.$el.append(a),this.sandbox.start([{name:"page-tree-route/page-select@suluarticle",options:{el:a,locale:this.options.locale,data:this.loadFilterFromStorage().page,translations:{overlayTitle:"sulu_article.page-selection-overlay.title"},selectCallback:function(a){this.replaceFilter("page",a,"filterByPage")}.bind(this)}}])},openAuthoredSelectionOverlay:function(){var a=$("<div/>");this.$el.append(a),this.sandbox.start([{name:"articles/list/authored-selection@suluarticle",options:{el:a,locale:this.options.locale,data:this.loadFilterFromStorage().authored,selectCallback:function(a){var b=this.appendFilter("authored",a);this.sandbox.emit("husky.toolbar.articles.button.set","authoredDate",{title:this.getAuthoredTitle(b)})}.bind(this)}}])},replaceFilter:function(a,b,c){var d=this.loadFilterFromStorage();return d.category=null,d.contact=null,d.tag=null,d.page=null,d.filterKey=c||a,b&&(d[a]=b),this.applyFilterToList(d)},appendFilter:function(a,b){var c=this.loadFilterFromStorage();return c[a]=b,this.applyFilterToList(c)},applyFilterToList:function(a){var b={contactId:a.contact?a.contact.id:null,categoryId:a.category?a.category.id:null,tagId:a.tag?a.tag.id:null,pageId:a.page?a.page.id:null,authoredFrom:a.authored?a.authored.from:null,authoredTo:a.authored?a.authored.to:null,workflowStage:a.workflowStage?a.workflowStage:null};return this.saveFilterToStorage(a),this.sandbox.emit("husky.datagrid.articles.url.update",b),this.sandbox.emit("husky.toolbar.articles.button.set","filter",{title:this.getFilterTitle(a)}),a},loadFilterFromStorage:function(){return this.storage.getWithDefault("filter",{filterKey:"all",contact:null,category:null,tag:null,authored:{from:null,to:null}})},saveFilterToStorage:function(a){this.storage.set("filter",a)},getFilterTitle:function(a){switch(a.filterKey){case"filterByAuthor":return this.translations.filterByAuthor+" "+a.contact.firstName+" "+a.contact.lastName;case"me":return this.translations.filterMe;case"filterByCategory":return this.translations.filterByCategory+" "+a.category.name;case"filterByTag":return this.translations.filterByTag+" "+a.tag.name;case"filterByPage":return this.translations.filterByPage+" "+a.page.title}return this.translations.filterAll},getPublishedTitle:function(a){return a.workflowStage?"published"===a.workflowStage?this.translations.published:this.translations.unpublished:this.translations.filterAll},getAuthoredTitle:function(a){if(!a.authored)return this.translation.filterAll;var b=[];return a.authored.from&&(b.push(this.translations.from),b.push(this.sandbox.date.format(a.authored.from+"T00:00"))),a.authored.to&&(b.push(b.length>0?this.translations.to.toLowerCase():this.translations.to),b.push(this.sandbox.date.format(a.authored.to+"T00:00"))),0===b.length?this.translations.filterAll:b.join(" ")}}});