/**
 * Created by ykuang on 2018/3/5.
 */
define(function (require, exports) {
    var base = require('app/panel/panel.base');//公共函数
    var opt = {
        prefix: '',//搜索或者跳文件夹时的前缀
        next_marker: '',//下一页跳转的标记
        max_keys: 100//每页显示的最大数据量
    };

    //初始化
    exports.init = function () {
        //全选
        $(".checkAll").on('click', function () {
            if ($(this).prop("checked") === false) {
                $(".check_item").each(function () {
                    //已经选中了
                    if ($(this).prop('checked') === false) {
                    } else {
                        $(this).click();
                    }
                    $(this).closest("li").removeClass('checked');
                })
            }
            //取消全选
            else {
                $(".check_item").each(function () {
                    //已经选中了
                    if ($(this).prop('checked') === true) {
                    } else {
                        $(this).click();
                    }
                    $(this).closest("li").addClass('checked');
                })
            }

        });

        base.requestApi('/api/crash/load', opt, function (res) {
            if (res.result == 1) {
                if (res.data.data) {
                    $(".listData").html(res.data.data);
                }
                if (res.data.back_tr != '') {
                    $(".listData").prepend(res.data.back_tr);
                }
                //   $(".pageBar").html(res.data.bar);
            }
        });

        $(".listData").on('click', '.folder', function () {
            opt.prefix = $(this).data('id');
            opt.next_marker = '';
            exports.reload();
        }).on('click', '.log_back a', function () {
            opt.prefix = $(this).data('id');
            opt.next_marker = '';
            exports.reload();
        }).on('click', '.btnScan', function () {
            $(".file_detail").attr('src', $(this).attr('data-href'));
            $("#fileModal").modal("show");
        }).on('click', '.loadMore', function () {
            if ($(this).data('id') != '') {
                opt.next_marker = $(this).data('id');
                exports.reload();
            }
        });
        //刷新
        $(".updateBtn").on('click', function () {
            opt.next_marker = '';
            exports.reload();
        });
        //删除文件或文件夹
        $(".removeBtn").on('click', function () {
            var list = {'dir': [], 'file': []};
            $(".check_item").each(function () {
                //已经选中了
                if ($(this).prop('checked') === true) {
                    if ($(this).attr('data-type') == 'file') {
                        list.file.push($(this).attr('data-id'));
                    } else {
                        list.dir.push($(this).attr('data-id'));
                    }
                }
            });
            if (list.dir.length == 0 && list.file.length == 0) {
                tip.showTip('err', '请选择需要删除的文件及文件夹');
                return false;
            }
            base.requestApi('/api/crash/remove', {data: list}, function (res) {
                if (res.result == 1) {
                    tip.showTip("ok", '删除成功', 1000, function () {
                        opt.next_marker = '';
                        exports.reload();
                    })
                }
            }, true);
        });

        $(".list").on('click', '.btnSolve', function () {
            var id = $(this).data('id');
            base.requestApi('/api/crash/checkTag', {path: id}, function (res) {
                if (res.result == 1) {
                    tip.showTip("ok", '标记成功', 1000, function () {
                        exports.reload();
                    })
                }
            }, true);
        })
    };
    //重新加载
    exports.reload = function () {
        base.requestApi('/api/crash/load', opt, function (res) {
            if (res.result == 1) {
                if (res.data.data) {
                    if (opt.next_marker != '') {
                        $(".load_item").remove();
                        $(".listData").append(res.data.data);
                    } else {

                        $(".listData").html(res.data.data);
                        if (res.data.back_tr != '') {
                            $(".listData").prepend(res.data.back_tr);
                        }
                    }

                }
            }
        }, true);
        if ($(".checkAll").prop('checked') === true) {
            $(".checkAll").click();
        }
    }
});