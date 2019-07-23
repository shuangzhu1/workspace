/**
 * 加载更多
 * ykuang 2017-03-03
 *
 * **/
(function ($) {
    window.inAjaxProcess = false;
    var methods =
    {
        init: function (options) {
            return this.each(function () {
                if (options) {
                    $.extend(methods.settings, options);
                }
                methods.template = $(this).children(methods.settings.template).wrap('<div/>').parent();
                methods.template.css('display', 'none');
                $(this).append('<div class="more_loader_spinner">' + methods.settings.spinner_code + '</div>')
                $(this).children(methods.settings.template).remove();
                methods.target = $(this);
                if (methods.settings.scroll == 'false') {
                    console.log($(this).attr('class'));
                    $(this).on('click', methods.settings.trigger, methods.get_data);
                    $(this).more('get_data');
                }
                else {
                    if ($(this).height() <= $(this).attr('scrollHeight')) {
                        methods.target.more('get_data', methods.settings.amount * 2);
                    }
                    $(this).on('scroll.more', methods.check_scroll);
                }
            })
        },
        check_scroll: function () {
            if ((methods.target.scrollTop() + methods.target.height() + parseInt(methods.settings.offset)) >= methods.target.attr('scrollHeight') && methods.lock == false) {
                methods.target.more('get_data');
            }
        },
        debug: function () {
            var debug_string = '';
            $.each(methods.variables, function (k, v) {
                debug_string += k + ' : ' + v + '\n';
            });
            alert(debug_string);
        },
        remove: function () {
            methods.target.children(methods.settings.trigger).unbind('.more');
            methods.target.unbind('.more');
            methods.target.children(methods.settings.trigger).remove();
        },
        add_elements: function (data) {
            //alert('adding elements')
            var root = methods.target;
            //   alert(root.attr('id'))
            var counter = 0;
            if (data) {
                $(".data_count").html(data['data']['count']);
                if ($.trim(methods.settings.params.key).length > 0) {
                    $('.search_result').show().find('.search_key').html(this.settings.params.key);
                } else {
                    $('.search_result').hide().find('.search_key').html('');
                }

                if (data['data']['count'] == 0 && methods.variables.page == 1) {
                    root.children(methods.settings.trigger).before('<li class="noData">暂无数据</li>');
                    window.scrollTo(0, 0);
                }

                $(data['data']['data_list']).each(function () {
                    counter++;
                    var t = methods.template;
                    $.each(this, function (key, value) {
                        t.html(value);
                    });
                    //t.attr('id', 'more_element_'+ (variables.last++))
                    if (methods.settings.scroll == 'true') {
                        //    root.append(t.html())
                        root.children('.more_loader_spinner').before(t.html())
                    } else {
                        //    alert('...')

                        root.children(methods.settings.trigger).before(t.html())

                    }

                    root.children(methods.settings.template + ':last').attr('id', 'more_element_' + ((methods.variables.last++) + 1))

                });
                methods.variables.page++;

            }
            else  methods.remove();
            methods.target.children('.more_loader_spinner').css('display', 'none');
            if (counter < methods.settings.amount) methods.remove()
            methods.settings.callback();

        },
        get_data: function () {
            console.log(this);
            console.log(methods.variables);
            console.log(methods.settings);
            console.log(methods.target);
            var ile;
            methods.lock = true;
            methods.target.children(".more_loader_spinner").css('display', 'block');

            $(methods.settings.trigger).css('display', 'none');
            if (typeof(arguments[0]) == 'number') ile = arguments[0];
            else {
                ile = methods.settings.amount;
            }

            setTimeout(function () {
                var params;
                if (methods.settings.params == '') {
                    params = {
                        page: methods.variables.page,
                        limit: ile
                    }
                } else {
                    params = methods.settings.params;
                    params['page'] = methods.variables.page;
                    params['limit'] = methods.settings.amount;
                    methods.variables.pre_page = methods.variables.page;
                }
                /* window.requestApi(settings.address, params, function (data) {
                 $(settings.trigger).css('display', 'block');
                 methods.add_elements(data);
                 lock = false;
                 $("img").lazyload({effect: "fadeIn"});
                 });*/
                $.ajax({
                    url: methods.settings.address,
                    data: params,
                    type: 'post',
                    async: true,
                    dataType: methods.settings.format,
                    success: function (res_data) {
                        //console.log("res");
                        //console.log(res_data);
                        $(methods.settings.trigger).css('display', 'block');

                        methods.add_elements(res_data);
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
                    }
                })
            }, 200);

            // }

        }
    };
    methods.target = null;
    methods.template = null;
    methods.lock = false;
    methods.variables = {
        'last': 1,
        'page': 1,
        'pre_page': 0
    };
    methods.settings = {
        'amount': '10',
        'address': 'comments.php',
        'params': "",
        'format': 'json',
        'template': '.single_item',
        'trigger': '.get_more',
        'scroll': 'false',
        'offset': '100',
        'spinner_code': '',
        'callback': '',
    };
    methods.variables.last = methods.variables.page = 1;
    $.fn.more = function (method) {
        if (methods[method])
            return methods[method].apply(this, Array.prototype.slice.call(arguments, 1));
        else if (typeof method == 'object' || !method) {
            return methods.init.apply(this, arguments);
        }
        else $.error('Method ' + method + ' does not exist!');
    }
})(jQuery);