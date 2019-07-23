/**
 * Created by ykuang on 2017/12/7.
 */
define(function (require, exports) {
    var base = require('app/panel/panel.base');//公共函数
    exports.task = function () {
        exports.getList();
        exports.add();
        exports.edit();
        exports.pause();
        exports.start();
        exports.remove();

        $(".btnRule").click(function () {
            $("#ruleModal").modal("show");
        });
        $(".task_callback_params").on('keyup', '.values', function (event) {
            if (event.keyCode == "13") {
                var html = $(this).closest("tr").clone();
                if ($(".task_callback_params").find("tr").length == 2) {
                    html.find("td").eq(0).append('<span class="minus_btn" style=""><i class="fa fa-minus"></i></span>');
                }
                $(".task_callback_params").append(html);
                html.find(".values").focus();
            } else if (event.keyCode === 8) {
                if ($(".task_callback_params").find("tr").length > 2) {
                    if ($(this).val() == '' && $(this).closest("tr").find('.keys').val() == '') {
                        $(this).closest("tr").prev().find('.values').focus();
                        $(this).closest("tr").remove();
                    }
                }

            }
        }).on("click", '.minus_btn', function () {
            if ($(".task_callback_params").find("tr").length > 2) {
                $(this).closest("tr").remove();
            }
        });
        $(".modal .trigger").on('click', function () {
            $(".trigger_arg").hide();
            $(".trigger_arg_" + $(this).val()).show();
        });

        $(".list").on('click', '.btnDetail', function () {
            var content = JSON.parse($.base64.decode($(this).closest(".item").data('content')));
            console.log(content);
            var modal = $("#detailModal");
            modal.find(".task_id").html(content.id);
            modal.find(".task_name").html(content.name);
            modal.find(".task_time").html(getnowtime((content.extra.time) * 1000));
            modal.find(".task_next_time").html(content.next_run_time ? getnowtime(content.next_run_time * 1000) : '');
            modal.find(".task_trigger").html(content.trigger);
            modal.find(".task_trigger_args").html(getTriggerArgs(content.trigger, content.trigger_args));
            if (Object.keys(content.params.data).length > 0) {
                SetTab();
                data = content.params.data;
                html = ProcessObject(data, 0, false, false, false);
                modal.find(".task_params").html("<PRE class='CodeContainer'>" + html + "</PRE>");
            } else {
                modal.find(".task_params").html("")
            }
            modal.find(".task_cmd").html(content.params.cmd);
            modal.modal("show");
        })
    };
    exports.add = function () {
        $(".btnAdd").click(function () {
            $("#addModal").modal("show");
        });
        //添加任务
        $("#addModal #sureBtn").on('click', function () {
            var port = $(".tabs .tab.active").attr('data-val');

            var modal = $("#addModal");
            var trigger = modal.find(".trigger:checked").val();
            var task_id = $.trim(modal.find("#task_id").val());
            var task_name = $.trim(modal.find("#task_name").val());
            var task_cmd = $.trim(modal.find("#task_cmd").val());
            var task_callback = $.trim(modal.find("#task_callback").val());
            var task_callback_params = {};
            modal.find(".task_callback_params .params_item").each(function () {
                if ($(this).find('.keys').val() != '') {
                    task_callback_params[$(this).find('.keys').val()] = $(this).find('.values').val()
                }
            });
            var trigger_wrap = $(".trigger_arg_" + trigger);
            var trigger_args = {};
            trigger_wrap.find("input").each(function () {
                if ($(this).val() != '') {
                    trigger_args[$(this).attr('name')] = $(this).val();
                }
            });
            var data = {
                'trigger': trigger,
                'trigger_args': trigger_args,
                'task_id': task_id,
                'task_name': task_name,
                'cmd': task_cmd,
                'callback_url': task_callback,
                'task_callback': task_callback_params,
                'port': port
            };

            if (Object.keys(trigger_args).length == 0) {
                base.showTip('err', '时间参数没有填写', 1000);
                return false;
            }
            if (task_name == '') {
                base.showTip('err', '请填写任务名称', 1000);
                return false;
            }
            if (data.callback == '' && data.cmd == '') {
                base.showTip('err', '任务需指定操作', 1000);
                return false;
            }
            console.log(data);

            base.requestApi('/api/task/add', data, function (res) {
                if (res.result == 1) {
                    window.location.reload();
                }
            })
        })
    };
    exports.edit = function () {
        $(".list").on('click', '.btnEdit', function () {
            var content = JSON.parse($.base64.decode($(this).closest(".item").data('content')));
            console.log(content);
            var modal = $("#editModal");
            //modal.find(".task_id").html(content.id);
            //modal.find(".task_name").html(content.name);
            //modal.find(".task_time").html(getnowtime((content.extra.time) * 1000));
            //modal.find(".task_next_time").html(content.next_run_time ? getnowtime(content.next_run_time * 1000) : '');
            //modal.find(".task_trigger").html(content.trigger);
            //modal.find(".task_trigger_args").html(getTriggerArgs(content.trigger, content.trigger_args));
            //if (Object.keys(content.params.data).length > 0) {
            //    SetTab();
            //    data = content.params.data;
            //    html = ProcessObject(data, 0, false, false, false);
            //    modal.find(".task_params").html("<PRE class='CodeContainer'>" + html + "</PRE>");
            //} else {
            //    modal.find(".task_params").html("")
            //}
            //modal.find(".task_cmd").html(content.params.cmd);

            modal.find(".task_callback_params .params_item").each(function (index) {
                if (index != 0) {
                    $(this).remove();
                }
            });
            modal.find("#task_id").val(content.id);
            modal.find("#task_name").val(content.name);
            modal.find("#task_callback").val(content.params.url);
            modal.find("#task_cmd").val(content.params.cmd);
            modal.find(".trigger[value='" + content.trigger + "']").prop('checked', 'checked');
            if (Object.keys(content.params.data).length > 0) {

                var data = content.params.data;
                console.log(data);
                for (var i in data) {
                    var copy = modal.find(".task_callback_params tr").eq(1).clone();
                    copy.find(".keys").val(i);
                    copy.find(".values").val(data[i]);
                    copy.css({"display": 'table-row'});
                    modal.find(".task_callback_params").append(copy);
                }
            }
            var trigger_args = content.trigger_args;
            var trigger_args_wrap = modal.find(".trigger_arg_" + content.trigger);
            modal.find(".trigger_arg").hide();
            trigger_args_wrap.show();
            for (var j in trigger_args) {
                trigger_args_wrap.find("#" + j).val(trigger_args[j]);
            }
            modal.modal('show');

        });

        //编辑任务
        $("#editModal #sureBtn").on('click', function () {
            var port = $(".tabs .tab.active").attr('data-val');
            var modal = $("#editModal");
            var trigger = modal.find(".trigger:checked").val();
            var task_id = $.trim(modal.find("#task_id").val());
            var trigger_wrap = $(".trigger_arg_" + trigger);
            var trigger_args = {};
            var task_name = $.trim(modal.find("#task_name").val());
            var task_cmd = $.trim(modal.find("#task_cmd").val());
            var task_callback = $.trim(modal.find("#task_callback").val());
            var task_callback_params = {};
            modal.find(".task_callback_params .params_item").each(function () {
                if ($(this).find('.keys').val() != '') {
                    task_callback_params[$(this).find('.keys').val()] = $(this).find('.values').val()
                }
            });
            trigger_wrap.find("input").each(function () {
                if ($(this).val() != '') {
                    trigger_args[$(this).attr('name')] = $(this).val();
                }
            });
            var data = {
                'trigger': trigger,
                'trigger_args': trigger_args,
                'task_id': task_id,
                'task_name': task_name,
                'cmd': task_cmd,
                'callback_url': task_callback,
                'task_callback': task_callback_params,
                'port': port
            };

            if (Object.keys(trigger_args).length == 0) {
                base.showTip('err', '时间参数没有填写', 1000);
                return false;
            }
            if (task_name == '') {
                base.showTip('err', '请填写任务名称', 1000);
                return false;
            }
            if (data.callback == '' && data.cmd == '') {
                base.showTip('err', '任务需指定操作', 1000);
                return false;
            }

            base.requestApi('/api/task/edit', data, function (res) {
                if (res.result == 1) {
                    tip.showTip("ok", "编辑成功", 1000, function () {
                        window.location.reload();
                    });
                }
            })
        })
    };
    exports.pause = function () {
        $(".list").on('click', '.btnPause', function () {
            var id = $(this).closest(".item").data('id');
            var port = $(".tabs .tab.active").attr('data-val');

            $(this).confirm("确定要暂停吗", {
                ok: function () {
                    base.requestApi('/api/task/pause', {id: id, port: port}, function (res) {
                        if (res.result == 1) {
                            tip.showTip("ok", "操作成功", 1000, function () {
                                window.location.reload();
                            });
                        }
                    });
                },
                cancel: function () {
                    return false;
                }
            });
        });
    };
    exports.start = function () {
        $(".list").on('click', '.btnStart', function () {
            var id = $(this).closest(".item").data('id');
            var port = $(".tabs .tab.active").attr('data-val');

            $(this).confirm("确定要恢复吗", {
                ok: function () {
                    base.requestApi('/api/task/start', {id: id, port: port}, function (res) {
                        if (res.result == 1) {
                            tip.showTip("ok", "操作成功", 1000, function () {
                                window.location.reload();
                            });
                        }
                    });
                },
                cancel: function () {
                    return false;
                }
            });
        });
    };
    exports.remove = function () {
        $(".list").on('click', '.btnRemove', function () {
            var id = $(this).closest(".item").data('id');
            var port = $(".tabs .tab.active").attr('data-val');

            $(this).confirm("确定要删除吗?删除不可逆", {
                ok: function () {
                    base.requestApi('/api/task/remove', {id: id, port: port}, function (res) {
                        if (res.result == 1) {
                            tip.showTip("ok", "删除成功", 1000, function () {
                                window.location.reload();
                            });
                        }
                    });
                },
                cancel: function () {
                    return false;
                }
            });
        });
    };
    exports.getList = function () {
        var port = $(".tabs .tab.active").attr('data-val');
        base.requestApi('/api/task/getList', {port: port}, function (res) {
            if (res.result == 1) {
                var html = "";
                if (res.data.list.length > 0) {
                    for (var i in res.data.list) {
                        html += res.data.list[i];
                    }
                    $(".listData").html(html);
                }
                $(".json_content").each(function () {
                    SetTab();

                    var data = $(this).attr('data-content');
                    data = (JSON.parse($.base64.decode(data)));

                    html = ProcessObject(data, 0, false, false, false);
                    $(this).html("<PRE class='CodeContainer'>" + html + "</PRE>");
                });
            }
            console.log(res)
        }, false, true);
    };
    function getTriggerArgs(trigger, args) {
        var html = '<table class="trigger_detail">';
        //周期性
        if (trigger == 'interval') {
            html += '<tr class="head">' +
                '<th>weeks</th>' +
                '<th>days</th>' +
                '<th>hours</th>' +
                '<th>minutes</th>' +
                '<th>seconds</th>' +
                '<th>start_date</th>' +
                '<th>end_date</th>' +
                '</tr>'
            html += '<tr>' +
                '<td>' + (args.weeks !== undefined ? args.weeks : '') + '</td>' +
                '<td>' + (args.days !== undefined ? args.days : '') + '</td>' +
                '<td>' + (args.hours !== undefined ? args.hours : '') + '</td>' +
                '<td>' + (args.minutes !== undefined ? args.minutes : '') + '</td>' +
                '<td>' + (args.seconds !== undefined ? args.seconds : '') + '</td>' +
                '<td>' + (args.start_date !== undefined ? args.start_date : '') + '</td>' +
                '<td>' + (args.end_date !== undefined ? args.end_date : '') + '</td>' +
                '</tr>'
        }
        //一次性
        if (trigger == 'date') {
            html += '<tr>' +
                '<th>run_date</th>' +
                '</tr>'
            html += '<tr>' +
                '<td>' + (args.run_date !== undefined ? args.run_date : '') + '</td>' +
                '</tr>'
        }
        //cron
        if (trigger == 'cron') {
            html += '<tr>' +
                '<th>second</th>' +
                '<th>minute</th>' +
                '<th>hour</th>' +
                '<th>day_of_week</th>' +
                '<th>week</th>' +
                '<th>day</th>' +
                '<th>month</th>' +
                '<th>year</th>' +
                '<th>start_date</th>' +
                '<th>end_date</th>' +
                '</tr>'
            html += '<tr>' +
                '<td>' + (args.second !== undefined ? args.second : '') + '</td>' +
                '<td>' + (args.minute !== undefined ? args.minute : '') + '</td>' +
                '<td>' + (args.hour !== undefined ? args.hour : '') + '</td>' +
                '<td>' + (args.day_of_week !== undefined ? args.day_of_week : '') + '</td>' +
                '<td>' + (args.week !== undefined ? args.week : '') + '</td>' +
                '<td>' + (args.day !== undefined ? args.day : '') + '</td>' +
                '<td>' + (args.month !== undefined ? args.month : '') + '</td>' +
                '<td>' + (args.year !== undefined ? args.year : '') + '</td>' +
                '<td>' + (args.start_date !== undefined ? args.start_date : '') + '</td>' +
                '<td>' + (args.end_date !== undefined ? args.end_date : '') + '</td>' +
                '</tr>'
        }
        html += "</table>";
        return html
    }

    function getnowtime(timestamp) {
        var nowtime = new Date(timestamp);
        var year = nowtime.getFullYear();
        var month = padleft0(nowtime.getMonth() + 1);
        var day = padleft0(nowtime.getDate());
        var hour = padleft0(nowtime.getHours());
        var minute = padleft0(nowtime.getMinutes());
        var second = padleft0(nowtime.getSeconds());
        var millisecond = nowtime.getMilliseconds();
        millisecond = millisecond.toString().length == 1 ? "00" + millisecond : millisecond.toString().length == 2 ? "0" + millisecond : millisecond;
        return year + "-" + month + "-" + day + " " + hour + ":" + minute + ":" + second + "." + millisecond;
    }

    //补齐两位数
    function padleft0(obj) {
        return obj.toString().replace(/^[0-9]{1}$/, "0" + obj);
    }

});