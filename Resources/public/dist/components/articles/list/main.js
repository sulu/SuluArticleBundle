define(function(){"use strict";var a={options:{config:{}},templates:{list:['<div class="list-toolbar-container"></div>','<div class="list-info"></div>','<div class="datagrid-container"></div>','<div class="dialog"></div>'].join("")},translations:{headline:"sulu_article.list.title"}};return{defaults:a,header:function(){return{title:this.translations.headline,underline:!1,noBack:!0,toolbar:{buttons:{add:{},deleteSelected:{}},languageChanger:{data:this.options.config.languageChanger,preSelected:this.options.locale}}}},layout:{content:{width:"max"}},initialize:function(){this.render(),this.bindCustomEvents()},render:function(){this.$el.html(this.templates.list()),this.sandbox.sulu.initListToolbarAndList.call(this,"article","/admin/api/articles/fields",{el:this.$find(".list-toolbar-container"),instanceName:"articles",template:this.sandbox.sulu.buttons.get({settings:{options:{dropdownItems:[{type:"columnOptions"}]}}})},{el:this.sandbox.dom.find(".datagrid-container"),url:"/admin/api/articles?locale="+this.options.locale,searchInstanceName:"articles",searchFields:["title"],resultKey:"articles",instanceName:"articles",actionCallback:function(a){this.toEdit(a)}.bind(this),viewOptions:{table:{actionIconColumn:"title"}}})},toEdit:function(a,b){this.sandbox.emit("sulu.router.navigate","articles/"+(b||this.options.locale)+"/edit:"+a)},toAdd:function(a){this.sandbox.emit("sulu.router.navigate","articles/"+(a||this.options.locale)+"/add")},toList:function(a){this.sandbox.emit("sulu.router.navigate","articles/"+(a||this.options.locale))},deleteItems:function(a){for(var b=0,c=a.length;b<c;b++)this.deleteItem(a[b])},deleteItem:function(a){this.sandbox.util.save("/admin/api/articles/"+a,"DELETE").then(function(){this.sandbox.emit("husky.datagrid.news.record.remove",a)}.bind(this))},bindCustomEvents:function(){this.sandbox.on("sulu.toolbar.add",this.toAdd.bind(this)),this.sandbox.on("husky.datagrid.news.number.selections",function(a){var b=a>0?"enable":"disable";this.sandbox.emit("sulu.header.toolbar.item."+b,"deleteSelected",!1)}.bind(this)),this.sandbox.on("sulu.toolbar.delete",function(){this.sandbox.emit("husky.datagrid.news.items.get-selected",this.deleteItems.bind(this))}.bind(this)),this.sandbox.on("sulu.header.language-changed",function(a){this.sandbox.sulu.saveUserSetting(this.options.config.settingsKey,a.id),this.toList(a.id)}.bind(this))}}});