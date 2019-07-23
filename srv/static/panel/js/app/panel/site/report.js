/**
 * Created by ykuang on 2016/12/19.
 */
define(function (require, exports) {
    var base = require('app/panel/panel.base');//公共函数

    base.selectNone();
    base.selectCheckbox();
    exports.edit = function () {
        var modal = $("#reportModal");
        //添加行业
        $(".btnAdd").on('click', function () {
            modal.find("#content").val('');
            modal.find("#reason_id").val(0);
            modal.find(".error-widget").hide();
            modal.find(".success-widget").hide();
            modal.find('.modal-title').html("添加原因");
            modal.modal('show');
        });
        $(".editBtn").on('click', function () {
            modal.find("#reason_id").val($(this).attr('data-id'));
            modal.find("#content").val($(this).attr('data-content'));
            modal.find("#sort").val($(this).attr('data-sort'));

            if (($(this).data('enable') == '1' && !modal.find("#enable").prop('checked')) || ($(this).data('enable') == '0' && modal.find("#enable").attr('checked'))) {
                modal.find("#enable").click();
            }


            modal.find(".error-widget").hide();
            modal.find(".success-widget").hide();
            modal.find('.modal-title').html("编辑原因");
            modal.modal('show');
        });
        //可用不可用
        $(".listData .enableBtn").on('click', function () {
            var id = $(this).attr('data-id');
            var enable = $(this).prop('checked') == true ? 1 : 0;
            base.requestApi('/api/site/reasonEnable', {
                id: id,
                enable: enable
            }, function (res) {
                if (res.result == 1) {
                    base.showTip('ok', res.data, 1000, function () {
                        //  base.showTip('ok', res.data, 1000, function () {
                        window.location.reload();
                        //   });
                    });
                } else {
                }
            });
        });


        //确定
        modal.find("#sureBtn").on('click', function () {
            var id = modal.find("#reason_id").val();
            var content = modal.find("#content").val().trim();
            var sort = modal.find("#sort").val().trim();
            var enable = 1;
            if (!(modal.find("#enable").is(":checked"))) {
                enable = 0;
            }
            if (isNaN(sort) || sort <= 0) {
                modal.find(".error-widget .error_msg").html("请输入正确的排序");
                modal.find(".error-widget").show();
                return false;
            }
            if (content == '') {
                modal.find(".error-widget .error_msg").html("请输入行业名称");
                modal.find(".error-widget").show();
                return false;
            }
            //编辑标签
            if (id > 0) {
                $(this).confirm("确定要修改吗?", {
                    ok: function () {
                        base.requestApi('/api/site/editReason', {
                            id: id,
                            content: content,
                            sort: sort,
                            enable: enable
                        }, function (res) {
                            if (res.result == 1) {
                                base.showTip('ok', res.data, 1000, function () {
                                    window.location.reload();
                                });
                                modal.find(".success-widget").show();
                                modal.find(".success-widget .success_msg").html(res.data);
                                modal.find(".error-widget").hide();
                                /*   $(".close").on('click', function () {
                                 window.location.reload();
                                 })*/
                            } else {
                                modal.find(".success-widget").hide();
                                modal.find(".error-widget .error_msg").html(res.error.msg);
                                modal.find(".error-widget").show();
                            }
                        });
                    },
                    cancel: function () {
                        return false;
                    }
                });
            }
            //添加标签
            else {
                $(this).confirm("确定要添加吗?", {
                    ok: function () {
                        base.requestApi('/api/site/addReason', {
                            content: content,
                            type: $(".tabs a.active[data-key='type']").attr('data-type'),
                            sort: sort,
                            enable: enable
                        }, function (res) {
                            if (res.result == 1) {
                                modal.find(".success-widget").show();
                                modal.find(".success-widget .success_msg").html(res.data);
                                modal.find(".error-widget").hide();
                                base.showTip('ok', res.data, 1000, function () {
                                    window.location.reload();
                                });

                                /*  $(".close").on('click', function () {
                                 window.location.reload();
                                 })*/
                            } else {
                                modal.find(".success-widget").hide();
                                modal.find(".error-widget .error_msg").html(res.error.msg);
                                modal.find(".error-widget").show();
                            }
                        });
                    },
                    cancel: function () {
                        return false;
                    }
                });
            }
        })

    }

});