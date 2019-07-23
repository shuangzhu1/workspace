/**
 * Created by ykuang on 2016/12/19.
 */
define(function (require, exports) {
    var base = require('app/panel/panel.base');//公共函数

    base.selectNone();
    base.selectCheckbox();
    exports.edit = function () {
        var modal = $("#industryModal");
        //添加行业
        $(".btnAdd").on('click', function () {
            modal.find("#name").val('');
            modal.find(".error-widget").hide();
            modal.find(".success-widget").hide();
            modal.find('.modal-title').html("添加行业");
            modal.modal('show');
        });
        //确定
        modal.find("#sureBtn").on('click', function () {
            var id = 0;
            var parent_id = modal.find("#parent_id").val();
            var name = modal.find("#name").val().trim();
            if (name == '') {
                modal.find(".error-widget .error_msg").html("请输入行业名称");
                modal.find(".error-widget").show();
                return false;
            }
            //编辑标签
            if (id > 0) {
                $(this).confirm("确定要修改吗?", {
                    ok: function () {
                        base.requestApi('/api/site/editIndustry', {
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
                        base.requestApi('/api/site/addIndustry', {
                            parent_id: parent_id,
                            name: name,
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