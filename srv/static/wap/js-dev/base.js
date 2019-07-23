/**
 * ajax tip
 *
 * @constructor
 */
define(function (require, exports) {


    window.inAjaxProcess = false;

    function ajaxTip() {

    }

    ajaxTip.ajaxTimer = null;
    ajaxTip.tip = null;
    ajaxTip.time = 0;
    ajaxTip.status = null;
    ajaxTip.prototype = {
        showTip: function (status, tip, time, callback) {
            ajaxTip.status = status;
            ajaxTip.tip = tip;
            ajaxTip.time = time;

            $('#ajaxStatus').show();
            $('#ajaxStatus #ajaxTip').html(ajaxTip.tip).removeClass().addClass(ajaxTip.status);


            if (ajaxTip.time) {
                if (ajaxTip.ajaxTimer) {
                    clearTimeout(ajaxTip.ajaxTimer);
                }
                ajaxTip.ajaxTimer = setTimeout(function () {
                    $('#ajaxStatus').hide();
                    ajaxTip.inProcess = true;
                    if (typeof callback == 'function') {
                        callback();
                    }
                }, ajaxTip.time);
            }
        },
        hideTip: function () {
            $('#ajaxStatus').hide();
        }
    };
    window.tip = new ajaxTip();

// popup basic show hide
    showPop = function (popElem) {

        var pop = new popup(popElem);
        pop.show();


    };


// popup basic show hide
    hidePop = function (popElem) {
        var pop = new popup(popElem);
        pop.hide();
    };


// popup
    function popup(popElem) {

        this.widget = popElem ? popElem : '.popup-widget';

        var _this = this;
        // close click
        $('.popup-widget .popup-close').on('click', function () {
            _this.hide();
        });

        // bg click
        $('.popup-bg').on('click', function () {
            _this.hide();
        });
    }

    popup.prototype = {
        'show': function () {
            $(this.widget).fadeIn();
            $('.popup-bg').fadeIn();
        },
        'hide': function () {
            $('.popup-widget').hide();
            $('.popup-bg').hide();
        }
    };
    exports.showTip = function (status, tip, time, callback) {
        var t = new ajaxTip();
        t.showTip(status, tip, time, callback);
    };

    exports.requestApi = function (uri, data, func, endProcess, hideTip, tipsDom, jsonp) {
        // 手动更改请求，立即结束ajax状态
        if (endProcess) {
            window.inAjaxProcess = false;
        }
        if (!inAjaxProcess) {
            var param = {
                url: uri,
                async: true,
                data: data,
                dataType: jsonp ? 'jsonp' : 'json',
                type: 'post',
                beforeSend: function () {
                    if (!hideTip) {
                        tip.showTip('wait', '处理请求...');
                    }
                    if (window.inAjaxProcess) {
                        tip.showTip('wait', '正在请求...');
                        return false;
                    }
                    // 正在处理状态
                    window.inAjaxProcess = true;
                },
                timeout: function () {
                    tip.showTip('err', '请求超时,请重试！', 2000);
                },
                abort: function () {
                    tip.showTip('err', '网路连接被中断！', 2000);
                },
                parsererror: function () {
                    tip.showTip('err', '运行时发生错误！', 2000);
                },
                error: function () {
                    if (window.inAjaxProcess && ajaxTip.time > 1000) {
                        tip.showTip('err', '运行错误，请重试！', 2000);
                    }
                },
                complete: function () {
                    setTimeout(function () {
                        if ($('#ajaxStatus').css('display') !== 'none') {
                            //tip.showTip('ok', '操作成功！', 2000);
                            tip.hideTip();
                        }

                        // 清除处理状态
                        window.inAjaxProcess = false;
                    }, ajaxTip.time);// 最后一次tip时间
                },
                success: function (res) {
                    if (typeof func == 'function') {
                        func(res);
                    }

                    if (res.result != 1) {
                        if (tipsDom) {
                            tipsDom.html(res.error.msg);
                            setTimeout(function () {
                                tipsDom.html(' ');
                            }, 2000)
                        } else {
                            if (!hideTip) {
                                tip.showTip('err', res.error.more ? (res.error.msg + "[" + res.error.more + "]") : res.error.msg, 3000);
                            }
                        }
                    }
                }
            };

            $.ajax(param);
        }
    };
});
function checkField(elem, msg, regx) {
    var val = $(elem).val();
    if ($(elem).val() == '') {
        $(elem).siblings('.tip').css({'display': 'block'});
        $(elem).siblings('.tip').html('不能为空');
        return false;
    } else {
        if (regx) {
            if (!new RegExp(regx).test(val)) {
                $(elem).siblings('.tip').css({'display': 'block'});
                $(elem).siblings('.tip').html(msg || '格式不正确');
                return false;
            } else {
                $(elem).siblings('.tip').css({'display': 'none'});
                $(elem).siblings('.tip').html('');
            }
        }
        $(elem).siblings('.tip').css({'display': 'none'});
        $(elem).siblings('.tip').html('');
    }

    return true;
}


