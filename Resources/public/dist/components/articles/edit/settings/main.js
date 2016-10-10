define(["underscore","jquery","sulusecurity/components/users/models/user","text!./form.html"],function(a,b,c,d){"use strict";var e={templates:{form:d},translations:{authored:"sulu_article.authored",authors:"sulu_article.authors",changelog:"sulu.content.form.settings.changelog",changed:"sulu.content.form.settings.changelog.changed",created:"sulu.content.form.settings.changelog.created",userNotFound:"sulu.content.form.settings.changelog.user-not-found"}};return{type:"form-tab",defaults:e,parseData:function(b){return{authored:b.authored,authors:a.map(b.authors,function(a){return"c"+a}),creator:b.creator,changer:b.changer,created:b.created,changed:b.changed}},tabInitialize:function(){this.sandbox.emit("sulu.tab.initialize",this.name),this.sandbox.on("sulu.tab.saved",this.saved.bind(this))},submit:function(b){if(this.sandbox.form.validate(this.formId)){var c=this.sandbox.form.getData(this.formId);a.each(c,function(a,b){this.data[b]=a}.bind(this)),this.save(this.data,b)}},save:function(b,c){b.authors=a.map(b.authors,function(a){return a.substr(1)}),this.sandbox.emit("sulu.article.save",b,c)},saved:function(a){this.data=this.parseData(a)},getTemplate:function(){return this.templates.form({translations:this.translations})},getFormId:function(){return"#settings-form"},listenForChange:function(){this.sandbox.dom.on(this.formId,"change keyup",this.setDirty.bind(this)),this.sandbox.on("sulu.content.changed",this.setDirty.bind(this)),this.sandbox.on("husky.ckeditor.changed",this.setDirty.bind(this)),this.updateChangelog(this.data)},updateChangelog:function(a){var c=function(a){this.sandbox.dom.text("#created .name",a),e.resolve()},d=function(a){this.sandbox.dom.text("#changed .name",a),f.resolve()},e=b.Deferred(),f=b.Deferred();a.creator===a.changer?this.loadUser(a.creator).done(function(a){d.call(this,a),c.call(this,a)}.bind(this)).fail(function(){d.call(this,this.translations.userNotFound),c.call(this,this.translations.userNotFound)}.bind(this)):(this.loadUser(a.creator).done(function(a){c.call(this,a)}.bind(this)).fail(function(){c.call(this,this.translations.userNotFound)}.bind(this)),this.loadUser(a.changer).done(function(a){d.call(this,a)}.bind(this)).fail(function(){d.call(this,this.translations.userNotFound)}.bind(this))),this.sandbox.dom.text("#created .date",this.sandbox.date.format(a.created,!0)),this.sandbox.dom.text("#changed .date",this.sandbox.date.format(a.changed,!0)),this.sandbox.data.when([e,f]).then(function(){this.sandbox.dom.show("#changelog-container")}.bind(this))},loadUser:function(a){var d=b.Deferred(),e=new c({id:a});return e.fetch({global:!1,success:function(a){d.resolve(a.get("fullName"))}.bind(this),error:function(){d.reject()}.bind(this)}),d}}});