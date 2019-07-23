/**
 * Created by ykuang on 2017/9/22.
 */
define(function (require, exports) {
    var base = require('app/panel/panel.base');//公共函数
    var storage = require('app/panel/panel.storage.js?v=1.0');//storage

    exports.edit = function () {
        //提示
        $(".tip").hover(function () {
            $(this).append($(".tip_box"));
            $(".tip_box").show();
        }, function () {
            $(".tip_box").hide();
        })
        //编辑 子技能
        $(document).on('click', '.editBtn', function () {
            var type = $(this).attr('data-type');
            var subtype = $(this).attr("data-subtype");
            var p = $(this).parent().parent();
            var original_title = p.find(".title").attr('data-old');
            var original_min_price = p.find(".min_price").attr('data-old');
            var original_max_price = p.find(".max_price").attr('data-old');
            var original_service_rate = p.find(".service_rate").attr('data-old');
            var original_offline = p.find(".offline").attr('data-old');
            var default_desc = p.find(".default_desc").attr('data-old');
            var original_restrict = p.find(".restrict").attr('data-old');
            var original_is_hot = p.find(".is_hot").attr('data-old');
            var original_weight = p.find(".weight").attr('data-old');

            p.find(".title").html("<input type='text' maxlength='10' value='" + original_title + "'/>");
            p.find(".min_price").html("<input type='number'  min='0' value='" + original_min_price + "'/>分");
            p.find(".max_price").html("<input type='number'  min='0' value='" + original_max_price + "'/>分");
            p.find(".service_rate").html("<input min='0' max='100' type='number' maxlength='3' value='" + original_service_rate + "'/>%");
            p.find(".offline").html("<div> <input id='enable' " + (original_offline == 1 ? 'checked' : '') + "  type='checkbox' class='ace ace-switch ace-switch-5'/><span class='lbl'></span> </div>");
            p.find(".default_desc").html("<div> <textarea cols='50'class='desc'>" + default_desc + "</textarea></div>");
            p.find(".restrict").html("<input min='0' max='3' type='number' maxlength='1' value='" + original_restrict + "'/>");
            p.find(".is_hot").html("<div> <input id='enable' " + (original_is_hot == 1 ? 'checked' : '') + "  type='checkbox' class='ace ace-switch ace-switch-5'/><span class='lbl'></span> </div>");
            p.find(".weight").html("<input type='number'  min='0' value='" + original_weight + "'/>");

            p.find(".saveBtn").show();
            p.find(".cancelBtn").show();
            $(this).hide();
        });
        //取消
        $(document).on('click', '.cancelBtn', function () {
            var type = $(this).attr('data-type');
            var subtype = $(this).attr("data-subtype");
            var p = $(this).parent().parent();
            var original_title = p.find(".title").attr('data-old');
            var original_min_price = p.find(".min_price").attr('data-old');
            var original_max_price = p.find(".max_price").attr('data-old');
            var original_service_rate = p.find(".service_rate").attr('data-old');
            var original_offline = p.find(".offline").attr('data-old');
            var default_desc = p.find(".default_desc").attr('data-old');
            var original_restrict = p.find(".restrict").attr('data-old');
            var original_is_hot = p.find(".is_hot").attr('data-old');
            var original_weight = p.find(".weight").attr('data-old');

            p.find(".title").html(original_title);
            p.find(".min_price").html(original_min_price + '分');
            p.find(".max_price").html(original_max_price + '分');
            p.find(".service_rate").html(original_service_rate + '%');
            p.find(".offline").html(original_offline == 1 ? '是' : '否');
            p.find(".default_desc").html(default_desc);
            p.find(".restrict").html(original_restrict);
            p.find(".weight").html(original_weight);
            p.find(".is_hot").html(original_is_hot == 1 ? '是' : '否');
            p.find(".saveBtn").hide();
            p.find(".editBtn").show();
            $(this).hide();
        });
        //添加技能
        $(".btnAdd").on('click', function () {
            $("#skillModal").modal("show");
        });
        //编辑父技能
        $(".editTop").on('click', function () {
            var modal = $("#editModal");
            modal.find(".type_title").val($(this).data('title'));
            modal.find("#sureBtn").attr('data-type', $(this).data('type'));
            modal.find("#thumb1").val($(this).data('icon'));
            modal.find(".preview-thumb1").attr('src', $(this).data('icon'));
            modal.find(".weight").val($(this).data('weight'));

            modal.modal("show");
        });
        storage.getImg('#iconUpload1', function (res) {
            $('#thumb1').val(res.url);
            $('.preview-thumb1').attr('src', res.url);
        }, false);
        storage.getImg('#iconUpload2', function (res) {
            $('#thumb2').val(res.url);
            $('.preview-thumb2').attr('src', res.url);
        }, false);
        $(".parent_type").on('change', function () {
            if ($(this).val() != '0') {
                $(".subAttr").show();
                $(".icon_group").hide()
            } else {
                $(".icon_group").show();
                $(".subAttr").hide()
            }
        });
        //展开 收起
        $(document).on('click', '.spread', function () {
            var target = $(".list_" + ($(this).attr('data-id')));
            if (target.is(":visible")) {
                $(this).find('i').removeClass('fa-angle-double-up').addClass("fa-angle-double-down");
                $(this).parent().css({'border-bottom': '1px solid #e4e4e4'})
            } else {
                $(this).find('i').removeClass('fa-angle-double-down').addClass("fa-angle-double-up");
                $(this).parent().css({'border-bottom': 'none'})
            }
            target.toggle();

        })
        //开启关闭自动审核
        $("#AutomaticAudit").on('change', function () {
            if ($(this).prop('checked')) {
                $(".AuditDuration_wrap").show();
            } else {
                $(".AuditDuration_wrap").hide();
            }
        });

        //保存时间限制
        $(".saveTime").on('click', function () {
            var deadline = parseInt($.trim($(".deadline").val()));
            var deadline_immediately = parseInt($.trim($(".deadline_immediately").val()));
            var pay_due_time = parseInt($.trim($(".pay_due_time").val()));
            var automatic_audit = 0;
            var audit_duration = parseInt($.trim($(".AuditDuration").val()));
            if (($('#AutomaticAudit').prop('checked'))) {
                automatic_audit = 1;
            } else {
                automatic_audit = 2;
            }
            if (deadline == 0) {
                $(".deadline").focus();
                return false;
            }
            if (deadline_immediately == 0) {
                $(".deadline_immediately").focus();
                return false;
            }
            if (pay_due_time == 0) {
                $(".pay_due_time").focus();
                return false;
            }
            var data = {
                'deadline': deadline,
                'deadline_immediately': deadline_immediately,
                'pay_due_time': pay_due_time,
                'automatic': automatic_audit,
                'audit_duration': audit_duration
            };
            base.requestApi('/api/rent/saveConfig', {data: data, type: 'time'}, function (res) {
                if (res.result == 1) {
                    base.showTip("ok", '保存成功', 1000, function () {
                        window.location.reload();
                    });
                }
            })
        });
        //保存技能配置
        $(".saveBtn").on('click', function () {
            var _this = $(this);
            var type = $(this).attr('data-type');
            var subtype = $(this).attr('data-subtype');
            var p = $(this).parent().parent();
            var title = $.trim(p.find('.title input').val());
            var min_price = $.trim(p.find('.min_price input').val());
            var max_price = $.trim(p.find('.max_price input').val());
            var service_rate = $.trim(p.find('.service_rate input').val());
            var offline = p.find('.offline input').prop('checked') == true ? 1 : 0;
            var default_desc = $.trim(p.find('.default_desc textarea').val());
            var restrict = parseInt($.trim(p.find('.restrict input').val()));
            var weight = $.trim(p.find('.weight input').val());
            var is_hot = p.find('.is_hot input').prop('checked') == true ? 1 : 0;


            var original_title = p.find(".title").attr('data-old');
            var original_min_price = p.find(".min_price").attr('data-old');
            var original_max_price = p.find(".max_price").attr('data-old');
            var original_service_rate = p.find(".service_rate").attr('data-old');
            var original_offline = p.find(".offline").attr('data-old');
            var original_default_desc = p.find(".default_desc").attr('data-old');
            var original_restrict = p.find(".restrict").attr('data-old');
            var original_weight = p.find(".weight").attr('data-old');
            var original_is_hot = p.find(".is_hot").attr('data-old');

            var data = {
                'type': type,
                'subtype': subtype,
                'sub_title': title,
                'min_price': min_price ? parseInt(min_price) : 0,
                'max_price': max_price ? parseInt(max_price) : 0,
                'service_rate': service_rate,
                'offline': offline,
                'default_desc': default_desc,
                'type_title': p.attr('data-title'),
                'restrict': restrict,
                'is_hot': is_hot,
                'weight': weight,
            };
            // console.log(data);//return ;
            if (data.sub_title == original_title &&
                data.min_price == original_min_price &&
                data.max_price == original_max_price &&
                data.service_rate == original_service_rate &&
                data.offline == original_offline &&
                data.default_desc == original_default_desc &&
                data.is_hot == original_is_hot &&
                data.weight == original_weight &&
                data.restrict == original_restrict
            ) {
                base.showTip("err", '数据没有变动', 1000);
                return false;
            }
            if (data.sub_title == '') {
                base.showTip("err", '技能名称不能为空', 1000);
                return false;
            }
            if (data.max_price < data.min_price) {
                base.showTip("err", '最低出售价格不得大于最高出售价格', 1000);
                return false;
            }
            if (!(data.restrict >= 0 && data.restrict <= 3)) {
                base.showTip("err", '技能限制只能为0/1/2/3', 1000);
                return false;
            }
            base.requestApi('/api/rent/saveConfig', {data: data, type: 'skill'}, function (res) {
                if (res.result == 1) {
                    base.showTip("ok", '保存成功', 1000, function () {
                        p.find(".title").attr('data-old', data.sub_title);
                        p.find(".min_price").attr('data-old', data.min_price);
                        p.find(".max_price").attr('data-old', data.max_price);
                        p.find(".service_rate").attr('data-old', data.service_rate);
                        p.find(".offline").attr('data-old', data.offline);
                        p.find(".weight").attr('data-old', data.weight);
                        p.find(".is_hot").attr('data-old', data.is_hot);
                        p.find(".default_desc").attr('data-old', data.default_desc);
                        p.find(".restrict").attr('data-old', data.restrict);
                        $(_this).siblings(".cancelBtn").click();
                        // window.location.reload();
                    });
                }
            })

        });
        //添加技能
        $("#skillModal").on('click', '#sureBtn', function () {
            var parent_type = $(".parent_type").val();
            var title = $.trim($("#title").val());
            var min_price = parseInt($.trim($("#min_price").val()));
            var max_price = parseInt($.trim($("#max_price").val()));
            var service_rate = parseInt($.trim($("#service_rate").val()));
            var offline = $("#offline").prop('checked') == true ? 1 : 0;
            var weight = parseInt($.trim($("#weight").val()));
            var is_hot = $("#is_hot").prop('checked') == true ? 1 : 0;
            var icon = $.trim($("#thumb2").val());
            var restrict = parseInt($.trim($("#restrict").val()));
            if (title == '') {
                $("#title").focus();
                return false
            }
            //非顶级
            if (parent_type != '0') {
                if (min_price > max_price) {
                    $("#max_price").focus();
                    return false;
                }
                if (!(restrict >= 0 && restrict <= 3)) {
                    base.showTip("err", '请填写正确的技能限制【0/1/2/3】', 1000);
                    return false;
                }
            } else {
                if (icon == '') {
                    base.showTip("err", '请上传图标', 1000);
                    return false;
                }
            }
            data = {
                'type': parent_type,
                'title': title,
                'min_price': min_price,
                'max_price': max_price,
                'service_rate': service_rate,
                'offline': offline,
                'icon': icon,
                'restrict': restrict,
                'weight': weight,
                'is_hot': is_hot
            };
            base.requestApi('/api/rent/saveConfig', {data: data, type: 'add_skill'}, function (res) {
                if (res.result == 1) {
                    base.showTip("ok", '添加成功', 1000, function () {
                        window.location.reload();
                    });
                }
            })
        });
        //编辑一级技能
        $("#editModal").on('click', '#sureBtn', function () {
            var type = $(this).attr('data-type');
            var modal = $("#editModal");
            var title = $.trim(modal.find(".type_title").val());
            var icon = $.trim(modal.find("#thumb1").val());
            var weight = $.trim(modal.find(".weight").val());

            if (title == '') {
                modal.find(".type_title").focus();
                return false
            }
            if (!type) {
                return false
            }
            data = {
                'type': type,
                'title': title,
                'icon': icon,
                'weight': weight
            };
            base.requestApi('/api/rent/saveConfig', {data: data, type: 'edit_top_skill'}, function (res) {
                if (res.result == 1) {
                    base.showTip("ok", '编辑成功', 1000, function () {
                        window.location.reload();
                    });
                }
            })

        });

        $(document).on('click', '.btnRemove', function () {
            var type = $(this).attr('data-type');
            var p_type = $("#p_type_" + type).val();
            var sons = [];
            $("input[name='chk_" + type + "']").each(function () {
                if ($(this).prop('checked') == true) {
                    sons.push($(this).val());
                }
            });
            if (p_type == type) {
                base.showTip("err", '无需移动', 1000);
                return false;
            }
            if (sons.length == 0) {
                base.showTip("err", '没有选中需要移动的技能', 1000);
                return false;
            }
            data = {'type': type, 'subtype': sons, 'to_type': p_type};
            base.requestApi('/api/rent/saveConfig', {data: data, type: 'move_skill'}, function (res) {
                if (res.result == 1) {
                    base.showTip("ok", '编辑成功', 1000, function () {
                        window.location.reload();
                    });
                }
            })
        })
    }
});