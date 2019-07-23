/**
 * Created by ykuang on 2016/12/19.
 */
define(function (require, exports) {
    var base = require('app/panel/panel.base');//公共函数
    exports.edit = function () {
        var modal = $("#tagModal");
        //标签编辑
        $(".editBtn").on('click', function () {
            modal.find("#name").val($(this).data('name'));
            modal.find("#sort_num").val($(this).data('sort_num'));
            modal.find("#tag_id").val($(this).data('id'));
            if (($(this).data('enable') == '1' && modal.find("#enable").attr('checked') != 'checked') || ($(this).data('enable') == '0' && modal.find("#enable").attr('checked') == 'checked')) {
                modal.find("#enable").click();
            }
            modal.find(".error-widget").hide();
            modal.find(".success-widget").hide();
            modal.find('.modal-title').html("标签编辑");
            modal.modal('show');
        });
        //确定
        modal.find("#sureBtn").on('click', function () {
            var tag_id = modal.find("#tag_id").val();
            var name = modal.find("#name").val().trim();
            var sort_num = parseInt(modal.find("#sort_num").val().trim());
            var enable = 1;
            if (!( modal.find("#enable").attr('checked') == 'checked')) {
                enable = 0;
            }
            if (name == '') {
                modal.find(".error-widget .error_msg").html("请输入标签名称");
                modal.find(".error-widget").show();
                return false;
            }
            //编辑标签
            if (tag_id > 0) {
                $(this).confirm("确定要修改吗?", {
                    ok: function () {
                        base.requestApi('/api/site/editTag', {
                            tag_id: tag_id,
                            name: name,
                            sort_num: sort_num,
                            enable: enable
                        }, function (res) {
                            if (res.result == 1) {
                                base.showTip('ok', res.data, 1000);
                                modal.find(".success-widget").show();
                                modal.find(".success-widget .success_msg").html(res.data);
                                modal.find(".error-widget").hide();
                                $(".close").on('click', function () {
                                    window.location.reload();
                                })
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
                        base.requestApi('/api/site/editTag', {
                            tag_id: tag_id,
                            name: name,
                            sort_num: sort_num,
                            enable: enable
                        }, function (res) {
                            if (res.result == 1) {
                                base.showTip('ok', res.data, 1000);
                                modal.find(".success-widget").show();
                                modal.find(".success-widget .success_msg").html(res.data);
                                modal.find(".error-widget").hide();
                                $(".close").on('click', function () {
                                    window.location.reload();
                                })
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