/**
 * 判断浏览器是否为微信浏览器，并且版本是5.0以上版本
 */
function is_weixin(ver5) {
    //Mozilla/5.0(iphone;CPU iphone OS 5_1_1 like Mac OS X) AppleWebKit/534.46(KHTML,likeGeocko) Mobile/9B206 MicroMessenger/5.0
    var ua = navigator.userAgent.toLowerCase();
    var rwx = /.*(micromessenger)\/([\w.]+).*/;
    var match = rwx.exec(ua);
    if (match) {
        if (match[1] === 'micromessenger') {
            if (ver5) {
                if (parseFloat(match[2]) >= 5) {
                    return true;
                }
            } else {
                return true;
            }
        }
    }

    return false;
}

/**
 * 判断浏览器是否为微信浏览器，并且版本是5.0以上版本
 */
function is_weibo(ver5) {
    //Mozilla/5.0(iphone;CPU iphone OS 5_1_1 like Mac OS X) AppleWebKit/534.46(KHTML,likeGeocko) Mobile/9B206 MicroMessenger/5.0
    var ua = navigator.userAgent.toLowerCase();
    var rwx = /.*(weibo)\/([\w.]+).*/;
    var match = rwx.exec(ua);
    if (match) {
        if (match[1] === 'weibo') {
            return true;
        }
    }

    return false;
}

function datepicker() {
    $('#selectYear').on('change', function () {
        changeFbirary();
    });
    $('#selectMonth').on('change', function () {
        changeFbirary();
    });
    // 根据是否闰月改变二月份
    function changeFbirary() {
        $('#birthday').val('0000-00-00');

        var selectYear = parseInt($('#selectYear').val());
        var selectMonth = parseInt($('#selectMonth').val());
        if (!(selectYear && selectMonth)) return false;
        var monthDay = [0, 31, 28, 31, 30, 31, 30, 31, 31, 30, 31, 30, 31];
        // 年下拉框改变时，判断是否是是闰年，更改二月分的天数
        if (selectMonth == 2 && ((selectYear % 100 == 0) && (selectYear % 400 == 0) || (selectYear % 100 != 0) && (selectYear % 4 == 0))) {
            monthDay[2]++;
        }
        var opt = '<option value="">日期</option>';
        // 获取用户原来的数据
        for (var i = 1; i <= monthDay[selectMonth]; i++) {
            i = i < 10 ? 0 + '' + i : i;
            opt += "<option value='" + i + "'>" + i + "</option>";
        }
        $("#selectDay").html(opt);
    }

    $('#selectDay').on('change', function () {
        var selectYear = $('#selectYear').val();
        var selectMonth = $('#selectMonth').val();
        var selectDay = $('#selectDay').val();

        $('#birthday').val(selectYear + '-' + selectMonth + '-' + selectDay);
    });


}

/**
 * Implements JSON stringify and parse functions
 * v1.0
 *
 * By Craig Buckler, Optimalworks.net
 *
 * As featured on SitePoint.com
 * Please use as you wish at your own risk.
 *
 * Usage:
 *
 * // serialize a JavaScript object to a JSON string
 * var str = JSON.stringify(object);
 *
 * // de-serialize a JSON string to a JavaScript object
 * var obj = JSON.parse(str);
 */

var JSON = JSON || {};

