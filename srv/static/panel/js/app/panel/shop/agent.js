/**
 * Created by ykuang on 2017/10/18.
 */
define(function (require, exports) {
    var base = require("app/panel/panel.base");
    require("/srv/static/panel/js/tools/Url.js");
    require('/srv/static/panel/js/app/panel/stat/pager.js');
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
    exports.successCheck = function (btn) {
        $(" .listData").on('click', btn, function (e) {
            // params
            var id = $(this).attr('data-id');
            var data = [id];
            // confirm
            $(this).confirm("你确定审核通过吗?", {
                ok: function () {
                    base.requestApi('/api/agent/checkSuccess', {data: data}, function (res) {
                        if (res.result == 1) {
                            base.showTip('ok', '操作成功！', 3000,function(){
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
                    base.requestApi('/api/agent/checkSuccess', {data: data}, function (res) {
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
            base.requestApi('/api/agent/checkFail', {
                id: modal.find("#apply_id").val(),
                reason: reason
            }, function (res) {
                if (res.result == 1) {
                    modal.modal('hide');
                    base.showTip('ok', '操作成功！', 3000,function(){
                        window.location.reload();
                    });
                }
            });
        })

    };
    exports.del = function () {
        var modal = $("#removeModal");
        modal.find("#sureBtn").on('click', function () {
            var reason = modal.find("#reason").val();
            var data = [modal.find("#apply_id").val()];
            if (!reason) {
                base.showTip('err', '请输入封店原因', 1000);
                return false;
            }

            base.requestApi('/api/agent/del', {
                data: data,
                reason: reason
            }, function (res) {
                if (res.result == 1) {
                    base.showTip('ok', '操作成功！', 1000, function () {
                        modal.modal('hide');
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
            modal.modal('show');
            modal.find("#apply_id").val(id);
            e.stopImmediatePropagation();

        }).on('click', '.recoveryBtn', function (e) {
            // params
            var id = $(this).attr('data-id');
            var data = [id];
            var __this = $(this);
            // confirm
            $(this).confirm("您确认恢复该店铺吗?", {
                ok: function () {
                    base.requestApi('/api/agent/recovery', {data: data}, function (res) {
                        if (res.result == 1) {
                            base.showTip('ok', '恢复成功！', 1000, function () {
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