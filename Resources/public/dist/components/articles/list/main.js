define(["underscore"],function(a){"use strict";var b={options:{config:{}},templates:{list:['<div class="list-toolbar-container"></div>','<div class="list-info"></div>','<div class="datagrid-container"></div>','<div class="dialog"></div>'].join(""),draftIcon:'<span class="draft-icon" title="<%= title %>"/>',publishedIcon:'<span class="published-icon" title="<%= title %>"/>',route:["articles","<% if (!!type) { %>:<%=type%><% } %>","/<%=locale%>"].join("")},translations:{headline:"sulu_article.list.title",unpublished:"public.unpublished",publishedWithDraft:"public.published-with-draft"}};return{defaults:b,header:function(){var b,c=this.options.config.types,d=this.options.config.typeNames,e={icon:"plus-circle",title:"public.add-new"},f=!1,g=null;return 1===d.length?e.callback=function(){this.toAdd(d[0])}.bind(this):(e.dropdownItems=a.map(d,function(a){return{title:c[a].title,callback:function(){this.toAdd(a)}.bind(this)}}.bind(this)),b=[],this.options.config.displayTabAll===!0&&b.push({name:"public.all",key:null}),a.each(d,function(a){b.push({id:a,name:c[a].title,key:a}),a===this.options.type&&(g=c[a].title)}.bind(this)),f={componentOptions:{callback:this.typeChange.bind(this),preselector:"name",preselect:g},data:b}),{noBack:!0,tabs:f,toolbar:{buttons:{addArticle:{options:e},deleteSelected:{}},languageChanger:{data:this.options.config.languageChanger,preSelected:this.options.locale}}}},layout:{content:{width:"max"}},initialize:function(){this.render(),this.bindCustomEvents()},render:function(){this.$el.html(this.templates.list()),this.sandbox.sulu.initListToolbarAndList.call(this,"article","/admin/api/articles/fields",{el:this.$find(".list-toolbar-container"),instanceName:"articles",template:this.sandbox.sulu.buttons.get({settings:{options:{dropdownItems:[{type:"columnOptions"}]}}})},{el:this.sandbox.dom.find(".datagrid-container"),url:"/admin/api/articles?sortBy=authored&sortOrder=desc&locale="+this.options.locale+(this.options.type?"&type="+this.options.type:""),searchInstanceName:"articles",searchFields:["title"],resultKey:"articles",idKey:"uuid",instanceName:"articles",actionCallback:function(a){this.toEdit(a)}.bind(this),viewOptions:{table:{actionIconColumn:"title",badges:[{column:"title",callback:function(a,b){return!(!a.localizationState||"ghost"!==a.localizationState.state||a.localizationState.locale===this.options.language)&&(b.title=a.localizationState.locale,b)}.bind(this)},{column:"title",callback:function(a,b){var c="",d=this.translations.unpublished;return a.published&&!a.publishedState&&(d=this.translations.publishedWithDraft,c+=this.templates.publishedIcon({title:d})),a.publishedState||(c+=this.templates.draftIcon({title:d})),b.title=c,b.cssClass="badge-none",b}.bind(this)}]}}})},toEdit:function(a,b){this.sandbox.emit("sulu.router.navigate","articles/"+(b||this.options.locale)+"/edit:"+a+"/details")},toAdd:function(a,b){this.sandbox.emit("sulu.router.navigate","articles/"+(b||this.options.locale)+"/add"+(this.options.config.typeNames.length>1?":"+a:""))},toList:function(a){1!==this.options.config.typeNames.length&&this.options.type?this.sandbox.emit("sulu.router.navigate","articles:"+this.options.type+"/"+(a||this.options.locale)):this.sandbox.emit("sulu.router.navigate","articles/"+(a||this.options.locale))},deleteItems:function(b){this.sandbox.util.save("/admin/api/articles?ids="+b.join(","),"DELETE").then(function(){a.each(b,function(a){this.sandbox.emit("husky.datagrid.articles.record.remove",a)}.bind(this))}.bind(this))},typeChange:function(a){var b=this.templates.route({type:a.key,locale:this.options.locale});this.options.type=a.key,this.sandbox.emit("husky.datagrid.articles.url.update",{type:a.key}),this.sandbox.emit("sulu.router.navigate",b,!1,!1)},bindCustomEvents:function(){this.sandbox.on("husky.datagrid.articles.number.selections",function(a){var b=a>0?"enable":"disable";this.sandbox.emit("sulu.header.toolbar.item."+b,"deleteSelected",!1)}.bind(this)),this.sandbox.on("sulu.toolbar.delete",function(){this.sandbox.emit("husky.datagrid.articles.items.get-selected",this.deleteItems.bind(this))}.bind(this)),this.sandbox.on("sulu.header.language-changed",function(a){this.sandbox.sulu.saveUserSetting(this.options.config.settingsKey,a.id),this.toList(a.id)}.bind(this))}}});