// implement JSON.stringify serialization
JSON.stringify = JSON.stringify || function (obj) {

        var t = typeof (obj);
        if (t != "object" || obj === null) {

            // simple data type
            if (t == "string") obj = '"' + obj + '"';
            return String(obj);

        }
        else {

            // recurse array or object
            var n, v, json = [], arr = (obj && obj.constructor == Array);

            for (n in obj) {
                v = obj[n];
                t = typeof(v);

                if (t == "string") v = '"' + v + '"';
                else if (t == "object" && v !== null) v = JSON.stringify(v);

                json.push((arr ? "" : '"' + n + '":') + String(v));
            }

            return (arr ? "[" : "{") + String(json) + (arr ? "]" : "}");
        }
    };
// 序列化插件
(function ($) {
    $.fn.serializeObject = function () {

        var self = this,
            json = {},
            push_counters = {},
            patterns = {
                "validate": /^[a-zA-Z][a-zA-Z0-9_]*(?:\[(?:\d*|[a-zA-Z0-9_]+)\])*$/,
                "key": /[a-zA-Z0-9_]+|(?=\[\])/g,
                "push": /^$/,
                "fixed": /^\d+$/,
                "named": /^[a-zA-Z0-9_]+$/
            };


        this.build = function (base, key, value) {
            base[key] = value;
            return base;
        };

        this.push_counter = function (key) {
            if (push_counters[key] === undefined) {
                push_counters[key] = 0;
            }
            return push_counters[key]++;
        };

        $.each($(this).serializeArray(), function () {

            // skip invalid keys
            if (!patterns.validate.test(this.name)) {
                return;
            }

            var k,
                keys = this.name.match(patterns.key),
                merge = this.value,
                reverse_key = this.name;

            while ((k = keys.pop()) !== undefined) {

                // adjust reverse_key
                reverse_key = reverse_key.replace(new RegExp("\\[" + k + "\\]$"), '');

                // push
                if (k.match(patterns.push)) {
                    merge = self.build([], self.push_counter(reverse_key), merge);
                }

                // fixed
                else if (k.match(patterns.fixed)) {
                    merge = self.build([], k, merge);
                }

                // named
                else if (k.match(patterns.named)) {
                    merge = self.build({}, k, merge);
                }
            }

            json = $.extend(true, json, merge);
        });

        return json;
    };
})($);


// implement JSON.parse de-serialization
JSON.parse = JSON.parse || function (str) {
        if (str === "") str = '""';
        eval("var p=" + str + ";");
        return p;
    };
