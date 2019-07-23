define(function (require, exports) {
    var base = require("/srv/static/panel/js/app/panel/panel.base");
/*
    require("jquery/jquery.dragdrop");
*/
    require('/srv/static/panel/js/jquery/jquery.stickyNavbar.min');
    require('/srv/static/panel/js/jquery/jquery.easing.min');

    exports.forgot = function () {
        $('.forgotForm').on('click', '.loginBtn', function (e) {
            var email = $('#email').val();
            if (!new RegExp(/(\w+([-+.]\w+)*@\w+([-.]\w+)*\.\w+([-.]\w+)*)/).test(email)) {
                tip.showTip('err', "账号格式不准确，请填写用户名或邮箱！", 2000);
                return;
            }

            base.requestApi('/srv/api/account/forgot', {email: email}, function (res) {
                if (res.result == 1) {
                    setTimeout(function () {
                        window.location.href = '/account/login';
                    }, 1500);
                    tip.showTip("ok", '重置后的密码已经发送到您的油箱，请注意查收！', 5000);
                }
            });

            e.stopImmediatePropagation();
        });
    };

    exports.login = function () {
        $('.loginForm').on('click', '.loginBtn', function (e) {
            var data = $('.loginForm').serializeObject();

            if (!new RegExp(/(\w+([-+.]\w+)*@\w+([-.]\w+)*\.\w+([-.]\w+)*)/).test(data.account)) {
                if (!new RegExp(/[0-9a-zA-Z-+]{4,16}/).test(data.account)) {
                    tip.showTip('err', "账号格式不准确，请填写用户名或邮箱！", 2000);
                    $('.forgotForm #account').focus();
                    return;
                }
            }

          /*  if (data.password.length < 5 || data.password.length > 16) {
                tip.showTip('err', "密码长度为6-16位", 2000);
                return;
            }*/

            //window.alert(0);
            base.requestApi('/srv/api/account/login', data, function (res) {
                //window.alert(0);
                if (res.result == 1) {
                    setTimeout(function () {
                        window.location.href = res.data;
                    }, 1500);
                    tip.showTip("ok", '恭喜您，登陆成功！', 2000);
                }
            });

            e.stopImmediatePropagation();
        });
    };

    exports.reg = function () {
        $('#regForm').on('click', '.regBtn', function (e) {
            var data = $('.regForm').serializeObject();

            if (!checkField('#regForm #account', /^[a-zA-Z][0-9a-zA-Z_]{4,15}$/)) {
                $('.regForm #account').focus();

                return;
            }
            if (!checkField('#regForm #email', /\w+([-+.]\w+)*@\w+([-.]\w+)*\.\w+([-.]\w+)*/)) {
                $('.regForm #email').focus();
                return;
            }

            if (!checkField('#regForm #password', /(.*){6,16}/)) {
                $('.regForm #password').focus();
                return;
            }

            if (data.password != $("#repassword").val()) {
                tip.showTip("err", "两次输入的密码不一致！", 2000);
                $('.regForm #repassword').focus();
                return;
            }

            if ($("#agree:checked").attr("data-checked") != "true") {
                tip.showTip("err", "请接受用户协议！", 2000);
                return;
            }


            base.requestApi('/api/account/reg', data, function (res) {
                if (res.result == 1) {
                    setTimeout(function () {
                        window.location.href = res.data;
                    }, 1500);
                    tip.showTip("ok", '恭喜您，注册成功！', 2000);
                }
            });

            e.stopImmediatePropagation();
        });
    };

    exports.profile = function (fromPage) {
        require("/srv/static/panel/js/tools/region.select");

        $("#profileForm").on("click", '.saveProfileBtn', function () {
            var data = $("#profileForm").serializeObject();

            if (!data.company_name) {
                $("#company_name").focus();
                tip.showTip('err', "请填写企业名称！", 2000);
                return;
            }

            if (!checkField('#industry')) {
                $('#industry').focus();
                return;
            }

            if (!checkField('#province')) {
                $('#province').focus();
                return;
            }

            if (data.town == "市辖区") {
                $("#town").focus();
                tip.showTip('err', "请选择区域！", 2000);
                return;
            }

            if ($.trim(data.address).length < 6) {
                $("#address").focus();
                tip.showTip('err', "请填写正确的详细地址！", 2000);
                return;
            }
            if ($.trim(data.detail).length < 10 || $.trim(data.detail).length >90) {
                $("#town").focus();
                tip.showTip('err', "请填写企业简介，10字以上 90字以内！", 2000);
                return;
            }

            // 联系信息验证
            if (!checkField('#contact_person', /^[\u4E00-\u9FA5]{2,6}$/)) {
                $('#contact_person').focus();
                return;
            }

            if (!checkField('#contact_phone', /^1[\d]{10}$/)) {
                $('#contact_phone').focus();
                return;
            }

            if (data.contact_tel) {
                if (!checkField('#contact_tel', /^(1[\d]{10})|(((400)-(\d{3})-(\d{4}))|^((\d{7,8})|(\d{4}|\d{3})-(\d{7,8})|(\d{4}|\d{3})-(\d{3,7,8})-(\d{4}|\d{3}|\d{2}|\d{1})|(\d{7,8})-(\d{4}|\d{3}|\d{2}|\d{1}))$)$/)) {
                    $('#contact_tel').focus();
                    return;
                }
            }

            if (!checkField('#contact_email', /^\w+([-+.]\w+)*@\w+([-.]\w+)*\.\w+([-.]\w+)*$/)) {
                $('#contact_email').focus();
                return false;
            }

            if (data.contact_qq) {
                if (!checkField('#contact_qq', /^\d{5,11}$/)) {
                    $('#contact_qq').focus();
                    return;
                }
            }

            base.requestApi("/api/customer/saveProfile", {data: data}, function (res) {
                if (res.result == 1) {
                    if (fromPage) {
                        setTimeout(function () {
                            window.location.reload()
                        }, 1000)
                    } else {
                        window.location.href = res.data;
                    }
                }
            });
        });
    };
    function checkField(elem, regx) {
        var val = $(elem).val();
        var placeholder = $(elem).attr("placeholder");
        var tips = $(elem).attr("data-tip");
        if ($(elem).val() == '') {
            tip.showTip('err', placeholder + '不能为空', 2000);
            return false;
        } else {
            if (regx) {
                tips = tips || "格式不正确";
                if (!new RegExp(regx).test(val)) {
                    tip.showTip('err', placeholder + tips, 3000);
                    return false;
                }
            }
        }

        return true;
    }

});