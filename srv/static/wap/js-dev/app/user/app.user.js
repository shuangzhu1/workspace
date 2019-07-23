define(function (require, exports) {
    var base = require('base');
    var load_more = require("app/more");
    var psinit = require('app/ps.init');
    var uploader = require('app/user/app.upload');
    var klg_encrypt = require('/static/wap/js/cryptKLG.js');

    exports.user_center = function (uid) {
        var load_discuss = false;
        var load_album = false;
        var load_show = false;

        //tab 切换
        $(".tab_list li").on('click', function () {
            var data_id = $(this).data('id');
            $(".tab_content").hide();
            $(".tab_content[data-id='" + data_id + "']").show();
            $(this).addClass('on').siblings().removeClass('on');
            //加载动态
            if (data_id == 2 && !load_discuss) {
                load_more.more('/api/user/discuss', {to: uid}, function () {
                    load_discuss = true;
                    psinit.init();
                }, "#discuss_wrap", ".item")
            }
            //加载相册
            if (data_id == 3 && !load_album) {
                load_more.more('/api/user/album', {to: uid}, function () {
                    load_album = true;
                    /*$('.lightGallery').lightGallery({
                     mode: "lg-slide",
                     speed: 300,
                     scale: 2,
                     keypress: true,
                     enableZoomAfter: 300
                     });*/
                    psinit.init();
                }, "#album_wrap", ".album_list_item", {amount: 5})
            }
            //加载秀场
            if (data_id == 4 && !load_show) {
                load_more.more('/api/user/show', {uid: uid}, function () {
                    load_show = true;
                    if($('#show_wrap > div.item').length === 0)
                        $('#no-video').show();
                }, '#show_wrap', '.item');
            }
        });
        $(document).on('click', '.rent li', function () {
            window.location.href = '/user/skillInfo?skill=' + ($(this).data('data')) + '&info=' + ($(this).data('info'));
        });

        //初始化为动态
        $(".tab_list li[data-id='1']").click();


    };
    exports.send_code = function (type, obj) {
        $('#sendCodeBtn').on('click', function (e) {
            var phone = obj.find(".phone").val();
            var state_code = obj.find(".state-code").val();
            var _reg = /^1\d{10}$/;
            if (!_reg.test(phone)) {
                obj.find('.phone').focus();
                //$(this).parent().siblings(".ft_error").html("请输入正确的手机号码").show();
                //tip.showTip('err', '请输入正确的手机号码', 3000);
                showError($('.next-2'), '请输入正确的手机号码')
                return false;
            }
            if ($(this).hasClass('btn-disabled')) {
                //$(this).parent().siblings(".ft_error").html("验证码一分钟只能发送一次,请等待").show();
                //tip.showTip('err', ' 一分钟只能发送一次,请等待', 3000);
                showError($('.next-2'), '一分钟只能发送一次');
                return;
            }
            var check_unique = $(this).attr('data-checkUnique');
            var disabled = $(this).attr('disabled');
            if (disabled && disabled == 'true') {
                return false;
            }
            if (!(type && type !== 'null')) {
                type = '';
            }

            var params = klg_encrypt.encrypt({
                phone: phone,
                check_unique: check_unique,
                state_code: state_code,
                type: type
            });
            base.requestApi('/api/sms/sendPhoneVerifyCode', {
                'params': params
            }, function (res) {
                if (res.result == 1) {
                    //tip.showTip('ok', '短信验证码已经发送！', 3000);
                    codeTimer('sendCodeBtn', 60);
                } else {
                    if (res.error.code == 1016) {
                        showError($('.next-2'), '该手机号已注册');
                    } else {
                        console.log(res.error);
                    }
                }
            }, false, true, $('.step-2 .err'));

            e.stopImmediatePropagation();
        });
    };
    exports.register = function () {
        var today = function () {
            var d = new Date();
            return d.getFullYear() + '/' + (d.getMonth() + 1) + '/' + d.getDate();
        }
        var upload_atatar = '';
        var obj = $(".regForm");
        exports.send_code("register", obj);
        $('.next-1').on('click', function () {
            $('.step-1').slideUp();
            $('.step-2').slideDown();
        });
        /*   uploader.uploadAvatar('.upload-widget[data-unique="1"]', {'type': 'img'}, function (res) {
         upload_atatar = res;
         }, submit);*/
        $('.next-2').on('click', function () {

            //阅读协议
            if (!$('.agree input').is(':checked')) {
                showError(this, '请阅读并同意注册协议');
                return;
            }
            //验证码、密码校验
            var code = $('#code').val(),
                phone = $('#phone').val(),
                pwd = $('#pwd').val();
            if (!/^1[\d]{10}$/.test(phone)) {
                //tip.showTip('err','请输入正确手机号',1500);
                showError($('.next-2'), '请输入正确手机号');
                return;
            }
            if (code == '' || code == undefined) {
                //tip.showTip('err','请输入验证码',1500);
                showError($('.next-2'), '请输入验证码');
                return;
            }
            if (!/^(\d){6}$/.test(code)) {
                //tip.showTip('err','验证码为6位数字',1500);
                showError($('.next-2'), '验证码为6位数字');
                return;
            }
            if (pwd == '' || pwd == undefined) {
                //tip.showTip('err','请输入密码',1500);
                showError($('.next-2'), '请输入密码');
                return;
            }
            if (!/^[0-9a-zA-Z]{6,16}$/g.test(pwd)) {
                //tip.showTip('err','验证码为6位数字',1500);
                showError($('.next-2'), '密码为6-16位数字或字母');
                return;
            }
            base.requestApi('/api/user/ValidateCode', {code: code, phone: phone}, function (res) {
                if (res.result == 1) {
                    $('.step-2').slideUp();
                    $('.step-3').slideDown();
                    uploader.uploadAvatar('.upload-widget[data-unique="1"]', {'type': 'img'}, function (res) {
                        upload_atatar = res;

                    }, submit);
                } else {
                    if (res.error.code == 1039)
                        showError($('.next-2'), '验证码未发送或者已过期,请重新发送')
                }

            }, false, true, $('.step-2 .err'))


        })

        $('.regBtn').on('click', function () {
            var data = obj.serializeObject();
            var avatar = $("#avatar").attr('src'),
                username = $("#username").val().trim(),
                birthday = $("#birthday").val().replace(/-/g, '/');


            //console.log("今天：" + new Date(birthday).getTime());return;
            /*if (avatar == '' || avatar === undefined) {
             //tip.showTip("err", "请选择头像", 1000);
             showError($('.next-3'),'请选择头像');
             return;
             }*/
            if (username == '' || username === undefined || username.length < 2 || username.length > 8) {
                //tip.showTip("err", "请选择头像", 1000);
                showError($('.regBtn'), '用户名为2-8位');
                return;
            }
            if (birthday == '' || birthday === undefined) {
                showError($('.regBtn'), '请选择出生日期');
                return;
            }
            if (new Date(today()).getTime() - new Date(birthday).getTime() <= 0) {
                showError($('.regBtn'), '请选择正确的生日');
                return;
            }
            //$('.step-3 .err').css({'color': '#ff8300', "visibility": "visible"}).html('正在保存信息，请稍后......');
            if ($("#avatar").attr('data-width') !== undefined) {
                upload_atatar.start();
            } else {
                submit("", true);
            }
        })

        //密码可见
        $('#eye_toggle').on('click', function () {
            var _this = this;
            var stat = $(_this).attr('data-stat');
            $(_this).find('img').each(function () {
                $(this).toggleClass('hide');
            });
            if ($(_this).find('img:visible').attr('data-stat') == 'open') {
                $(_this).prev().attr('type', 'text');
            } else {
                $(_this).prev().attr('type', 'password');
            }

        });


        /*$(".phone").on('keyup', function () {
         $(this).parent().siblings(".ft_error").html("");
         });
         $(".username").on('keyup', function () {
         $(this).parent().siblings(".ft_error").html("");
         });
         $(".code").on('keyup', function () {
         $(this).parent().siblings(".ft_error").html("");
         });
         $(".birthday").on('change', function () {
         $(this).parent().siblings(".ft_error").html("");
         });
         $(".pwd").on('keyup', function () {
         $(this).parent().siblings(".ft_error").html("");
         });*/
        /*$(".regBtn").on('click', function () {
         });*/
        function submit(url, need_upload) {
            var data = obj.serializeObject();
            if (need_upload !== true) {
                if (url === undefined || url == '') {
                    showError($('.next-2'), '头像上传失败')
                }
            }
            data.avatar = url;
            base.requestApi('/api/user/reg', data, function (res) {
                if (res.result == 1) {
                    $('#page-3').slideUp();
                    $('#page-4').slideDown();
                }
            }, false, true, $('.step-3 .err'));
        }

    };
    function codeTimer(targetId, times) {
        var target = $('#' + targetId);
        if (target) {
            if (times && times > 0) {
                target.addClass('btn-disabled');
                if (times == 60) {
                    target.removeClass('btn-green');
                    target.addClass('btn-gray');
                }
                var newTimes = times - 1;
                target.html('等待' + newTimes + "秒");
                setTimeout(function () {
                    codeTimer(targetId, times - 1);
                }, 1000);
            }
            else {
                target.removeClass('btn-disabled');
                target.html("重发验证码");
            }
        }
    }

    function showError(ele, msg, time, callback) {
        time = time || 2000;
        $(ele).prev().css('visibility', 'visible').html(msg);
        setTimeout(function () {
            $(ele).prev().css('visibility', 'hidden');
        }, time);
        if (typeof callback == 'function') {
            callback();
        }
    }

});