(function (global) {
    "use strict";
    var _Base64 = global.Base64;
    var version = "2.1.4";
    var buffer;
    if (typeof module !== "undefined" && module.exports) {
        buffer = require("buffer").Buffer
    }
    var b64chars = "ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789+/";
    var b64tab = function (bin) {
        var t = {};
        for (var i = 0, l = bin.length; i < l; i++)t[bin.charAt(i)] = i;
        return t
    }(b64chars);
    var fromCharCode = String.fromCharCode;
    var cb_utob = function (c) {
        if (c.length < 2) {
            var cc = c.charCodeAt(0);
            return cc < 128 ? c : cc < 2048 ? fromCharCode(192 | cc >>> 6) + fromCharCode(128 | cc & 63) : fromCharCode(224 | cc >>> 12 & 15) + fromCharCode(128 | cc >>> 6 & 63) + fromCharCode(128 | cc & 63)
        } else {
            var cc = 65536 + (c.charCodeAt(0) - 55296) * 1024 + (c.charCodeAt(1) - 56320);
            return fromCharCode(240 | cc >>> 18 & 7) + fromCharCode(128 | cc >>> 12 & 63) + fromCharCode(128 | cc >>> 6 & 63) + fromCharCode(128 | cc & 63)
        }
    };
    var re_utob = /[\uD800-\uDBFF][\uDC00-\uDFFFF]|[^\x00-\x7F]/g;
    var utob = function (u) {
        return u.replace(re_utob, cb_utob)
    };
    var cb_encode = function (ccc) {
        var padlen = [0, 2, 1][ccc.length % 3], ord = ccc.charCodeAt(0) << 16 | (ccc.length > 1 ? ccc.charCodeAt(1) : 0) << 8 | (ccc.length > 2 ? ccc.charCodeAt(2) : 0), chars = [b64chars.charAt(ord >>> 18), b64chars.charAt(ord >>> 12 & 63), padlen >= 2 ? "=" : b64chars.charAt(ord >>> 6 & 63), padlen >= 1 ? "=" : b64chars.charAt(ord & 63)];
        return chars.join("")
    };
    var btoa = global.btoa ? function (b) {
        return global.btoa(b)
    } : function (b) {
        return b.replace(/[\s\S]{1,3}/g, cb_encode)
    };
    var _encode = buffer ? function (u) {
        return new buffer(u).toString("base64")
    } : function (u) {
        return btoa(utob(u))
    };
    var encode = function (u, urisafe) {
        return !urisafe ? _encode(u) : _encode(u).replace(/[+\/]/g, function (m0) {
            return m0 == "+" ? "-" : "_"
        }).replace(/=/g, "")
    };
    var encodeURI = function (u) {
        return encode(u, true)
    };
    var re_btou = new RegExp(["[À-ß][-¿]", "[à-ï][-¿]{2}", "[ð-÷][-¿]{3}"].join("|"), "g");
    var cb_btou = function (cccc) {
        switch (cccc.length) {
            case 4:
                var cp = (7 & cccc.charCodeAt(0)) << 18 | (63 & cccc.charCodeAt(1)) << 12 | (63 & cccc.charCodeAt(2)) << 6 | 63 & cccc.charCodeAt(3), offset = cp - 65536;
                return fromCharCode((offset >>> 10) + 55296) + fromCharCode((offset & 1023) + 56320);
            case 3:
                return fromCharCode((15 & cccc.charCodeAt(0)) << 12 | (63 & cccc.charCodeAt(1)) << 6 | 63 & cccc.charCodeAt(2));
            default:
                return fromCharCode((31 & cccc.charCodeAt(0)) << 6 | 63 & cccc.charCodeAt(1))
        }
    };
    var btou = function (b) {
        return b.replace(re_btou, cb_btou)
    };
    var cb_decode = function (cccc) {
        var len = cccc.length, padlen = len % 4, n = (len > 0 ? b64tab[cccc.charAt(0)] << 18 : 0) | (len > 1 ? b64tab[cccc.charAt(1)] << 12 : 0) | (len > 2 ? b64tab[cccc.charAt(2)] << 6 : 0) | (len > 3 ? b64tab[cccc.charAt(3)] : 0), chars = [fromCharCode(n >>> 16), fromCharCode(n >>> 8 & 255), fromCharCode(n & 255)];
        chars.length -= [0, 0, 2, 1][padlen];
        return chars.join("")
    };
    var atob = global.atob ? function (a) {
        return global.atob(a)
    } : function (a) {
        return a.replace(/[\s\S]{1,4}/g, cb_decode)
    };
    var _decode = buffer ? function (a) {
        return new buffer(a, "base64").toString()
    } : function (a) {
        return btou(atob(a))
    };
    var decode = function (a) {
        return _decode(a.replace(/[-_]/g, function (m0) {
            return m0 == "-" ? "+" : "/"
        }).replace(/[^A-Za-z0-9\+\/]/g, ""))
    };
    var noConflict = function () {
        var Base64 = global.Base64;
        global.Base64 = _Base64;
        return Base64
    };
    global.Base64 = {
        VERSION: version,
        atob: atob,
        btoa: btoa,
        fromBase64: decode,
        toBase64: encode,
        utob: utob,
        encode: encode,
        encodeURI: encodeURI,
        btou: btou,
        decode: decode,
        noConflict: noConflict
    };
    if (typeof Object.defineProperty === "function") {
        var noEnum = function (v) {
            return {value: v, enumerable: false, writable: true, configurable: true}
        };
        global.Base64.extendString = function () {
            Object.defineProperty(String.prototype, "fromBase64", noEnum(function () {
                return decode(this)
            }));
            Object.defineProperty(String.prototype, "toBase64", noEnum(function (urisafe) {
                return encode(this, urisafe)
            }));
            Object.defineProperty(String.prototype, "toBase64URI", noEnum(function () {
                return encode(this, true)
            }))
        }
    }
})(this);

/**
 * base64
 * Usage
 $.base64.encode( "this is a test" ) returns "dGhpcyBpcyBhIHRlc3Q="
 $.base64.decode( "dGhpcyBpcyBhIHRlc3Q=" ) returns "this is a test"
 */
