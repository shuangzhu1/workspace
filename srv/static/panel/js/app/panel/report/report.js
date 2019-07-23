/**
 * Created by yanue on 6/25/14.
 */
define(function (require, exports) {
    var base = require('app/panel/panel.base');//公共函数

    base.selectNone();
    base.selectCheckbox();

    /**
     * publish panorama or not
     *
     * @param btn
     * @param referer
     */

    /**
     * del panorama
     * @param btn
     */
    exports.checkUser = function (btn) {
        $(" .listData").on('click', btn, function (e) {
            // params
            var id = $(this).attr('data-id');
            var type = $(this).attr('data-type');
            var data = [id];
            // confirm
            $(this).confirm("你确定审核通过吗?", {
                ok: function () {
                    base.requestApi('/srv/api/report/checkUser', {data: data, type: type}, function (res) {
                        if (res.result == 1) {
                            base.showTip('ok', '操作成功！', 3000, function () {
                                window.location.reload();
                            });
                            //
                        }
                    });
                },
                cancel: function () {
                    return false;
                }
            });

            // api request

            e.stopImmediatePropagation();
        });
        $(".checkAllSelected").on('click', function (e) {
            // params
            var data = [];
            var type = $(this).attr('data-type');
            $(".listData input.chk").each(function () {
                if ($(this).attr('checked') == true || $(this).attr('checked') == 'checked') {
                    data.push($(this).attr('data-id'));
                }
            });

            //  has no selected
            if (data.length == 0) {
                base.showTip('err', '请选择需要审核的项', 3000);
                return;
            }

            // confirm
            $(this).confirm("你确定选中的项审核通过吗?", {
                ok: function () {
                    base.requestApi('/srv/api/report/checkUser', {data: data, type: type}, function (res) {
                        if (res.result == 1) {
                            base.showTip('ok', '操作成功！', 3000, function () {
                                window.location.reload();
                            });
                        }
                    });
                },
                cancel: function () {
                    return false;
                }
            });

            // api request

            e.stopImmediatePropagation();
        });
    };
    exports.failCheck = function () {
        $(" .listData").on('click', '.failBtn', function (e) {
            // params
            var id = $(this).attr('data-id');

            $("#apply_id").val(id);
            $('#checkModal').modal('show');
            // api request
            e.stopImmediatePropagation();
        });
        $("#checkModal #sureBtn").on('click', function () {
            var type = $(this).attr('data-type');
            var reason = $("#reason").val();
            if (!reason) {
                base.showTip('err', '请输入审核失败原因', 1000);
                return false;
            }
            base.requestApi('/api/report/checkFail', {
                id: $("#apply_id").val(),
                reason: reason,
                type: type
            }, function (res) {
                if (res.result == 1) {
                    base.showTip('ok', '操作成功！', 3000, function () {
                        $('#checkModal').modal('hide');
                        window.location.reload();
                    });

                }
            });
        })

    };
});