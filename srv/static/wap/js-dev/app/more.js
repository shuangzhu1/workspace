define(function (require, exports) {
    var base = require('base');
    require("jquery/jquery.more.class.js");
    var spinner_code = "<li style='text-align:center; margin:0.25rem;list-style-type: none;font-size:0.8rem;'><img style='width:1rem' src='/static/wap/images/loading.gif' />  数据加载中...</li>";
    var Item_Wrap_Elem = "#dataWrap02";
    var Item_Elem = '.item';

    function Fresh() {
    }

    Fresh.prototype = {
        more: function (url, params, callback, Item_Wrap_Elem, Item_Elem, opts) {
            var default_opt = {
                'address': url,
                'params': params,
                'spinner_code': spinner_code,
                'template': Item_Elem,
                'callback': callback,
                'trigger': '.get_more'
            };
            if (typeof opts == 'object') {
                $.extend(default_opt, opts);
            }
            $(Item_Wrap_Elem).more(default_opt);
        }
    };
    exports.more = function (url, params, callback, wrap_elem, item_elem, opts) {
        // 重新加载
        exports.reload(url, params, callback, wrap_elem, item_elem, opts);
    };

// 重新过滤
    exports.reload = function (url, params, callback, wrap_elem, item_elem, opts) {
        if (wrap_elem) {
            Item_Wrap_Elem = wrap_elem;
        }
        if (item_elem) {
            Item_Elem = item_elem;
        }
        $(Item_Wrap_Elem).html("<li class='topic-item " + Item_Elem.substr(1) + "'></li><li href='javascript:;' class='get_more'></li>");
        var fresh = new Fresh();
        fresh.more(url, params, callback, Item_Wrap_Elem, Item_Elem, opts);
    }
})