!function (a) {
    "use strict";
    var d, e, f, g, h, i, j, k, l, m, n, o, p, q, r, s, t, u, v, w, x, b = a.base64, c = "2.1.4";
    "undefined" != typeof module && module.exports && (d = require("buffer").Buffer), e = "ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789+/", f = function (a) {
        var c, d, b = {};
        for (c = 0, d = a.length; d > c; c++)b[a.charAt(c)] = c;
        return b
    }(e), g = String.fromCharCode, h = function (a) {
        var b;
        return a.length < 2 ? (b = a.charCodeAt(0), 128 > b ? a : 2048 > b ? g(192 | b >>> 6) + g(128 | 63 & b) : g(224 | 15 & b >>> 12) + g(128 | 63 & b >>> 6) + g(128 | 63 & b)) : (b = 65536 + 1024 * (a.charCodeAt(0) - 55296) + (a.charCodeAt(1) - 56320), g(240 | 7 & b >>> 18) + g(128 | 63 & b >>> 12) + g(128 | 63 & b >>> 6) + g(128 | 63 & b))
    }, i = /[\uD800-\uDBFF][\uDC00-\uDFFFF]|[^\x00-\x7F]/g, j = function (a) {
        return a.replace(i, h)
    }, k = function (a) {
        var b = [0, 2, 1][a.length % 3], c = a.charCodeAt(0) << 16 | (a.length > 1 ? a.charCodeAt(1) : 0) << 8 | (a.length > 2 ? a.charCodeAt(2) : 0), d = [e.charAt(c >>> 18), e.charAt(63 & c >>> 12), b >= 2 ? "=" : e.charAt(63 & c >>> 6), b >= 1 ? "=" : e.charAt(63 & c)];
        return d.join("")
    }, l = a.btoa ? function (b) {
        return a.btoa(b)
    } : function (a) {
        return a.replace(/[\s\S]{1,3}/g, k)
    }, m = d ? function (a) {
        return new d(a).toString("base64")
    } : function (a) {
        return l(j(a))
    }, n = function (a, b) {
        return b ? m(a).replace(/[+\/]/g, function (a) {
            return "+" == a ? "-" : "_"
        }).replace(/=/g, "") : m(a)
    }, o = function (a) {
        return n(a, !0)
    }, p = new RegExp(["[À-ß][-¿]", "[à-ï][-¿]{2}", "[ð-÷][-¿]{3}"].join("|"), "g"), q = function (a) {
        switch (a.length) {
            case 4:
                var b = (7 & a.charCodeAt(0)) << 18 | (63 & a.charCodeAt(1)) << 12 | (63 & a.charCodeAt(2)) << 6 | 63 & a.charCodeAt(3), c = b - 65536;
                return g((c >>> 10) + 55296) + g((1023 & c) + 56320);
            case 3:
                return g((15 & a.charCodeAt(0)) << 12 | (63 & a.charCodeAt(1)) << 6 | 63 & a.charCodeAt(2));
            default:
                return g((31 & a.charCodeAt(0)) << 6 | 63 & a.charCodeAt(1))
        }
    }, r = function (a) {
        return a.replace(p, q)
    }, s = function (a) {
        var b = a.length, c = b % 4, d = (b > 0 ? f[a.charAt(0)] << 18 : 0) | (b > 1 ? f[a.charAt(1)] << 12 : 0) | (b > 2 ? f[a.charAt(2)] << 6 : 0) | (b > 3 ? f[a.charAt(3)] : 0), e = [g(d >>> 16), g(255 & d >>> 8), g(255 & d)];
        return e.length -= [0, 0, 2, 1][c], e.join("")
    }, t = a.atob ? function (b) {
        return a.atob(b)
    } : function (a) {
        return a.replace(/[\s\S]{1,4}/g, s)
    }, u = d ? function (a) {
        return new d(a, "base64").toString()
    } : function (a) {
        return r(t(a))
    }, v = function (a) {
        return u(a.replace(/[-_]/g, function (a) {
            return "-" == a ? "+" : "/"
        }).replace(/[^A-Za-z0-9\+\/]/g, ""))
    }, w = function () {
        var c = a.base64;
        return a.base64 = b, c
    }, a.base64 = {
        VERSION: c,
        atob: t,
        btoa: l,
        fromBase64: v,
        toBase64: n,
        utob: j,
        encode: n,
        encodeURI: o,
        btou: r,
        decode: v,
        noConflict: w
    }, "function" == typeof Object.defineProperty && (x = function (a) {
        return {value: a, enumerable: !1, writable: !0, configurable: !0}
    }, a.base64.extendString = function () {
        Object.defineProperty(String.prototype, "fromBase64", x(function () {
            return v(this)
        })), Object.defineProperty(String.prototype, "toBase64", x(function (a) {
            return n(this, a)
        })), Object.defineProperty(String.prototype, "toBase64URI", x(function () {
            return n(this, !0)
        }))
    })
}($);
// 点击加载更多
window.loadMore = function (url, params) {
    var page = 1;
    $(".ui-refresh .ui-refresh-down").on('click', function () {
        params['page'] = page;
        $(".ui-refresh .ui-refresh-down").html("<img src='/static/home/images/loading.gif'>");
        requestApi(url, params, function (res) {
            if (res.result == 1) {
                $("#ajaxStatus").hide();
                page++;
                if (res.data == '') {
                    $(".ui-refresh .ui-refresh-down").html("没有更多内容了").unbind('click');
                } else {
                    $(".ui-refresh .ui-refresh-down").html("加载更多");
                    $(".ui-refresh .data-list").append(res.data);
                    if (window.baguetteBox) {
                        baguetteBox.run('.gallery');
                    }
                    if (window.refreshSwiper) {
                        seajs.use('tools/swiper.js', function () {
                            $.each($(".item-swiper"), function (i, e) {
                                var that = '#' + $(e).attr('id');
                                var mySwiper = new Swiper(that, {
                                    pagination: that + ' > .pagination',
                                    paginationClickable: true,
                                    calculateHeight: true,
                                    resizeReInit: true,
                                    caculateHeight: true
                                })
                            });
                        });
                    }
                }
            }
        })
    })

    $(".ui-refresh .comment_reply_load_more").on('click', function () {
        params['page'] = page;
        $(".ui-refresh .comment_reply_load_more").html("<img src='/static/home/images/loading.gif'>");
        requestApi(url, params, function (res) {
            if (res.result == 1) {
                $("#ajaxStatus").hide();
                page++;
                if (res.data == '') {
                    $(".ui-refresh .comment_reply_load_more").html("没有更多内容了").unbind('click');
                } else {
                    $(".ui-refresh .comment_reply_load_more").html("加载更多");
                    $(".ui-refresh .data-list").append(res.data);
                    if (window.baguetteBox) {
                        baguetteBox.run('.gallery');
                    }
                }
            }
        })
    })
};
// 默认加载
window.loadInit = function (url, params) {
    var page = 0;
    params['page'] = page;
    $.ajax({
        'type': 'post',
        'dataType': 'json',
        'url': url,
        'data': params,
        'success': function (res) {
            if (res.result == 1) {
                $("#ajaxStatus").hide();
                page++;
                if (res.data == '') {
                    $(".ui-refresh .data-list").html('');
                    $(".ui-refresh .ui-refresh-down").html("没有更多内容了").unbind('click');
                } else {
                    $(".ui-refresh .data-list").html(res.data);
                    $(".ui-refresh .ui-refresh-down").html("加载更多");
                    loadMore(url, params);
                }

            }
        }

    });
    /* requestApi(url, params, function (res) {
     if (res.result == 1) {
     $("#ajaxStatus").hide();
     page++;
     if (res.data == '') {
     $(".ui-refresh .ui-refresh-down").html("没有更多内容了").unbind('click');
     } else {
     $(".ui-refresh .data-list").html(res.data);
     }
     }
     })*/
};

