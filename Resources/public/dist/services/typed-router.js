define(["services/husky/mediator","articlebundle/services/base-router"],function(a,b){"use strict";var c={list:_.template("articles:<%= type %>/<%= locale %>"),add:_.template("articles/<%= locale %>/add:<%= type %>")},d=function(b){a.emit("sulu.router.navigate",b)};return{toList:function(a,b){d(c.list({locale:a,type:b}))},toAdd:function(a,b){d(c.add({locale:a,type:b}))},toEdit:function(a,c,d){b.toEdit(a,c,d)},toEditForce:function(a,c,d){b.toEditForce(a,c,d)},toEditUpdate:function(a,c,d){b.toEditUpdate(a,c,d)}}});