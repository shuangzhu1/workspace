/**
 * 返回顶部组件
 * src/widget/gotop/gotop.js
 */

!function(a,b,c){a.define("Gotop",{options:{container:"",useFix:!0,useHide:!0,useAnimation:!1,position:{bottom:10,right:10},afterScroll:null},_init:function(){var c,e,a=this,d=a._options;b.os.version&&b.os.version.substr(0,3)>=7&&(d.position.bottom=40),a.on("ready",function(){c=a.$el,e=b.proxy(a._eventHandler,a),d.useHide&&b(document).on("touchmove",e),b(window).on("touchend touchcancel scrollStop",e),b(window).on("scroll ortchange",e),c.on("click",e),a.on("destroy",function(){b(window).off("touchend touchcancel scrollStop",e),b(document).off("touchmove",e),b(window).off("scroll ortchange",e)}),d.useFix&&c.fix(d.position),d.root=c[0]}),a.on("destroy",function(){a.$el.remove()})},_create:function(){var a=this;return a.$el||(a.$el=b("<div></div>")),a.$el.addClass("ui-gotop").append("<div></div>").appendTo(a._options.container||(a.$el.parent().length?"":document.body)),a},_eventHandler:function(a){var b=this;switch(a.type){case"touchmove":b.hide();break;case"scroll":clearTimeout(b._options._TID);break;case"touchend":case"touchcancel":clearTimeout(b._options._TID),b._options._TID=setTimeout(function(){b._check.call(b)},300);break;case"scrollStop":b._check();break;case"ortchange":b._check.call(b);break;case"click":b._scrollTo()}},_check:function(a){var b=this;return(a!==c?a:window.pageYOffset)>document.documentElement.clientHeight?b.show():b.hide(),b},_scrollTo:function(){var a=this,b=window.pageYOffset;return a.hide(),clearTimeout(a._options._TID),a._options.useAnimation?a._options.moveToTop=setInterval(function(){b>1?(window.scrollBy(0,-Math.min(150,b-1)),b-=150):(clearInterval(a._options.moveToTop),a.trigger("afterScroll"))},25,!0):(window.scrollTo(0,1),a.trigger("afterScroll")),a},show:function(){return this._options.root.style.display="block",this},hide:function(){return this._options.root.style.display="none",this}})}(gmu,gmu.$);