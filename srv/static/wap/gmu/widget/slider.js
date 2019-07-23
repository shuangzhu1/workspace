/**
 * 图片轮播组件
 * src/widget/slider/slider.js,src/widget/slider/$touch.js,src/widget/slider/arrow.js,src/widget/slider/$autoplay.js,src/widget/slider/dots.js,src/widget/slider/imgzoom.js,src/widget/slider/$lazyloadimg.js
 */

!function (a, b) {
    var d = b.fx.cssPrefix, e = b.fx.transitionEnd, f = " translateZ(0)";
    a.define("Slider", {options: {loop: !1, speed: 400, index: 0, selector: {container: ".ui-slider-group"}}, template: {item: '<div class="ui-slider-item"><a href="<%= href %>"><img src="<%= pic %>" alt="" /></a><% if( title ) { %><p><%= title %></p><% } %></div>'}, _create: function () {
        var a = this, c = a.getEl(), d = a._options;
        a.index = d.index, a._initDom(c, d), a._initWidth(c, a.index), a._container.on(e + a.eventNs, b.proxy(a._tansitionEnd, a)), b(window).on("ortchange" + a.eventNs, function () {
            a._initWidth(c, a.index)
        })
    }, _initDom: function (a, c) {
        var f, g, d = c.selector, e = c.viewNum || 1;
        for (g = a.find(d.container), g.length || (g = b("<div></div>"), c.content ? this._createItems(g, c.content) : a.is("ul") ? (this.$el = g.insertAfter(a), g = a, a = this.$el) : g.append(a.children()), g.appendTo(a)), (f = g.children()).length < e + 1 && (c.loop = !1); c.loop && g.children().length < 3 * e;)g.append(f.clone());
        this.length = g.children().length, this._items = (this._container = g).addClass("ui-slider-group").children().addClass("ui-slider-item").toArray(), this.trigger("done.dom", a.addClass("ui-slider"), c)
    }, _createItems: function (a, b) {
        for (var c = 0, d = b.length; d > c; c++)a.append(this.tpl2html("item", b[c]))
    }, _initWidth: function (a, b, c) {
        var e, d = this;
        (c || (e = a.width()) !== d.width) && (d.width = e, d._arrange(e, b), d.height = a.height(), d.trigger("width.change"))
    }, _arrange: function (a, b) {
        var e, f, c = this._items, d = 0;
        for (this._slidePos = new Array(c.length), f = c.length; f > d; d++)e = c[d], e.style.cssText += "width:" + a + "px;" + "left:" + d * -a + "px;", e.setAttribute("data-index", d), this._move(d, b > d ? -a : d > b ? a : 0, 0);
        this._container.css("width", a * f)
    }, _move: function (a, b, c, d) {
        var e = this._slidePos, f = this._items;
        e[a] !== b && f[a] && (this._translate(a, b, c), e[a] = b, d && f[a].clientLeft)
    }, _translate: function (a, b, c) {
        var e = this._items[a], g = e && e.style;
        return g ? (g.cssText += d + "transition-duration:" + c + "ms;" + d + "transform: translate(" + b + "px, 0)" + f + ";", void 0) : !1
    }, _circle: function (a, b) {
        var c;
        return b = b || this._items, c = b.length, (a % c + c) % b.length
    }, _tansitionEnd: function (a) {
        ~~a.target.getAttribute("data-index") === this.index && this.trigger("slideend", this.index)
    }, _slide: function (a, b, c, d, e, f) {
        var h, g = this;
        return h = g._circle(a - c * b), f.loop || (c = Math.abs(a - h) / (a - h)), this._move(h, -c * d, 0, !0), this._move(a, d * c, e), this._move(h, 0, e), this.index = h, this.trigger("slide", h, a)
    }, slideTo: function (a, b) {
        if (this.index === a || this.index === this._circle(a))return this;
        var c = this._options, d = this.index, e = Math.abs(d - a), f = e / (d - a), g = this.width;
        return b = b || c.speed, this._slide(d, e, f, g, b, c)
    }, prev: function () {
        return(this._options.loop || this.index > 0) && this.slideTo(this.index - 1), this
    }, next: function () {
        return(this._options.loop || this.index + 1 < this.length) && this.slideTo(this.index + 1), this
    }, getIndex: function () {
        return this.index
    }, destroy: function () {
        return this._container.off(this.eventNs), b(window).off("ortchange" + this.eventNs), this.$super("destroy")
    }})
}(gmu, gmu.$), function (a, b, c) {
    var e, f, g, h, d = {touchstart: "_onStart", touchmove: "_onMove", touchend: "_onEnd", touchcancel: "_onEnd", click: "_onClick"};
    b.extend(a.Slider.options, {stopPropagation: !1, disableScroll: !1}), a.Slider.register("touch", {_init: function () {
        var a = this, b = a.getEl();
        a._handler = function (b) {
            return a._options.stopPropagation && b.stopPropagation(), d[b.type] && a[d[b.type]].call(a, b)
        }, a.on("ready", function () {
            b.on("touchstart" + a.eventNs, a._handler), a._container.on("click" + a.eventNs, a._handler)
        })
    }, _onClick: function () {
        return!h
    }, _onStart: function (a) {
        if (a.touches.length > 1)return!1;
        var k, b = this, d = a.touches[0], i = b._options, j = b.eventNs;
        f = {x: d.pageX, y: d.pageY, time: +new Date}, g = {}, h = !1, e = c, k = i.viewNum || 1, b._move(i.loop ? b._circle(b.index - k) : b.index - k, -b.width, 0, !0), b._move(i.loop ? b._circle(b.index + k) : b.index + k, b.width, 0, !0), b.$el.on("touchmove" + j + " touchend" + j + " touchcancel" + j, b._handler)
    }, _onMove: function (a) {
        if (a.touches.length > 1 || a.scale && 1 !== a.scale)return!1;
        var j, k, l, m, b = this._options, c = b.viewNum || 1, d = a.touches[0], i = this.index;
        if (b.disableScroll && a.preventDefault(), g.x = d.pageX - f.x, g.y = d.pageY - f.y, "undefined" == typeof e && (e = Math.abs(g.x) < Math.abs(g.y)), !e) {
            for (a.preventDefault(), b.loop || (g.x /= !i && g.x > 0 || i === this._items.length - 1 && g.x < 0 ? Math.abs(g.x) / this.width + 1 : 1), m = this._slidePos, j = i - c, k = i + 2 * c; k > j; j++)l = b.loop ? this._circle(j) : j, this._translate(l, g.x + m[l], 0);
            h = !0
        }
    }, _onEnd: function () {
        if (this.$el.off("touchmove" + this.eventNs + " touchend" + this.eventNs + " touchcancel" + this.eventNs, this._handler), h) {
            var m, n, o, p, q, a = this, b = a._options, c = b.viewNum || 1, d = a.index, e = a._slidePos, i = +new Date - f.time, j = Math.abs(g.x), k = !b.loop && (!d && g.x > 0 || d === e.length - c && g.x < 0), l = g.x > 0 ? 1 : -1;
            if (250 > i ? (m = j / i, n = Math.min(Math.round(1.2 * m * c), c)) : n = Math.round(j / (a.perWidth || a.width)), n && !k)a._slide(d, n, l, a.width, b.speed, b, !0), c > 1 && i >= 250 && Math.ceil(j / a.perWidth) !== n && (a.index < d ? a._move(a.index - 1, -a.perWidth, b.speed) : a._move(a.index + c, a.width, b.speed)); else for (o = d - c, p = d + 2 * c; p > o; o++)q = b.loop ? a._circle(o) : o, a._translate(q, e[q], b.speed)
        }
    }})
}(gmu, gmu.$), function (a, b) {
    b.extend(!0, a.Slider, {template: {prev: '<span class="ui-slider-pre"></span>', next: '<span class="ui-slider-next"></span>'}, options: {arrow: !0, select: {prev: ".ui-slider-pre", next: ".ui-slider-next"}}}), a.Slider.option("arrow", !0, function () {
        var a = this, c = ["prev", "next"];
        this.on("done.dom", function (d, e, f) {
            var g = f.selector;
            c.forEach(function (c) {
                var d = e.find(g[c]);
                d.length || e.append(d = b(a.tpl2html(c))), a["_" + c] = d
            })
        }), this.on("ready", function () {
            c.forEach(function (b) {
                a["_" + b].on("tap" + a.eventNs, function () {
                    a[b].call(a)
                })
            })
        }), this.on("destroy", function () {
            a._prev.off(a.eventNs), a._next.off(a.eventNs)
        })
    })
}(gmu, gmu.$), function (a, b) {
    b.extend(!0, a.Slider, {options: {autoPlay: !0, interval: 4e3}}), a.Slider.register("autoplay", {_init: function () {
        var a = this;
        a.on("slideend ready", a.resume).on("destory", a.stop), a.getEl().on("touchstart" + a.eventNs, b.proxy(a.stop, a)).on("touchend" + a.eventNs, b.proxy(a.resume, a))
    }, resume: function () {
        var a = this, b = a._options;
        return b.autoPlay && !a._timer && (a._timer = setTimeout(function () {
            a.slideTo(a.index + 1), a._timer = null
        }, b.interval)), a
    }, stop: function () {
        var a = this;
        return a._timer && (clearTimeout(a._timer), a._timer = null), a
    }})
}(gmu, gmu.$), function (a, b) {
    b.extend(!0, a.Slider, {template: {dots: '<p class="ui-slider-dots"><%= new Array( len + 1 ).join("<b></b>") %></p>'}, options: {dots: !0, selector: {dots: ".ui-slider-dots"}}}), a.Slider.option("dots", !0, function () {
        var c = function (b, c) {
            var d = this._dots;
            "undefined" == typeof c || a.staticCall(d[c % this.length], "removeClass", "ui-state-active"), a.staticCall(d[b % this.length], "addClass", "ui-state-active")
        };
        this.on("done.dom", function (a, c, d) {
            var e = c.find(d.selector.dots);
            e.length || (e = this.tpl2html("dots", {len: this.length}), e = b(e).appendTo(c)), this._dots = e.children().toArray()
        }), this.on("slide", function (a, b, d) {
            c.call(this, b, d)
        }), this.on("ready", function () {
            c.call(this, this.index)
        })
    })
}(gmu, gmu.$), function (a) {
    a.Slider.options.imgZoom = !0, a.Slider.option("imgZoom", function () {
        return!!this._options.imgZoom
    }, function () {
        function d() {
            c && c.off("load" + a.eventNs, f)
        }

        function e() {
            d(), c = a._container.find(b).on("load" + a.eventNs, f)
        }

        function f(b) {
            var c = b.target || this, d = Math.min(1, a.width / c.naturalWidth, a.height / c.naturalHeight);
            c.style.width = d * c.naturalWidth + "px"
        }

        var c, a = this, b = a._options.imgZoom;
        b = "string" == typeof b ? b : "img", a.on("ready dom.change", e), a.on("width.change", function () {
            c && c.each(f)
        }), a.on("destroy", d)
    })
}(gmu), function (a) {
    a.Slider.template.item = '<div class="ui-slider-item"><a href="<%= href %>"><img lazyload="<%= pic %>" alt="" /></a><% if( title ) { %><p><%= title %></p><% } %></div>', a.Slider.register("lazyloadimg", {_init: function () {
        this.on("ready slide", this._loadItems)
    }, _loadItems: function () {
        var e, f, a = this._options, b = a.loop, c = a.viewNum || 1, d = this.index;
        for (e = d - c, f = d + 2 * c; f > e; e++)this.loadImage(b ? this._circle(e) : e)
    }, loadImage: function (b) {
        var d, c = this._items[b];
        return c && (d = a.staticCall(c, "find", "img[lazyload]"), d.length) ? (d.each(function () {
            this.src = this.getAttribute("lazyload"), this.removeAttribute("lazyload")
        }), void 0) : this
    }})
}(gmu);