/**
 * Created by ykuang on 2017/10/18.
 */
define(function (require, exports) {
    var base = require("app/panel/panel.base");
    require("/static/panel/js/tools/Url.js");
    exports.del = function () {
        $("#checkModal #sureBtn").on('click', function () {
            var reason = $("#reason").val();
            var data = [$("#group_id").val()];
            if (!reason) {
                base.showTip('err', '请输入封店原因', 1000);
                return false;
            }

            base.requestApi('/api/good/del', {
                data: data,
                reason: reason,
            }, function (res) {
                if (res.result == 1) {
                    base.showTip('ok', '操作成功！', 1000, function () {
                        $('#checkModal').modal('hide');
                        window.location.reload();
                    });
                }
            });
        });
        $(".list").on('click', '.delBtn', function (e) {
            // params
            var id = $(this).attr('data-id');
            var data = [id];
            // confirm
            var __this = $(this);
            $('#checkModal').modal('show');
            $("#group_id").val(id);
            e.stopImmediatePropagation();
            /*   $(this).confirm("您确认封杀该群吗?", {
             ok: function () {
             base.requestApi('/panel/api/group/del', {data: data}, function (res) {
             if (res.result == 1) {
             base.showTip('ok', '操作成功！', 1000);
             __this.html("恢复正常");
             __this.removeClass("delBtn").addClass("btn-success recoveryBtn");
             }
             });
             },
             cancel: function () {
             return false;
             }
             });*/
        }).on('click', '.recoveryBtn', function (e) {
            // params
            var id = $(this).attr('data-id');
            var data = [id];
            var __this = $(this);
            // confirm
            $(this).confirm("您确认恢复该店铺吗?", {
                ok: function () {
                    base.requestApi('/api/good/recovery', {data: data}, function (res) {
                        if (res.result == 1) {
                            base.showTip('ok', '恢复成功！', 1000,function(){
                                window.location.reload();
                            });
                            //  __this.html("封杀该店铺");
                            //  __this.removeClass("btn-success recoveryBtn").addClass("delBtn");

                        }
                    });
                },
                cancel: function () {
                    return false;
                }
            });

            e.stopImmediatePropagation();
        })
    };

})
;
