/**
 * Created by ykuang on 2018/1/15.
 */
define(function (require, exports) {
    var base = require('app/panel/panel.base');//公共函数
    var store = require('app/panel/panel.storage.js?v=1.0');
    require('jquery/jquery.easing.min');
    require('jquery/jquery.dragsort');

    var uploader = require('app/panel/virtual/upload.js?v=1.0');

    //添加广告
    exports.addAdvertise = function () {
        uploader.upload('.pub-all-pic', {'type': 'img', multi_selection: true}, function (res) {
        });
        $('#picturesPreview').dragsort({
            dragSelector: "img",
            dragBetween: true,
            dragEnd: function () {
            }
        });
        // 移除图片
        $("#picturesPreview").on('click', '.removeBtn', function (e) {
            $(this).parent().parent().remove();
            $("#browse_files_button_undefined").show();
            e.stopImmediatePropagation();
        });

        //保存
        $(".saveBtn").on('click', function () {
            var media = [];//媒体数据
            var content = $("#content").val().trim();//文本内容
            var id = parseInt($(this).data('id'));

            if (content == '') {
                tip.showTip('err', '请输入文字内容', 1000);
                return false
            }
            $(".img_list img").each(function () {
                var src = $(this).attr('src');
                if (src.indexOf("base64") > 0) {
                    media.push(src + '?' + $(this).data('width') + 'x' + $(this).data('height'));
                } else {
                    media.push(src);
                }
            });
            var data = {
                media: media,
                content: content,
                id: id
            };
            base.requestApi('/api/package/addAds', data, function (res) {
                if (res.result == 1) {
                    tip.showTip('ok', '添加成功', 1000, function () {
                        window.location.href = "/panel/package/ads"
                    });
                }
            });
        });
    };

//修改广告
    exports.removeAds = function () {
        //删除广告
        $(".list .del_btn").on('click', function () {
            var id = $(this).attr('data-id');
            base.requestApi('/api/package/delAds', {id: id}, function (res) {
                if (res.result == 1) {
                    tip.showTip("ok", "删除成功", 1000, function () {
                        $(".item[data-id='" + id + "']").remove();
                    });

                }
            });
        });
    };

    //添加假日红包
    exports.addFestivalPackage = function () {
        uploader.upload('.pub-all-pic', {'type': 'img', multi_selection: true}, function (res) {
        });
        $('#picturesPreview').dragsort({
            dragSelector: "img",
            dragBetween: true,
            dragEnd: function () {
            }
        });
        // 移除图片
        $("#picturesPreview").on('click', '.removeBtn', function (e) {
            $(this).parent().parent().remove();
            $("#browse_files_button_undefined").show();
            e.stopImmediatePropagation();
        });
        //选择用户
        $(".user_item").on('click', function () {
            if ($(this).hasClass('checked')) {
                $(this).removeClass("checked");
            } else {
                $(this).addClass("checked");
                $(this).siblings().removeClass("checked");
            }
        });
        //保存
        $(".saveBtn").on('click', function () {
            var media = [];//媒体数据
            var content = $("#content").val().trim();//文本内容
            var send_time = $("#send_time").val().trim();//发送时间
            var money = $("#money").val().trim();//红包金额
            var num = $("#num").val().trim();//红包个数

            var id = parseInt($(this).data('id'));

            if (send_time == '') {
                tip.showTip('err', '请输入发布时间', 1000);
                $("#send_time").focus();
                return false
            }
            if (money == '' || money <= 0) {
                tip.showTip('err', '请输入红包金额', 1000);
                $("#money").focus();
                return false
            }
            if (!num || num <= 0) {
                tip.showTip('err', '请输入红包个数', 1000);
                $("#num").focus();
                return false
            }
            if (content == '') {
                tip.showTip('err', '请输入文字内容', 1000);
                return false
            }
            $(".img_list img").each(function () {
                var src = $(this).attr('src');
                if (src.indexOf("base64") > 0) {
                    media.push(src + '?' + $(this).data('width') + 'x' + $(this).data('height'));
                } else {
                    media.push(src);
                }
            });

            var app_uid = $(".user_item.checked").attr('data-id');//发布用户

            var data = {
                media: media,
                content: content,
                send_time: send_time,
                money: money,
                num: num,
                id: id,
                app_uid: app_uid
            };
            base.requestApi('/api/package/addFestivalPackage', data, function (res) {
                if (res.result == 1) {
                    tip.showTip('ok', '添加成功', 1000, function () {
                        window.location.href = "/panel/package/festival"
                    });
                }
            });
        });
    };

    //删除假日红包
    exports.removeFestivalPackage = function () {
        //删除广告
        $(".list .del_btn").on('click', function () {
            var id = $(this).attr('data-id');
            base.requestApi('/api/package/delFestivalPackage', {id: id}, function (res) {
                if (res.result == 1) {
                    tip.showTip("ok", "删除成功", 1000, function () {
                        $(".item[data-id='" + id + "']").remove();
                    });

                }
            });
        });
    };
})