// Zepto.cookie plugin
//
// Copyright (c) 2010, 2012
// @author Klaus Hartl (stilbuero.de)
// @author Daniel Lacy (daniellacy.com)
//
// Dual licensed under the MIT and GPL licenses:
// http://www.opensource.org/licenses/mit-license.php
// http://www.gnu.org/licenses/gpl.html

/**
 * base64
 * Usage
 $.base64.encode( "this is a test" ) returns "dGhpcyBpcyBhIHRlc3Q="
 $.base64.decode( "dGhpcyBpcyBhIHRlc3Q=" ) returns "this is a test"
 */
$.base64 = (function ($) {
    var _PADCHAR = "=", _ALPHA = "ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789+/", _VERSION = "1.0";

    function _getbyte64(s, i) {
        var idx = _ALPHA.indexOf(s.charAt(i));
        if (idx === -1) {
            throw"Cannot decode base64"
        }
        return idx
    }

    function _decode(s) {
        var pads = 0, i, b10, imax = s.length, x = [];
        s = String(s);
        if (imax === 0) {
            return s
        }
        if (imax % 4 !== 0) {
            throw"Cannot decode base64"
        }
        if (s.charAt(imax - 1) === _PADCHAR) {
            pads = 1;
            if (s.charAt(imax - 2) === _PADCHAR) {
                pads = 2
            }
            imax -= 4
        }
        for (i = 0; i < imax; i += 4) {
            b10 = (_getbyte64(s, i) << 18) | (_getbyte64(s, i + 1) << 12) | (_getbyte64(s, i + 2) << 6) | _getbyte64(s, i + 3);
            x.push(String.fromCharCode(b10 >> 16, (b10 >> 8) & 255, b10 & 255))
        }
        switch (pads) {
            case 1:
                b10 = (_getbyte64(s, i) << 18) | (_getbyte64(s, i + 1) << 12) | (_getbyte64(s, i + 2) << 6);
                x.push(String.fromCharCode(b10 >> 16, (b10 >> 8) & 255));
                break;
            case 2:
                b10 = (_getbyte64(s, i) << 18) | (_getbyte64(s, i + 1) << 12);
                x.push(String.fromCharCode(b10 >> 16));
                break
        }
        return x.join("")
    }

    function _getbyte(s, i) {
        var x = s.charCodeAt(i);
        if (x > 255) {
            throw"INVALID_CHARACTER_ERR: DOM Exception 5"
        }
        return x
    }

    function _encode(s) {
        if (arguments.length !== 1) {
            throw"SyntaxError: exactly one argument required"
        }
        s = String(s);
        var i, b10, x = [], imax = s.length - s.length % 3;
        if (s.length === 0) {
            return s
        }
        for (i = 0; i < imax; i += 3) {
            b10 = (_getbyte(s, i) << 16) | (_getbyte(s, i + 1) << 8) | _getbyte(s, i + 2);
            x.push(_ALPHA.charAt(b10 >> 18));
            x.push(_ALPHA.charAt((b10 >> 12) & 63));
            x.push(_ALPHA.charAt((b10 >> 6) & 63));
            x.push(_ALPHA.charAt(b10 & 63))
        }
        switch (s.length - imax) {
            case 1:
                b10 = _getbyte(s, i) << 16;
                x.push(_ALPHA.charAt(b10 >> 18) + _ALPHA.charAt((b10 >> 12) & 63) + _PADCHAR + _PADCHAR);
                break;
            case 2:
                b10 = (_getbyte(s, i) << 16) | (_getbyte(s, i + 1) << 8);
                x.push(_ALPHA.charAt(b10 >> 18) + _ALPHA.charAt((b10 >> 12) & 63) + _ALPHA.charAt((b10 >> 6) & 63) + _PADCHAR);
                break
        }
        return x.join("")
    }

    return {decode: _decode, encode: _encode, VERSION: _VERSION}
}($));
$(function () {
    $(window).scroll(function () {
        var H = $(window).scrollTop();
        if (H > 600) {
            $(".all-goTop").fadeIn("fast")
        } else {
            $(".all-goTop").fadeOut("fast")
        }
    });
    $(".all-goTop").click(function () {
        $(window).scrollTop(0);
    })
});


