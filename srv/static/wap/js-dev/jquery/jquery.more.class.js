//定义构造函数
var more = function (ele, opt) {
    this.ele = ele;
    this.target = null;
    this.template = null;
    this.lock = false;
    this.no_data = false;
    this.variables = {
        'last': 1,
        'page': 1,
        'pre_page': 0,
        'last_id': ''
    };
    this.settings = {
        'amount': '10',
        'address': '',
        'params': "",
        'format': 'json',
        'template': '.single_item',
        'trigger': '.get_more',
        'scroll': 'false',
        'offset': '100',
        'spinner_code': '',
        'callback': '',
    };
    this.variables.last = this.variables.page = 1;
    $.extend(this.settings, opt);
};

//定义方法
more.prototype = {
    init: function () {
        var __this = this;
        __this.template = $(__this.ele).children(__this.settings.template).wrap('<div/>').parent();
        __this.template.css('display', 'none');
        $(__this.ele).append('<div class="more_loader_spinner">' + __this.settings.spinner_code + '</div>')
        $(__this.ele).children(__this.settings.template).remove();
        __this.target = $(__this.ele);
        if (__this.settings.scroll == 'false') {
            $(__this.ele).on('click', __this.settings.trigger, __this.get_data);
            __this.get_data();
        }
        else {
            if ($(__this.ele).height() <= $(__this.ele).attr('scrollHeight')) {
                __this.get_data(__this.settings.amount * 2);
                //  __this.target.more('get_data');
            }
            $(__this.ele).on('scroll.more', __this.check_scroll);
        }
        $(window).scroll(function () {
            // alert($(Item_Elem).length);
            if (!$(__this.ele).is(":hidden")) {
                if ($(__this.template).length > 0) {
                    if ($(window).scrollTop() == $(document).height() - $(window).height()) {
                        __this.get_data(__this.settings.amount * 2);
                    }
                }
            }

        });
    },
    check_scroll: function () {
        var __this = this;
        if ((__this.target.scrollTop() + __this.target.height() + parseInt(__this.settings.offset)) >= __this.target.attr('scrollHeight') && __this.lock == false) {
            __this.target.more('get_data');
        }
    },
    debug: function () {
        var __this = this;
        var debug_string = '';
        $.each(__this.variables, function (k, v) {
            debug_string += k + ' : ' + v + '\n';
        });
        alert(debug_string);
    },
    remove: function () {
        var __this = this;
        __this.target.children(__this.settings.trigger).unbind('.more');
        __this.target.unbind('.more');
        __this.target.children(__this.settings.trigger).remove();
    },
    add_elements: function (data) {
        var __this = this;
        //alert('adding elements')
        var root = __this.target;
        //   alert(root.attr('id'))
        var counter = 0;

        if (data) {
            if (data['data']['count'] == 0 && __this.variables.page == 1) {
                root.children(__this.settings.trigger).before('<li class="noData">暂无数据</li>');
                // window.scrollTo(0, 0);
            }
            if (data['data']['data_list'].length == 0) {
                __this.target.children('.more_loader_spinner').hide();

            } else {
                $(data['data']['data_list']).each(function () {
                    counter++;
                    var t = __this.template;
                    $.each(this, function (key, value) {
                        t.html(value);
                    });
                    //t.attr('id', 'more_element_'+ (variables.last++))
                    if (__this.settings.scroll == 'true') {
                        //    root.append(t.html())
                        root.children('.more_loader_spinner').before(t.html())
                    } else {
                        //    alert('...')

                        root.children(__this.settings.trigger).before(t.html())

                    }

                    root.children(__this.settings.template + ':last').attr('id', 'more_element_' + ((__this.variables.last++) + 1))
                    __this.variables.last++
                });
                __this.variables.page++;
            }
            if (data['data']['last_id'] !== undefined) __this.variables.last_id = data['data']['last_id'];
        }
        else {
            __this.remove();
        }
        __this.target.children('.more_loader_spinner').hide();
        if (counter < __this.settings.amount) {
            __this.no_data = true;
        }
        __this.settings.callback();

    },
    get_data: function () {
        var __this = this;
        if (!__this.lock && !__this.no_data) {
            var ile;
            __this.target.children(".more_loader_spinner").css('display', 'block');
            $(__this.settings.trigger).css('display', 'none');
            if (typeof(arguments[0]) == 'number') ile = arguments[0];
            else {
                ile = __this.settings.amount;
            }
            __this.lock = true;
            setTimeout(function () {
                var params;
                if (__this.settings.params == '') {
                    params = {
                        page: __this.variables.page,
                        limit: ile
                    }
                } else {
                    params = __this.settings.params;
                    params['page'] = __this.variables.page;
                    params['limit'] = __this.settings.amount;
                    params['last_id'] = __this.variables.last_id;
                    __this.variables.pre_page = __this.variables.page;
                }
                /* window.requestApi(settings.address, params, function (data) {
                 $(settings.trigger).css('display', 'block');
                 methods.add_elements(data);
                 lock = false;
                 $("img").lazyload({effect: "fadeIn"});
                 });*/
                $.ajax({
                    url: __this.settings.address,
                    data: params,
                    type: 'post',
                    async: true,
                    dataType: __this.settings.format,
                    success: function (res_data) {
                        //console.log("res");
                        //console.log(res_data);
                        $(__this.settings.trigger).css('display', 'block');
                        __this.add_elements(res_data);
                        //baguetteBox.run('.gallery');
                        /*  $('.lightGallery').lightGallery({
                         mode: "lg-slide",
                         speed: 300,
                         scale: 2,
                         keypress: true,
                         enableZoomAfter: 300
                         });*/
                    },
                    error: function () {

                    },
                    beforeSend: function () {
                        if (window.inAjaxProcess) {
                            return false;
                        }
                        // 正在处理状态
                        window.inAjaxProcess = true;
                    },
                    complete: function () {
                        window.inAjaxProcess = false;
                        __this.lock = false;
                    }
                })
            }, 200);

        }
        // }
    }
};

//在插件中使用Beautifier对象
$.fn.more = function (options) {
    //创建Beautifier的实体
    var load_more = new more(this, options);
    //调用其方法
    load_more.init();
};