/**
 * Created by ykuang on 2017/7/3.
 */
var pager = function (ele, opt) {
    this.ele = ele;
    this.no_data = false;

    this.settings = {
        'page': 1,
        'limit': '10', //每页显示的数量
        'params': {},
        'format': 'json',
        'callback': '',
        'url': '',
    };
    this.settings.page = 1;
    $.extend(this.settings, opt);
};
pager.prototype = {
    init: function () {
        var __this = this;
        __this.ele.html("<p style='text-align: center;height: 100px;line-height: 100px;vertical-align: middle'><img src='/srv/static/panel/images/admin/loading2.gif'/>&nbsp;数据加载中.. </p>");
        __this.get_data();
        $(__this.ele).unbind('click').on('click', '.pageBar li a', function (e) {
            if ($(this).parent().hasClass("disabled")) {
                return;
            }
            if ($(this).attr("data-id") !== undefined) {
                __this.settings.page = $(this).attr('data-id');
                __this.get_data();
            }
            e.preventDefault();
            e.stopPropagation();
        });
        //排序
        $(__this.ele).parent().find(".arrow").unbind('click').on('click', function (e) {
            //之前已被选中
            if ($(this).hasClass("active")) {
                if ($(this).data('sort') == 'desc') {
                    $(this).data('sort', 'asc');
                    $(this).find('.arrow-down').addClass("disabled").removeClass("active");
                    $(this).find('.arrow-up').addClass("active").removeClass("disabled");

                    __this.settings.params.sort = 'asc';
                } else {
                    $(this).data('sort', 'desc');
                    $(this).find('.arrow-up').addClass("disabled").removeClass("active")
                    $(this).find('.arrow-down').addClass("active").removeClass("disabled");
                    __this.settings.params.sort = 'desc';
                }
                __this.settings.params.order = $(this).data('order');
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
                __this.settings.params.sort = 'asc';
                __this.settings.params.order = $(this).data('order');
            }
            __this.settings.page = 1;
            __this.get_data();
            e.preventDefault();
            e.stopPropagation();
        });
    },
    get_data: function () {
        var __this = this;
        if (!__this.no_data) {
            var params;
            params = __this.settings.params;
            params['page'] = __this.settings.page;
            params['limit'] = __this.settings.limit;
            /* window.requestApi(settings.address, params, function (data) {
             $(settings.trigger).css('display', 'block');
             methods.add_elements(data);
             lock = false;
             $("img").lazyload({effect: "fadeIn"});
             });*/
            $.ajax({
                url: __this.settings.url,
                data: params,
                type: 'post',
                async: true,
                dataType: __this.settings.format,
                success: function (res_data) {
                    __this.ele.html(res_data.data);
                },
                error: function () {

                },
                /*  beforeSend: function () {
                 __this.ele.html("<p style='text-align: center;height: 100px;line-height: 100px;vertical-align: middle'><img src='/static/panel/images/admin/loading2.gif'/>&nbsp;数据加载中.. </p>");
                 if (window.inAjaxProcess) {
                 return false;
                 }
                 // 正在处理状态
                 window.inAjaxProcess = true;
                 },
                 complete: function () {
                 window.inAjaxProcess = false;
                 __this.lock = false;
                 }*/
            })
        }
        // }
    }

};
$.fn.pager = function (options) {
    if (options) {
        if (options.ele === undefined) options.ele = $(this);
    } else {
        options = {ele: $(this)};
    }
    var load_more = new pager(this, options);
    load_more.init();
};