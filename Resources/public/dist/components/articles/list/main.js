define(["underscore","services/husky/storage","sulucontent/components/copy-locale-overlay/main","sulucontent/components/open-ghost-overlay/main","services/suluarticle/article-manager"],function(a,b,c,d,e){"use strict";var f={options:{config:{},storageName:"articles"},templates:{list:['<div class="list-toolbar-container"></div>','<div class="list-info"></div>','<div class="datagrid-container"></div>','<div class="dialog"></div>'].join(""),draftIcon:'<span class="draft-icon" title="<%= title %>"/>',publishedIcon:'<span class="published-icon" title="<%= title %>"/>',route:["articles","<% if (!!type) { %>:<%=type%><% } %>","/<%=locale%>"].join("")},translations:{headline:"sulu_article.list.title",unpublished:"public.unpublished",publishedWithDraft:"public.published-with-draft",filter:"sulu_article.list.filter",filterMe:"sulu_article.list.filter.me",filterAll:"sulu_article.list.filter.all",openGhostOverlay:{info:"sulu_article.settings.open-ghost-overlay.info","new":"sulu_article.settings.open-ghost-overlay.new",copy:"sulu_article.settings.open-ghost-overlay.copy",ok:"sulu_article.settings.open-ghost-overlay.ok"}}};return{defaults:f,header:function(){this.storage=b.get("sulu",this.options.storageName);var c,d=this.options.config.types,e=this.options.config.typeNames,f={icon:"plus-circle",title:"public.add-new"},g=!1,h=null,i=this.options.type||this.storage.getWithDefault("type",null);return 1===e.length?f.callback=function(){this.toAdd(e[0])}.bind(this):(f.dropdownItems=a.map(e,function(a){return{title:d[a].title,callback:function(){this.toAdd(a)}.bind(this)}}.bind(this)),c=[],this.options.config.displayTabAll===!0&&c.push({name:"public.all",key:null}),a.each(e,function(a){c.push({id:a,name:d[a].title,key:a}),a===i&&(h=d[a].title)}.bind(this)),g={componentOptions:{callback:this.typeChange.bind(this),preselector:"name",preselect:h},data:c}),{noBack:!0,tabs:g,toolbar:{buttons:{addArticle:{options:f},deleteSelected:{}},languageChanger:{data:this.options.config.languageChanger,preSelected:this.options.locale}}}},layout:{content:{width:"max"}},initialize:function(){if(this.options.type)this.storage.set("type",this.options.type);else if(this.storage.has("type")){var a=this.templates.route({type:this.storage.get("type"),locale:this.options.locale});this.sandbox.emit("sulu.router.navigate",a,!1,!1),this.options.type=this.storage.get("type")}this.render(),this.bindCustomEvents()},render:function(){this.$el.html(this.templates.list()),this.sandbox.sulu.initListToolbarAndList.call(this,"article","/admin/api/articles/fields",{el:this.$find(".list-toolbar-container"),instanceName:"articles",template:this.retrieveListToolbarTemplate()},{el:this.sandbox.dom.find(".datagrid-container"),url:"/admin/api/articles?sortBy=authored&sortOrder=desc&locale="+this.options.locale+(this.options.type?"&type="+this.options.type:""),storageName:this.options.storageName,searchInstanceName:"articles",searchFields:["title"],resultKey:"articles",idKey:"uuid",instanceName:"articles",actionCallback:function(a,b){"ghost"===b.localizationState.state?e.load(a,this.options.locale).then(function(b){d.openGhost.call(this,b,this.translations.openGhostOverlay).then(function(b,d){b?c.copyLocale.call(this,a,d,[this.options.locale],function(){this.toEdit(a)}.bind(this)):this.toEdit(a)}.bind(this))}.bind(this)).fail(function(a){this.sandbox.emit("sulu.article.error",a.status,data)}.bind(this)):this.toEdit(a)}.bind(this),viewOptions:{table:{actionIconColumn:"title",badges:[{column:"title",callback:function(a,b){return!(!a.localizationState||"ghost"!==a.localizationState.state||a.localizationState.locale===this.options.locale)&&(b.title=a.localizationState.locale,b)}.bind(this)},{column:"title",callback:function(a,b){var c="",d=this.translations.unpublished;return a.published&&!a.publishedState&&(d=this.translations.publishedWithDraft,c+=this.templates.publishedIcon({title:d})),a.publishedState||(c+=this.templates.draftIcon({title:d})),b.title=c,b.cssClass="badge-none",b}.bind(this)}]}}})},toEdit:function(a,b){this.sandbox.emit("sulu.router.navigate","articles/"+(b||this.options.locale)+"/edit:"+a+"/details")},toAdd:function(a,b){this.sandbox.emit("sulu.router.navigate","articles/"+(b||this.options.locale)+"/add"+(this.options.config.typeNames.length>1?":"+a:""))},toList:function(a){1!==this.options.config.typeNames.length&&this.options.type?this.sandbox.emit("sulu.router.navigate","articles:"+this.options.type+"/"+(a||this.options.locale)):this.sandbox.emit("sulu.router.navigate","articles/"+(a||this.options.locale))},deleteItems:function(b){this.sandbox.util.save("/admin/api/articles?ids="+b.join(","),"DELETE").then(function(){a.each(b,function(a){this.sandbox.emit("husky.datagrid.articles.record.remove",a)}.bind(this))}.bind(this))},typeChange:function(a){var b=this.templates.route({type:a.key,locale:this.options.locale});this.options.type=a.key,this.sandbox.emit("husky.datagrid.articles.url.update",{page:1,type:a.key}),this.sandbox.emit("sulu.router.navigate",b,!1,!1),this.storage.set("type",a.key)},getCopyLocaleUrl:function(a,b,c){return e.getCopyLocaleUrl(a,b,c)},bindCustomEvents:function(){this.sandbox.on("husky.datagrid.articles.number.selections",function(a){var b=a>0?"enable":"disable";this.sandbox.emit("sulu.header.toolbar.item."+b,"deleteSelected",!1)}.bind(this)),this.sandbox.on("sulu.toolbar.delete",function(){this.sandbox.emit("husky.datagrid.articles.items.get-selected",this.deleteItems.bind(this))}.bind(this)),this.sandbox.on("sulu.header.language-changed",function(a){a.id!==this.options.locale&&(this.sandbox.sulu.saveUserSetting(this.options.config.settingsKey,a.id),this.toList(a.id))}.bind(this))},retrieveListToolbarTemplate:function(){var a=this.sandbox.sulu.buttons.get({settings:{options:{dropdownItems:[{type:"columnOptions"}]}}});return a.push({id:"filter",icon:"filter",title:this.translations.filter,group:2,dropdownOptions:{url:"/admin/api/users",resultKey:"users",titleAttribute:"fullName",idAttribute:"id",markSelected:!0,preSelected:this.filter?parseInt(this.filter.id):null,callback:function(a){this.applyFilterToList.call(this,a)}.bind(this)},dropdownItems:[{divider:!0},{id:"me",fullName:this.translations.filterMe},{id:"all",fullName:this.translations.filterAll}]}),a},applyFilterToList:function(a){var b=null;a.id&&"me"===a.id?b=this.sandbox.sulu.user.fullName:a.id&&"all"===a.id?b=null:a.fullName&&(b=a.fullName),this.sandbox.emit("husky.datagrid.articles.url.update",{filter:b})}}});