define(function (require, exports) {
    window.inAjaxProcess = false;
    exports.requestApi = function (uri, data, func, endProcess, hideTip) {
        // 手动更改请求，立即结束ajax状态
        if (endProcess) {
            window.inAjaxProcess = false;
        }
        if (!inAjaxProcess) {
            var param = {
                url: uri,
                async: true,
                data: data,
                dataType: 'json',
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

                        if (!ajaxTip.layout && $('#ajaxStatus').css('display') !== 'none') {
                            //tip.showTip('ok', '操作成功！', 2000);
                            tip.hideTip();
                        } else if (ajaxTip.layout && $('#ajaxStatus', parent.document).css('display') !== 'none') {
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
                        tip.showTip('err', res.error.more ? (res.error.msg) : res.error.msg, 3000);
                    }
                }
            };

            $.ajax(param);
        }
    };
    // status : err, ok, wait, warming
    exports.showTip = function (status, tip, time, callback) {
        var t = new ajaxTip();
        t.showTip(status, tip, time, callback);
    };

    exports.hideTip = function () {
        var t = new ajaxTip();
        t.hideTip();
    };
    /**
     * ajax tip
     *
     * @constructor
     */

    function ajaxTip() {

    }

    ajaxTip.ajaxTimer = null;
    ajaxTip.tip = null;
    ajaxTip.time = 0;
    ajaxTip.status = null;
    ajaxTip.layout = $('#ajaxStatus').hasClass('layout') ? true : false; //当前窗口是否是子窗口 layout为子窗口
    ajaxTip.prototype = {
        showTip: function (status, tip, time, callback) {
            ajaxTip.status = status;
            ajaxTip.tip = tip;
            ajaxTip.time = time;
            if (ajaxTip.layout) {
                $('#ajaxStatus', parent.document).show();
                $('#ajaxStatus #ajaxTip', parent.document).html(ajaxTip.tip).removeClass().addClass(ajaxTip.status);
            } else {
                $('#ajaxStatus').show();
                $('#ajaxStatus #ajaxTip').html(ajaxTip.tip).removeClass().addClass(ajaxTip.status);
            }


            if (ajaxTip.time) {
                if (ajaxTip.ajaxTimer) {
                    clearTimeout(ajaxTip.ajaxTimer);
                }
                ajaxTip.ajaxTimer = setTimeout(function () {
                    ajaxTip.layout ? $('#ajaxStatus', parent.document).hide() : $('#ajaxStatus').hide();
                    ajaxTip.inProcess = true;
                    if (typeof callback == 'function') {
                        callback();
                    }
                }, ajaxTip.time);
            }
        },
        hideTip: function () {
            ajaxTip.layout ? $('#ajaxStatus', parent.document).hide() : $('#ajaxStatus').hide();
        },
        setTip: function (tip) {
            ajaxTip.layout ? $('#ajaxStatus #ajaxTip', parent.document).html(tip) : $('#ajaxStatus #ajaxTip').html(tip);
        }
    };
    window.tip = new ajaxTip();


    // popup basic show hide
    exports.showPop = function (popElem) {

        var pop = new popup(popElem);
        pop.show();


    };


    // popup basic show hide
    exports.hidePop = function (popElem) {
        var pop = new popup(popElem);
        pop.hide();
    };


    // popup
    function popup(popElem) {

        this.widget = popElem ? popElem : '.popup-wrap';

        var _this = this;
        // close click
        $('.popup-widget .popup-close').on('click', function () {
            _this.hide();
        });

        // bg click
        /*$('.popup-wrap').on('click', function (e) {
         if (e.target !== this) return;

         _this.hide();

         e.stopImmediatePropagation();
         });*/
    }

    popup.prototype = {
        'show': function () {
            $(this.widget).fadeIn();
        },
        'hide': function () {
            $('.popup-wrap').hide();
        }
    };

    //ajax 请求公用参数
    function ajaxparam(btn) {
        if (!inAjaxProcess) {
            var param = {
                dataType: 'json',
                type: 'post',
                beforeSend: function () {
                    tip.showTip('wait', '处理请求...');
                    if (inAjaxProcess == true) {
                        tip.showTip('wait', '正在请求...');
                        return false;
                    }
                    // 正在处理状态
                    inAjaxProcess = true;
                },
                timeout: function () {
                    tip.showTip('err', '请求超时,请重试！', 3000);
                },
                abort: function () {
                    tip.showTip('err', '网路连接被中断！', 3000);
                },
                parsererror: function () {
                    tip.showTip('err', '运行时发生错误！', 3000);
                },
                complete: function () {
                    setTimeout(function () {
                        if ($('#ajaxStatus').css('display') !== 'none') {
                            tip.showTip('ok', '操作成功！', 3000);
                        }
                        // 清除处理状态
                        inAjaxProcess = false;
                    }, ajaxTip.time);// 最后一次tip时间
                },
                error: function () {
                    $(btn).attr('disabled', false);
                    tip.showTip('err', '内部程序错误！', 3000);
                }
            };
        }
        return param;
    }

    function showErrorCode(err) {
        var tip = new ajaxTip();
        tip.showTip('err', err.msg || err.more, 3000);
    }

    /*异步加载 列表*/
    exports.pageList = function (option) {
        var opt = {
            url: '',
            page: 1,
            limit: 10,
            order: '',
            sort: '',
            key: '',
            callback:null
        };
        //重新加载
        var reload = function () {

            $(".listData").html('<td colspan="17" style="height:100px;line-height:100px" class="center">' +
                '<img style="width: 40px" src="/srv/static/panel/images/admin/loading2.gif"></td>');
            exports.requestApi(opt.url, opt, function (res) {
                if (res.result == 1) {
                    var html = "";
                    if (res.data.list.length > 0) {
                        for (var i in res.data.list) {
                            html += res.data.list[i];
                        }
                        $(".listData").html(html);
                    }
                    $(".pageBar").html(res.data.bar);
                    if(typeof opt.callback=='function'){
                        opt.callback();
                    }
                }
            }, true, true);
        };
        if (option) {
            $.extend(opt, option);
        }
        if (opt.url) {
            reload();
            //分页
            $(".pageBar").on("click", 'li a', function () {
                if ($(this).parent().hasClass("disabled")) {
                    return;
                }
                if ($(this).attr("data-id") !== undefined) {
                    opt.page = $(this).attr('data-id');
                    reload();
                }
            }).on('blur', '.page', function () {
                var page = parseInt($(this).val());
                var limit_page = parseInt($(this).attr('data-limit'));

                if (!isNaN(page) && page >= 1) {
                    if (page > limit_page) {
                        opt.page = limit_page;
                    } else {
                        opt.page = page;
                    }
                    reload();
                }
            }).on('blur', '.page_limit', function () {
                var limit = parseInt($(this).val());
                if (!isNaN(limit) && limit >= 1) {
                    if (limit > 100) {
                        opt.limit = 100;
                    } else {
                        opt.limit = limit;
                    }
                    reload();
                }
            });
            //搜索
            $(".btnSearch").on('click', function () {
                $.extend(opt, $("form").serializeObject());
                opt.page = 1;
                reload();
            });
            //排序
            $(".list .arrow").on('click', function () {
                //之前已被选中
                if ($(this).hasClass("active")) {
                    if ($(this).data('sort') == 'desc') {
                        $(this).data('sort', 'asc');
                        $(this).find('.arrow-down').addClass("disabled").removeClass("active");
                        $(this).find('.arrow-up').addClass("active").removeClass("disabled");

                        opt.sort = 'asc';
                    } else {
                        $(this).data('sort', 'desc');
                        $(this).find('.arrow-up').addClass("disabled").removeClass("active")
                        $(this).find('.arrow-down').addClass("active").removeClass("disabled");
                        opt.sort = 'desc';
                    }
                }
                //之前没有被选中
                else {
                    $(".list .arrow.active").find(".active").removeClass('active');
                    $(".list .arrow.active").removeClass("active");
                    $(this).addClass("active").data('sort', 'asc');

                    $(".list .arrow").find('.arrow-down').removeClass("disabled").removeClass("active");
                    $(".list .arrow").find('.arrow-up').removeClass("disabled").removeClass("active");

                    $(this).find('.arrow-down').addClass("disabled").removeClass("active");
                    $(this).find('.arrow-up').addClass("active").removeClass("disabled");
                    opt.sort = 'asc';
                    opt.order = $(this).data('order');
                }
                opt.page = 1;
                reload();
            });
            //tab 切换
            $(".tabs .tab").on('click', function () {
                var keys = [];
                $(".tabs .tab").each(function(){
                    keys.push($(this).data('key'));
                });
                var key = $(this).data('key');
                var val = $(this).data('val');
                if ($(this).hasClass('active')) {
                    //opt[key] = '';
                    //$(this).removeClass("active");
                } else {
                    //$(this).siblings('.tab[data-key=' + key + ']').removeClass('active');
                    for (var item in keys)
                    {
                        if(keys[item] !== key)
                            delete opt[keys[item]];
                    }
                    $(this).siblings('.tab').removeClass('active');
                    opt[key] = val;
                    $(this).addClass("active");
                    opt.page =1;
                    reload();
                }

            });


        }
        return reload;

    };
    // all select status
    exports.selectCheckbox = function () {
        // tr click
        $('.list .listData .item .chk').on('click', function (e) {
            var chk_id = $(this).attr('data-id');

            if ($(this).prop('checked') == true || $(this).prop('checked') == 'checked') {
                $(this).parents('.item[data-id=' + chk_id + ']').addClass('selected');
            } else {
                $(this).parents('.item[data-id=' + chk_id + ']').removeClass('selected');
            }

            e.stopImmediatePropagation();
        });

        // checkbox click
        $('.list .listData .item').on('click', function (e) {
            var chk_id = $(this).find('.chk').attr('data-id');
            if ($(this).find('.chk').prop('checked') == true || $(this).find('.chk').prop('checked') == 'checked') {
                $(this).removeClass('selected');
                $(this).find('.chk').attr('checked', false);
            } else {
                $(this).addClass('selected');
                $(this).find('.chk').attr('checked', true);
            }
        });

        // 全选
        $(".selectAll").on('click', function () {
            $(this).addClass('current');
            $(this).siblings('.btn-light').removeClass('current');
            $(".list .listData .item input.chk").attr("checked", true);
            $('.list .listData .item').addClass('selected');
        });
        // 全不选
        $(".selectNone").on('click', function () {
            $(this).siblings('.btn-light').removeClass('current');
            $(".list .listData .item input.chk").attr("checked", false);
            $('.list .listData .item').removeClass('selected');
        });
        // 反选
        $(".selectInvert").on('click', function () {
            $(this).addClass('current');
            $(this).siblings('.btn-light').removeClass('current');
            $(".list .listData .item input.chk").each(function () {
                $(this).attr("checked", !this.checked);//反选
                var chk_id = $(this).attr('data-id')
                if ($(this).attr('checked') == true || $(this).attr('checked') == 'checked') {
                    $(this).parents('.item[data-id=' + chk_id + ']').addClass('selected');
                } else {
                    $(this).parents('.item[data-id=' + chk_id + ']').removeClass('selected');
                }
            });
        });
    };

    // all select status
    exports.selectNone = function () {
        // 全不选
        $(".list .listData input.chk").attr("checked", false);
        $('.list .listData .item').removeClass('selected');
    };

    // go to the input page
    exports.goPage = function (btn) {
        $(btn).on('click', function () {
            var page = parseInt($(this).siblings('.pageVal').val());
            var requestSting = $(this).attr('data-string');
            var suffix = $(this).attr('data-suffix');
            var total = $(this).attr('data-total');
            page = isNaN(page) ? 1 : page;
            page = page >= total ? total : page;
            page = page <= 1 ? 1 : page;
            var url = $(this).attr('data-url') + '/p/' + page + suffix;
            url = requestSting ? url + '?' + requestSting : url;
            window.location.href = url;
        });
    };

    window.checkField = function (elem, msg, regx) {
        var val = $(elem).val();
        var dmsg = $(elem).attr('placeholder');

        if ($(elem).val() == '') {
            $(elem).siblings('.tip').show().addClass('err');
            $(elem).siblings('.tip').html(dmsg);
            return false;
        } else {
            if (regx) {
                if (!new RegExp(regx).test(val)) {
                    $(elem).siblings('.tip').show().addClass('err');
                    $(elem).siblings('.tip').html(msg || dmsg);
                    return false;
                } else {
                    $(elem).siblings('.tip').hide().removeClass('err');
                    $(elem).siblings('.tip').html('');
                }
            }
            $(elem).siblings('.tip').hide().removeClass('err');
            $(elem).siblings('.tip').html('');
        }

        return true;
    };


});

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
})(jQuery);
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
var base64 = {
    // 转码表
    table: [
        'A', 'B', 'C', 'D', 'E', 'F', 'G', 'H',
        'I', 'J', 'K', 'L', 'M', 'N', 'O', 'P',
        'Q', 'R', 'S', 'T', 'U', 'V', 'W', 'X',
        'Y', 'Z', 'a', 'b', 'c', 'd', 'e', 'f',
        'g', 'h', 'i', 'j', 'k', 'l', 'm', 'n',
        'o', 'p', 'q', 'r', 's', 't', 'u', 'v',
        'w', 'x', 'y', 'z', '0', '1', '2', '3',
        '4', '5', '6', '7', '8', '9', '+', '/'
    ],
    UTF16ToUTF8: function (str) {
        var res = [], len = str.length;
        for (var i = 0; i < len; i++) {
            var code = str.charCodeAt(i);
            if (code > 0x0000 && code <= 0x007F) {
                // 单字节，这里并不考虑0x0000，因为它是空字节
                // U+00000000 – U+0000007F  0xxxxxxx
                res.push(str.charAt(i));
            } else if (code >= 0x0080 && code <= 0x07FF) {
                // 双字节
                // U+00000080 – U+000007FF  110xxxxx 10xxxxxx
                // 110xxxxx
                var byte1 = 0xC0 | ((code >> 6) & 0x1F);
                // 10xxxxxx
                var byte2 = 0x80 | (code & 0x3F);
                res.push(
                    String.fromCharCode(byte1),
                    String.fromCharCode(byte2)
                );
            } else if (code >= 0x0800 && code <= 0xFFFF) {
                // 三字节
                // U+00000800 – U+0000FFFF  1110xxxx 10xxxxxx 10xxxxxx
                // 1110xxxx
                var byte1 = 0xE0 | ((code >> 12) & 0x0F);
                // 10xxxxxx
                var byte2 = 0x80 | ((code >> 6) & 0x3F);
                // 10xxxxxx
                var byte3 = 0x80 | (code & 0x3F);
                res.push(
                    String.fromCharCode(byte1),
                    String.fromCharCode(byte2),
                    String.fromCharCode(byte3)
                );
            } else if (code >= 0x00010000 && code <= 0x001FFFFF) {
                // 四字节
                // U+00010000 – U+001FFFFF  11110xxx 10xxxxxx 10xxxxxx 10xxxxxx
            } else if (code >= 0x00200000 && code <= 0x03FFFFFF) {
                // 五字节
                // U+00200000 – U+03FFFFFF  111110xx 10xxxxxx 10xxxxxx 10xxxxxx 10xxxxxx
            } else /** if (code >= 0x04000000 && code <= 0x7FFFFFFF)*/ {
                // 六字节
                // U+04000000 – U+7FFFFFFF  1111110x 10xxxxxx 10xxxxxx 10xxxxxx 10xxxxxx 10xxxxxx
            }
        }

        return res.join('');
    },
    UTF8ToUTF16: function (str) {
        var res = [], len = str.length;
        var i = 0;
        for (var i = 0; i < len; i++) {
            var code = str.charCodeAt(i);
            // 对第一个字节进行判断
            if (((code >> 7) & 0xFF) == 0x0) {
                // 单字节
                // 0xxxxxxx
                res.push(str.charAt(i));
            } else if (((code >> 5) & 0xFF) == 0x6) {
                // 双字节
                // 110xxxxx 10xxxxxx
                var code2 = str.charCodeAt(++i);
                var byte1 = (code & 0x1F) << 6;
                var byte2 = code2 & 0x3F;
                var utf16 = byte1 | byte2;
                res.push(Sting.fromCharCode(utf16));
            } else if (((code >> 4) & 0xFF) == 0xE) {
                // 三字节
                // 1110xxxx 10xxxxxx 10xxxxxx
                var code2 = str.charCodeAt(++i);
                var code3 = str.charCodeAt(++i);
                var byte1 = (code << 4) | ((code2 >> 2) & 0x0F);
                var byte2 = ((code2 & 0x03) << 6) | (code3 & 0x3F);
                utf16 = ((byte1 & 0x00FF) << 8) | byte2
                res.push(String.fromCharCode(utf16));
            } else if (((code >> 3) & 0xFF) == 0x1E) {
                // 四字节
                // 11110xxx 10xxxxxx 10xxxxxx 10xxxxxx
            } else if (((code >> 2) & 0xFF) == 0x3E) {
                // 五字节
                // 111110xx 10xxxxxx 10xxxxxx 10xxxxxx 10xxxxxx
            } else /** if (((code >> 1) & 0xFF) == 0x7E)*/ {
                // 六字节
                // 1111110x 10xxxxxx 10xxxxxx 10xxxxxx 10xxxxxx 10xxxxxx
            }
        }

        return res.join('');
    },
    encode: function (str) {
        if (!str) {
            return '';
        }
        var utf8 = this.UTF16ToUTF8(str); // 转成UTF8
        var i = 0; // 遍历索引
        var len = utf8.length;
        var res = [];
        while (i < len) {
            var c1 = utf8.charCodeAt(i++) & 0xFF;
            res.push(this.table[c1 >> 2]);
            // 需要补2个=
            if (i == len) {
                res.push(this.table[(c1 & 0x3) << 4]);
                res.push('==');
                break;
            }
            var c2 = utf8.charCodeAt(i++);
            // 需要补1个=
            if (i == len) {
                res.push(this.table[((c1 & 0x3) << 4) | ((c2 >> 4) & 0x0F)]);
                res.push(this.table[(c2 & 0x0F) << 2]);
                res.push('=');
                break;
            }
            var c3 = utf8.charCodeAt(i++);
            res.push(this.table[((c1 & 0x3) << 4) | ((c2 >> 4) & 0x0F)]);
            res.push(this.table[((c2 & 0x0F) << 2) | ((c3 & 0xC0) >> 6)]);
            res.push(this.table[c3 & 0x3F]);
        }

        return res.join('');
    },
    decode: function (str) {
        if (!str) {
            return '';
        }

        var len = str.length;
        var i = 0;
        var res = [];

        while (i < len) {
            code1 = this.table.indexOf(str.charAt(i++));
            code2 = this.table.indexOf(str.charAt(i++));
            code3 = this.table.indexOf(str.charAt(i++));
            code4 = this.table.indexOf(str.charAt(i++));

            c1 = (code1 << 2) | (code2 >> 4);
            c2 = ((code2 & 0xF) << 4) | (code3 >> 2);
            c3 = ((code3 & 0x3) << 6) | code4;

            res.push(String.fromCharCode(c1));

            if (code3 != 64) {
                res.push(String.fromCharCode(c2));
            }
            if (code4 != 64) {
                res.push(String.fromCharCode(c3));
            }

        }

        return this.UTF8ToUTF16(res.join(''));
    }
};
function parseURL(url) {
    var a = document.createElement('a');
    a.href = url;
    alert(url);
    return {
        source: url,
        protocol: a.protocol.replace(':', ''),
        host: a.hostname,
        port: a.port,
        query: a.search,
        params: (function () {
            var ret = {},
                seg = a.search.replace(/^\?/, '').split('&'),
                len = seg.length, i = 0, s;
            for (; i < len; i++) {
                if (!seg[i]) {
                    continue;
                }
                s = seg[i].split('=');
                ret[s[0]] = s[1];
            }
            return ret;
        })(),
        file: (a.pathname.match(/\/([^\/?#]+)$/i) || [, ''])[1],
        hash: a.hash.replace('#', ''),
        path: a.pathname.replace(/^([^\/])/, '/$1'),
        relative: (a.href.match(/tps?:\/\/[^\/]+(.+)/) || [, ''])[1],
        segments: a.pathname.replace(/^\//, '').split('/')
    };
}
//分页 修改每页显示的数量
$("#limit").on('keydown', function () {
    if (event.keyCode !== 13) {
        return;
    }
    var limit = 20;
    var val = $(this).val();
    if (val != '' && !isNaN(val)) {
        limit = parseInt($(this).val())
    }
    var href = window.location.href;
    var url = parseURL(href);
    console.log(href);
    if (!$.isEmptyObject(url.params)) {
        if (url.params.limit == limit) {
            return
        }
        url.params.limit = limit;
        url.params.p = 1;
        var query = "";
        var j = 0;
        for (var i in url.params) {

            if (j == 0) {
                query += "?" + i + "=" + url.params[i];
            } else {
                query += "&" + i + "=" + url.params[i];
            }
            j++;
        }
        window.location.href = url.protocol + "://" + url.host + url.path + query;
    } else {
        window.location.href = href + '?limit=' + limit;
    }

});
//分页 修改页数
$("#page").on('keydown', function () {

    if (event.keyCode !== 13) {
        return;
    }
    var page = 1;
    var val = $(this).val();
    if (val != '' && !isNaN(val)) {
        page = parseInt($(this).val())
    }
    var href = window.location.href;
    var url = parseURL(href);

    if (!$.isEmptyObject(url.params)) {
        if (url.params.p == page) {
            return
        }
        url.params.p = page;
        var query = "";
        var j = 0;
        for (var i in url.params) {
            if (j == 0) {
                query += "?" + i + "=" + url.params[i];
            } else {
                query += "&" + i + "=" + url.params[i];
            }
            j++;
        }
        window.location.href = url.protocol + "://" + url.host + url.path + query;
    } else {
        window.location.href = href + '?p=' + page;
    }

});