$.cookie = function (name, value, options) {
    if (typeof value != 'undefined') { // name and value given, set cookie
        options = options || {};
        if (value === null) {
            value = '';
            options.expires = -1;
        }
        var expires = '';
        if (options.expires && (typeof options.expires == 'number' || options.expires.toUTCString)) {
            var date;
            if (typeof options.expires == 'number') {
                date = new Date();
                date.setTime(date.getTime() + (options.expires * 24 * 60 * 60 * 1000));
            } else {
                date = options.expires;
            }
            expires = '; expires=' + date.toUTCString(); // use expires attribute, max-age is not supported by IE
        }
        var path = options.path ? '; path=' + options.path : '';
        var domain = options.domain ? '; domain=' + options.domain : '';
        var secure = options.secure ? '; secure' : '';
        document.cookie = [name, '=', encodeURIComponent(value), expires, path, domain, secure].join('');
    } else { // only name given, get cookie
        var cookieValue = null;
        if (document.cookie && document.cookie != '') {
            var cookies = document.cookie.split(';');
            for (var i = 0; i < cookies.length; i++) {
                var cookie = $.trim(cookies[i]);
                // Does this cookie string begin with the name we want?
                if (cookie.substring(0, name.length + 1) == (name + '=')) {
                    cookieValue = decodeURIComponent(cookie.substring(name.length + 1));
                    break;
                }
            }
        }
        return cookieValue;
    }
};

