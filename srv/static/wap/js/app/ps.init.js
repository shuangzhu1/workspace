define(function(require,exports){function e(e){for(var t=function(e){for(var e,t,i,n,r=e.childNodes,a=r.length,o=[],l=0;l<a;l++)if(1===(e=r[l]).nodeType){t=e.children,i=e.getAttribute("data-size").split("x"),(n={src:e.getAttribute("data-src"),w:parseInt(i[0],10),h:parseInt(i[1],10),author:e.getAttribute("data-author")}).el=e,t.length>0&&(n.msrc=t[0].getAttribute("src"),t.length>1&&(n.title=t[1].innerHTML));var s=e.getAttribute("data-med-src");s&&(i=e.getAttribute("data-med-size").split("x"),n.m={src:s,w:parseInt(i[0],10),h:parseInt(i[1],10)}),n.o={src:n.src,w:n.w,h:n.h},o.push(n)}return o},i=function e(t,i){return t&&(i(t)?t:e(t.parentNode,i))},n=function(e,i,n,r){var a,o,l,s=document.querySelectorAll(".pswp")[0];if(l=t(i),o={galleryUID:i.getAttribute("data-pswp-uid"),getThumbBoundsFn:function(e){var t=l[e].el.children[0],i=window.pageYOffset||document.documentElement.scrollTop,n=t.getBoundingClientRect();return{x:n.left,y:n.top+i,w:n.width}},addCaptionHTMLFn:function(e,t,i){return e.title?(t.children[0].innerHTML=e.title+"<br/><small>Photo: "+e.author+"</small>",!0):(t.children[0].innerText="",!1)}},r)if(o.galleryPIDs){for(var d=0;d<l.length;d++)if(l[d].pid==e){o.index=d;break}}else o.index=parseInt(e,10)-1;else o.index=parseInt(e,10);if(!isNaN(o.index)){o.mainClass="pswp--minimal--dark",o.barsSize={top:0,bottom:0},o.captionEl=!1,o.fullscreenEl=!1,o.shareEl=!1,o.bgOpacity=1,o.tapToClose=!0,o.tapToToggleControls=!1,o.showHideOpacity=!0,o.maxSpreadZoom=1,n&&(o.showAnimationDuration=0);var u,c,p=!1,h=!0;(a=new PhotoSwipe(s,PhotoSwipeUI_Default,l,o)).listen("beforeResize",function(){var e=window.devicePixelRatio?window.devicePixelRatio:1;e=Math.min(e,2.5),(u=a.viewportSize.x*e)>=1200||!a.likelyTouchDevice&&u>800||screen.width>1200?p||(p=!0,c=!0):p&&(p=!1,c=!0),c&&!h&&a.invalidateCurrItems(),h&&(h=!1),c=!1}),a.listen("gettingData",function(e,t){p?(t.src=t.o.src,t.w=t.o.w,t.h=t.o.h):(t.src=t.m.src,t.w=t.m.w,t.h=t.m.h)}),a.init()}},r=document.querySelectorAll(e),a=0,o=r.length;a<o;a++)r[a].setAttribute("data-pswp-uid",a+1),r[a].onclick=function(e){(e=e||window.event).preventDefault?e.preventDefault():e.returnValue=!1;var t=e.target||e.srcElement,r=i(t,function(e){return"LI"===e.tagName});if(r){for(var a,o=r.parentNode,l=r.parentNode.childNodes,s=l.length,d=0,u=0;u<s;u++)if(1===l[u].nodeType){if(l[u]===r){a=d;break}d++}return a>=0&&n(a,o),!1}};var l=function(){var e=window.location.hash.substring(1),t={};if(e.length<5)return t;for(var i=e.split("&"),n=0;n<i.length;n++)if(i[n]){var r=i[n].split("=");r.length<2||(t[r[0]]=r[1])}return t.gid&&(t.gid=parseInt(t.gid,10)),t}();l.pid&&l.gid&&n(l.pid,r[l.gid-1],!0,!0)}exports.init=function(){e(".gallery")}});