/**
 * dropmenu
 * src/widget/popover/popover.js,src/widget/popover/arrow.js,src/widget/dropmenu/dropmenu.js,src/widget/dropmenu/placement.js
 **/
!function (a, b, c) {
    a.define("Popover", {options: {container: null, content: null, event: "click"}, template: {frame: "<div>"}, _create: function () {
        var a = this, c = a._options, d = c.target && b(c.target) || a.getEl(), e = c.container && b(c.container);
        e && e.length || (e = b(a.tpl2html("frame")).addClass("ui-mark-temp")), a.$root = e, c.content && a.setContent(c.content), a.trigger("done.dom", e.addClass("ui-" + a.widgetName), c), e.parent().length || d.after(e), a.target(d)
    }, _checkTemp: function (a) {
        a.is(".ui-mark-temp") && a.off(this.eventNs) && a.remove()
    }, show: function () {
        var b = this, c = a.Event("beforeshow");
        return b.trigger(c), c.isDefaultPrevented() ? void 0 : (b.trigger("placement", b.$root.addClass("ui-in"), b.$target), b._visible = !0, b.trigger("show"))
    }, hide: function () {
        var b = this, c = new a.Event("beforehide");
        return b.trigger(c), c.isDefaultPrevented() ? void 0 : (b.$root.removeClass("ui-in"), b._visible = !1, b.trigger("hide"))
    }, toggle: function () {
        var a = this;
        return a[a._visible ? "hide" : "show"].apply(a, arguments)
    }, target: function (a) {
        if (a === c)return this.$target;
        var d = this, e = b(a), f = d.$target, g = d._options.event + d.eventNs;
        return f && f.off(g), d.$target = e.on(g, function (a) {
            a.preventDefault(), d.toggle()
        }), d
    }, setContent: function (a) {
        var b = this.$root;
        return b.empty().append(a), this
    }, destroy: function () {
        var a = this;
        return a.$target.off(a.eventNs), a._checkTemp(a.$root), a.$super("destroy")
    }})
}(gmu, gmu.$), function (a) {
    var b = a.Popover;
    b.template.arrow = '<span class="ui-arrow"></span>', b.options.arrow = !0, b.option("arrow", !0, function () {
        var a = this, b = a._options;
        b.offset = b.offset || function (a, b) {
            return b = b.split("_")[0], {left: 15 * ("left" === b ? -1 : "right" === b ? 1 : 0), top: 15 * ("top" === b ? -1 : "bottom" === b ? 1 : 0)}
        }, a.on("done.dom", function (b, c) {
            c.append(a.tpl2html("arrow")).addClass("ui-pos-default")
        }), a.on("after.placement", function (a, b, c) {
            var d = this.$root[0], e = d.className, f = c.placement, g = c.align || "";
            d.className = e.replace(/(?:\s|^)ui-pos-[^\s$]+/g, "") + " ui-pos-" + f + (g ? "-" + g : "")
        })
    })
}(gmu), function (a, b) {
    a.define("Dropmenu", {options: {content: null}, template: {item: '<li><a <% if ( href ) { %>href="<%= href %>"<% } %>><% if ( icon ) { %><span class="ui-icon <%= icon %>"></span><% } %><%= text %></a></li>', divider: '<li class="divider"></li>', wrap: "<ul>"}, _init: function () {
        var a = this;
        a.on("done.dom", function (b, c) {
            a.$list = c.find("ul").first().addClass("ui-dropmenu-items").highlight("ui-state-hover", ".ui-dropmenu-items>li:not(.divider)")
        })
    }, _create: function () {
        var c = this, d = c._options, e = "";
        "array" === b.type(d.content) && (d.content.forEach(function (a) {
            a = b.extend({href: "", icon: "", text: ""}, "string" == typeof a ? {text: a} : a), e += c.tpl2html("divider" === a.text ? "divider" : "item", a)
        }), d.content = b(c.tpl2html("wrap")).append(e)), c.$super("_create"), c.$list.on("click" + c.eventNs, ".ui-dropmenu-items>li:not(.ui-state-disable):not(.divider)", function (b) {
            var d = a.Event("itemclick", b);
            c.trigger(d, this), d.isDefaultPrevented() || c.hide()
        })
    }}, a.Popover)
}(gmu, gmu.$), function (a, b) {
    b.extend(a.Dropmenu.options, {placement: "bottom", align: "center", offset: null}), a.Dropmenu.option("placement", function (a) {
        return~["top", "bottom"].indexOf(a)
    }, function () {
        function e(a, b) {
            return"right" === a || "bottom" === a ? b : "center" === a ? b / 2 : 0
        }

        function f(a, b, c, f, g) {
            var h = d.of, i = d.coord, j = d.offset, k = h.top, l = h.left;
            return l += e(b, h.width) - e(f, i.width), k += e(c, h.height) - e(g, i.height), j = "function" == typeof j ? j.call(null, {left: l, top: k}, a) : j || {}, {left: l + (j.left || 0), top: k + (j.top || 0)}
        }

        var d, a = {top_center: "center top center bottom", top_left: "left top left bottom", top_right: "right top right bottom", bottom_center: "center bottom center top", bottom_right: "right bottom right top", bottom_left: "left bottom left top"}, c = {};
        b.each(a, function (a, b) {
            b = b.split(/\s/g), b.unshift(a), c[a] = function () {
                return f.apply(null, b)
            }
        }), this.on("placement", function (a, b, e) {
            var j, f = this, g = f._options, h = g.placement, i = g.align;
            d = {coord: b.offset(), of: e.offset(), placement: h, align: i, $el: b, $of: e, offset: g.offset}, j = c[h + "_" + i](), f.trigger("before.placement", j, d, c), /^(\w+)_(\w+)$/.test(d.preset) && (d.placement = RegExp.$1, d.align = RegExp.$2), b.offset(j), f.trigger("after.placement", j, d)
        })
    })
}(gmu, gmu.$);