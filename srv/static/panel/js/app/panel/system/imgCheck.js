/**
 * Created by ykuang on 2016/12/19.
 */
define(function (require, exports) {
    var base = require('app/panel/panel.base');//公共函数
    var opt = {
        start: "",
        end: "",
        score_start: '',
        score_end: '',
        page: 1,
        limit: 9
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

        $(".list").on('mouseover', 'li', function (e) {
            var __this = $(this);
            if ($(this).find(".handle_filter").length > 0) {
                __this.find(".handle_filter").toggle();
                __this.find(".handle_box").show();
            } else {
                $(".handle_filter").appendTo(__this).toggle();
                $(".handle_box").appendTo(__this).show();
            }
            e.stopPropagation();
        }).on('mouseout', 'li', function (e) {
            var __this = $(this);
            __this.find(".handle_box").hide();
            __this.find(".handle_filter").toggle();
            e.stopPropagation();
        });


        $(".list").on('click', 'li', function (e) {
            if ($(this).hasClass("checked")) {
                $(this).removeClass("checked");
            } else {
                $(this).addClass("checked");
            }
            $(this).find('.chk input').click();
        });
        $(".list").on('click', 'li input', function (e) {
            e.stopPropagation();
        });


        base.requestApi('/api/system/getImg', opt, function (res) {
            if (res.result == 1) {
                var html = "";
                if (res.data.list.length > 0) {
                    for (var i in res.data.list) {
                        html += res.data.list[i];
                    }
                    $(".list").html(html);
                }
                $(".pageBar").html(res.data.bar);
            }
        });
        //分页
        $(".pageBar").on("click", 'li a', function () {
            if ($(this).parent().hasClass("disabled")) {
                return;
            }
            if ($(this).attr("data-id") !== undefined) {
                moveHandleFilter();
                opt.page = $(this).attr('data-id');
                exports.reload();
            }
        });

        //单张删除
        $(".handle_box .porn").on('click', function () {
            var data = [];
            var __this = $(this).closest('li');
            data.push({id:__this.data('id'),type:__this.data('type')});
            if (data.length == 0) {
                tip.showTip("err", "没有图片被选中", 1000);
                return false;
            }

            $(this).confirm("确定要删除吗?操作不可逆", {
                ok: function () {
                    base.requestApi('/api/system/delImg', {data: data}, function (res) {
                        if (res.result == 1) {
                            moveHandleFilter();
                            __this.remove();
                        }
                    });
                },
                cancel: function () {
                    return false;
                }
            });
        });

        //单张忽略
        $(".handle_box .not_porn").on('click', function () {
            var data = [];
            var __this = $(this).closest('li');
            data.push(__this.data('id'));
            if (data.length == 0) {
                tip.showTip("err", "没有图片被选中", 1000);
                return false;
            }

            $(this).confirm("确定要忽略吗?操作不可逆", {
                ok: function () {
                    base.requestApi('/api/system/ignoreImg', {data: data}, function (res) {
                        if (res.result == 1) {
                            moveHandleFilter();
                            __this.remove();
                        }
                    });
                },
                cancel: function () {
                    return false;
                }
            });
        });
        //删除选中的图片
        $(".pornBtn").on('click', function () {
            var data = [];
            $(".list li.checked").each(function () {
                data.push({id:$(this).data('id'),type:$(this).data('type')});
            });
            if (data.length == 0) {
                tip.showTip("err", "没有图片被选中", 1000);
                return false;
            }
            $(this).confirm("确定要删除吗?操作不可逆", {
                ok: function () {
                    base.requestApi('/api/system/delImg', {data: data}, function (res) {
                        if (res.result == 1) {
                            moveHandleFilter();
                            $(".list li.checked").remove();
                        }
                    });
                },
                cancel: function () {
                    return false;
                }
            });
        });
        //忽略选中的图片
        $(".notPornBtn").on('click', function () {
            var data = [];
            $(".list li.checked").each(function () {
                data.push($(this).data('id'));
            });
            if (data.length == 0) {
                tip.showTip("err", "没有图片被选中", 1000);
                return false;
            }
            $(this).confirm("确定要忽略吗?操作不可逆", {
                ok: function () {
                    base.requestApi('/api/system/ignoreImg', {data: data}, function (res) {
                        if (res.result == 1) {
                            moveHandleFilter();
                            $(".list li.checked").remove();
                        }
                    });
                },
                cancel: function () {
                    return false;
                }
            });

        });
        $(".SearchBtn").on('click', function () {
            opt.start = $("#start").val();
            opt.end = $("#end").val();
            opt.score_start = $("#score_start").val();
            opt.score_end = $("#score_end").val();
            opt.page = 1;

            moveHandleFilter();
            exports.reload();
        });
        function moveHandleFilter() {
            //刷新前 把操作层移出去
            $(".handle_filter").hide();
            $(".handle_box").hide();
            $(".handle_filter").appendTo($("body"));
            $(".handle_box").appendTo($("body"));
        }
    };
    //重新加载
    exports.reload = function () {
        base.requestApi('/api/system/getImg', opt, function (res) {
            if (res.result == 1) {
                var html = "";
                if (res.data.list.length > 0) {
                    for (var i in res.data.list) {
                        html += res.data.list[i];
                    }
                    $(".list").html(html);
                }
                $(".pageBar").html(res.data.bar);
            }
        });
    }
});