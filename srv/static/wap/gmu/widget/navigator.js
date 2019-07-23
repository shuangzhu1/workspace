/**
 * @file 导航栏组件
 * @import src/widget/navigator/navigator.js,src/widget/navigator/scrolltonext.js,src/widget/navigator/$scrollable.js
 */
!function (a, b, c) {
    a.define("Navigator", {options: {content: null, event: "click"}, template: {list: "<ul>", item: '<li><a<% if( href ) { %> href="<%= href %>"<% } %>><%= text %></a></li>'}, _create: function () {
        var h, i, a = this, d = a._options, e = a.getEl(), f = e.find("ul").first(), g = "ui-" + a.widgetName;
        !f.length && d.content ? (f = b(a.tpl2html("list")), h = a.tpl2html("item"), i = "", d.content.forEach(function (a) {
            a = b.extend({href: "", text: ""}, "string" == typeof a ? {text: a} : a), i += h(a)
        }), f.append(i).appendTo(e)) : (e.is("ul, ol") && (f = e.wrap("<div>"), e = e.parent()), d.index === c && (d.index = f.find(".ui-state-active").index(), ~d.index || (d.index = 0))), a.$list = f.addClass(g + "-list"), a.trigger("done.dom", e.addClass(g), d), f.highlight("ui-state-hover", "li"), f.on(d.event + a.eventNs, "li:not(.ui-state-disable)>a", function (c) {
            a._switchTo(b(this).parent().index(), c)
        }), a.index = -1, a.switchTo(d.index)
    }, _switchTo: function (b, c) {
        if (b !== this.index) {
            var g, d = this, e = d.$list.children(), f = a.Event("beforeselect", c);
            if (d.trigger(f, e.get(b)), !f.isDefaultPrevented())return g = e.removeClass("ui-state-active").eq(b).addClass("ui-state-active"), d.index = b, d.trigger("select", b, g[0])
        }
    }, switchTo: function (a) {
        return this._switchTo(~~a)
    }, unselect: function () {
        this.index = -1, this.$list.children().removeClass("ui-state-active")
    }, getIndex: function () {
        return this.index
    }})
}(gmu, gmu.$), function (a, b, c) {
    a.Navigator.options.isScrollToNext = !0, a.Navigator.option("isScrollToNext", !0, function () {
        var d, a = this;
        a.on("select", function (e, f, g) {
            d === c && (d = a.index ? 0 : 1);
            var l, h = f > d, i = b(g)[h ? "next" : "prev"](), j = i.offset() || b(g).offset(), k = a.$el.offset();
            (h ? j.left + j.width > k.left + k.width : j.left < k.left) && (l = a.$list.offset(), a.$el.iScroll("scrollTo", h ? k.width - j.left + l.left - j.width : l.left - j.left, 0, 400)), d = f
        })
    })
}(gmu, gmu.$), function (a, b) {
    a.Navigator.options.iScroll = {hScroll: !0, vScroll: !1, hScrollbar: !1, vScrollbar: !1}, a.Navigator.register("scrollable", {_init: function () {
        var a = this, c = a._options;
        a.on("done.dom", function () {
            a.$list.wrap('<div class="ui-scroller"></div>'), a.trigger("init.iScroll"), a.$el.iScroll(b.extend({}, c.iScroll))
        }), b(window).on("ortchange" + a.eventNs, b.proxy(a.refresh, a)), a.on("destroy", function () {
            a.$el.iScroll("destroy"), b(window).off("ortchange" + a.eventNs)
        })
    }, refresh: function () {
        this.trigger("refresh.iScroll").$el.iScroll("refresh")
    }})
}(gmu, gmu.$);