define(function (require, exports) {
    var base = require('base');
    require("jquery/jquery.more.js");
    var spinner_code = "<li style='text-align:center; margin:10px;list-style-type: none;'><img src='/srv/static/wap/images/loading.gif' />  数据加载中...</li>";
    var Item_Wrap_Elem = "#dataWrap02";
    var Item_Elem = '.item';

    function Fresh() {
    }

    Fresh.prototype = {
        more: function (url, params, callback) {
            this.url = url;
            $(Item_Wrap_Elem).more(
                {
                    'address': url,
                    'params': params,
                    'spinner_code': spinner_code,
                    'template': Item_Elem,
                    'callback': callback
                }
            );
            $(window).scroll(function () {
                // alert($(Item_Elem).length);
                if ($(Item_Elem).length > 0) {
                    if ($(window).scrollTop() == $(document).height() - $(window).height()) {
                        $('.get_more').click();
                    }
                }
            });
            $('.filter-list').css({
                'max-height': $(window).height() - 89
            });

        }
    };
    exports.more = function (url, params, callback) {
        // 重新加载
        exports.reload(url, params, callback);
    };

// 重新过滤
    exports.reload = function (url, params, callback) {
        $(Item_Wrap_Elem).html(" <li class='topic-item item'></li><li href='javascript:;' class='get_more'></li>");
        var fresh = new Fresh();
        fresh.more(url, params, callback);
    }
})