function wechatImagePreview(container) {
    if (typeof window.WeixinJSBridge == 'undefined') {
        return false;
    }
    $(container + " img").live('click', function () {
        var currentSrc = $(this).attr('src');
        var images = $(container).find('img');
        var imageSrcList = [];
        $(images).each(function () {
            var imageSrc = this.src;
            if (imageSrc && imageSrc.length > 0) {
                imageSrcList.push(imageSrc);
            }
        });

        //imageSrcList = JSON.stringify(imageSrcList);
        WeixinJSBridge.invoke('imagePreview', {
            'current': currentSrc,
            'urls': imageSrcList
        });
    });
}
function checkLogin(callback) {
    var uid = app._uid;
    if (!(!isNaN(uid) && uid > 0)) {
        $(".dis_login").click(function (e) {
            e.stopPropagation();
        });
        $(".popup_window").click(function (e) {
            $('.popup_window').hide();
        });
        $(".popup_window").show();
        do_login(callback);
        return false;
    } else {
        callback();
        return true;
    }
}
function do_login(callback) {
    $("#loginForm :input").blur(function () {
        $(this).parent().next().removeClass("error").text("");
        if ($(this).is("#tel")) {
            var uname = $(this).val();
            var regExp = /^1[3|4|5|7|8]\d{9}$/;
            if (this.value == "") {
                var onMessage = "请输入手机号";
                $(this).parent().next().addClass("error").text(onMessage);
            }
            else if (!regExp.test(uname)) {
                var onMessage = "手机号输入不正确";
                $(this).parent().next().addClass("error").text(onMessage);
            }
        }
        if ($(this).is("#pwd")) {
            var pwd = $(this).val();
            var regExp = /^[a-zA-Z0-9!"\#$%&'()*+,-./:;<=>?@\[\\\]^_`\{\|\}\~]{6,18}$/;
            if (this.value == "") {
                var onMessage = "请输入密码";
                $(this).parent().next().addClass("error").text(onMessage);
            }
            else if (!regExp.test(pwd)) {
                var onMessage = "密码输入不正确";
                $(this).parent().next().addClass("error").text(onMessage);
            }
        }

    })
    /*.keyup(function () {
     $(this).triggerHandler("blur");
     });*/
    $("#mylogin").on("click", function () {
        $("#loginForm :input").trigger("blur");
        var numError = $("#loginForm .error").length;
        if (numError) {
            return false;
        }
        var phone = $("#tel").val();
        var pwd = $("#pwd").val();
        requestApi('/api/user/AjaxLogin', {phone: phone, pass: pwd}, function (res) {
            if (res.result == 1) {
                $(".popup_window").hide();
                app._uid = res.data.uid;
                callback();
            }
        })

    });
}

/*2017-03-03*/
window.Popup = function Popup(content) {
    layer.open({
        content: content
        , skin: 'msg'
        , time: 2 //2秒后自动关闭
    });
};
/*function app_login(uid, token, callback, platform) {
 /!*  if (app._uid) {
 return;
 }*!/

 uid = parseInt(uid);
 if (uid && token) {
 requestApi('/api/user/app_login', {uid: uid, token: token}, function (res) {
 if (res.result == 1) {
 if (platform == 'android') {
 eval("android." + callback + "()");
 } else {
 eval(callback + "();");
 }
 // alert(callback);
 // callback();//app方法
 }
 })
 }
 }*/


