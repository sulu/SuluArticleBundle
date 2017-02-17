define(["underscore","jquery","sulusecurity/components/users/models/user","services/suluarticle/article-manager","text!/admin/articles/template/settings.html"],function(a,b,c,d,e){"use strict";var f={templates:{form:e},translations:{authored:"sulu_article.authored",authors:"sulu_article.authors",changelog:"sulu_article.form.settings.changelog",changed:"sulu_article.form.settings.changelog.changed",changedOnly:"sulu_article.form.settings.changelog.changed-only",created:"sulu_article.form.settings.changelog.created",createdOnly:"sulu_article.form.settings.changelog.created-only"}};return{type:"form-tab",defaults:f,parseData:function(b){return{id:b.id,authored:b.authored,authors:a.map(b.authors,function(a){return"c"+a}),creator:b.creator,changer:b.changer,created:b.created,changed:b.changed}},tabInitialize:function(){this.sandbox.emit("sulu.tab.initialize",this.name),this.sandbox.on("sulu.tab.saved",this.saved.bind(this))},submit:function(b){if(this.sandbox.form.validate(this.formId)){var c=this.sandbox.form.getData(this.formId);a.each(c,function(a,b){this.data[b]=a}.bind(this)),this.save(this.data,b)}},save:function(b,c){b.authors=a.map(b.authors,function(a){return a.substr(1)}),d.save(b,b.id,this.options.locale,c).then(function(a){this.sandbox.emit("sulu.tab.saved",a.id,a)}.bind(this)).fail(function(a){this.sandbox.emit("sulu.article.error",a.status,b)}.bind(this))},saved:function(a){this.data=this.parseData(a)},getTemplate:function(){return this.templates.form({translations:this.translations})},getFormId:function(){return"#settings-form"},listenForChange:function(){this.sandbox.dom.on(this.formId,"change keyup",this.setDirty.bind(this)),this.sandbox.on("sulu.content.changed",this.setDirty.bind(this)),this.sandbox.on("husky.ckeditor.changed",this.setDirty.bind(this)),this.updateChangelog(this.data)},setCreationChangelog:function(a,b){var c,d=this.sandbox.date.format(b,!0);c=a?this.sandbox.util.sprintf(this.translations.created,{creator:a,created:d}):this.sandbox.util.sprintf(this.translations.createdOnly,{created:d}),this.sandbox.dom.text("#created",c)},setChangeChangelog:function(a,b){var c,d=this.sandbox.date.format(b,!0);c=a?this.sandbox.util.sprintf(this.translations.changed,{changer:a,changed:d}):this.sandbox.util.sprintf(this.translations.changedOnly,{changed:d}),this.sandbox.dom.text("#changed",c)},updateChangelog:function(a){var c=b.Deferred(),d=b.Deferred();a.creator===a.changer?this.loadUser(a.creator).done(function(b){c.resolve(b.get("fullName"),a.created),d.resolve(b.get("fullName"),a.changed)}.bind(this)).fail(function(){c.resolve(null,a.created),d.resolve(null,a.changed)}.bind(this)):(this.loadUser(a.creator).done(function(b){c.resolve(b.get("fullName"),a.created)}.bind(this)).fail(function(){c.resolve(null,a.created)}.bind(this)),this.loadUser(a.changer).done(function(b){d.resolve(b.get("fullName"),a.changed)}.bind(this)).fail(function(){d.resolve(null,a.changed)}.bind(this))),this.sandbox.data.when(c,d).then(function(a,b){this.setCreationChangelog(a[0],a[1]),this.setChangeChangelog(b[0],b[1]),this.sandbox.dom.show("#changelog-container")}.bind(this))},loadUser:function(a){var d=b.Deferred(),e=new c({id:a});return e.fetch({global:!1,success:function(a){d.resolve(a)}.bind(this),error:function(){d.reject()}.bind(this)}),d}}});