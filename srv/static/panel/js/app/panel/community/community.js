/**
 * Created by ykuang on 2018/05/22.
 */
define(function (require, exports) {
    var base = require("app/panel/panel.base");
    base.selectNone();
    base.selectCheckbox();
    exports.successCheck = function (btn) {
        $(" .listData").on('click', btn, function (e) {
            // params
            var id = $(this).attr('data-id');
            var data = [id];
            // confirm
            $(this).confirm("你确定审核通过吗?", {
                ok: function () {
                    base.requestApi('/api/community/checkSuccess', {data: data}, function (res) {
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
        $(".checkAllSelected").on('click', function (e) {
            // params
            var data = [];
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
                    base.requestApi('/api/community/checkSuccess', {data: data}, function (res) {
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
        var modal = $("#checkModal");
        $(" .listData").on('click', '.failBtn', function (e) {
            // params
            var id = $(this).attr('data-id');
            modal.find("#apply_id").val(id);
            modal.modal('show');
            // api request
            e.stopImmediatePropagation();
        });
        modal.find("#sureBtn").on('click', function () {
            var reason = modal.find("#reason").val();
            if (!reason) {
                base.showTip('err', '请输入审核失败原因', 1000);
                return false;
            }
            base.requestApi('/api/community/checkFail', {
                id: modal.find("#apply_id").val(),
                reason: reason
            }, function (res) {
                if (res.result == 1) {
                    modal.modal('hide');
                    base.showTip('ok', '操作成功！', 3000, function () {
                        window.location.reload();
                    });
                }
            });
        })

    };
});