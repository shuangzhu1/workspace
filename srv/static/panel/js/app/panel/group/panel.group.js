/**
 * Created by ykuang on 2016/12/27.
 */
define(function (require, exports) {
    var base = require('app/panel/panel.base');
    require('/static/panel/js/jquery/jquery.pagination.js');
    /**
     * del post
     * @param btn
     */
    exports.del = function () {
        $("#checkModal #sureBtn").on('click', function () {
            var reason = $("#reason").val();
            var data = [$("#group_id").val()];
            if (!reason) {
                base.showTip('err', '请输入封群原因', 1000);
                return false;
            }

            base.requestApi('/api/group/del', {
                data: data,
                reason: reason,
            }, function (res) {
                if (res.result == 1) {
                    base.showTip('ok', '操作成功！', 3000, function () {
                        $('#checkModal').modal('hide');
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
            $('#checkModal').modal('show');
            $("#group_id").val(id);
            e.stopImmediatePropagation();
            /*   $(this).confirm("您确认封杀该群吗?", {
             ok: function () {
             base.requestApi('/panel/api/group/del', {data: data}, function (res) {
             if (res.result == 1) {
             base.showTip('ok', '操作成功！', 1000);
             __this.html("恢复正常");
             __this.removeClass("delBtn").addClass("btn-success recoveryBtn");
             }
             });
             },
             cancel: function () {
             return false;
             }
             });*/
        }).on('click', '.recoveryBtn', function (e) {
            // params
            var id = $(this).attr('data-id');
            var data = [id];
            var __this = $(this);
            // confirm
            $(this).confirm("您确认恢复该群吗?", {
                ok: function () {
                    base.requestApi('/api/group/recovery', {data: data}, function (res) {
                        if (res.result == 1) {
                            base.showTip('ok', '恢复成功！', 1000);
                            __this.html("封杀该群");
                            __this.removeClass("btn-success recoveryBtn").addClass("delBtn");
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
    exports.getList = function (page_index) {
        var data = {
            page: page_index,
            gid: $('.pagination').attr('data-gid'),
            limit: 10,
        };
        base.requestApi("/api/group/memberList", data, function (res) {
            if (res.data == '') {
                $(".page_content").html("<li>暂时还没有数据</li>");
            } else {
                $(".page_content").html(res.data);
            }
        }, false, true)

    };


    //刷新分页
    exports.refreshPagination = function (flag) {
        var limit = 10;
        if (flag == 1) {
            var data = {
                gid: $('.pagination').attr('data-gid'),
                limit: limit
            };
            requestApi("/api/group/memberCount", data, function (res) {
                if (res.result == 1) {
                    $(".pagination").attr('data-total', res.data);
                    var total = res.data;
                    // 创建分页
                    $('.pagination').pagination(Math.ceil(total / limit), {
                        num_edge_entries: 1, //边缘页数
                        num_display_entries: 8, //主体页数
                        items_per_page: 1, //每页显示1项
                        link_to: 'javascript:;',
                        prev_text: '上一页',
                        next_text: '下一页',
                        callback: function (page_index) {
                            exports.getList(page_index);
                        }
                    });
                }
            })
        }
        else {  //分页
            var total = $(".pagination").attr('data-total');
            // 创建分页
            $('.pagination').pagination(Math.ceil(total / limit), {
                num_edge_entries: 1, //边缘页数
                num_display_entries: 8, //主体页数
                items_per_page: 1, //每页显示1项
                link_to: 'javascript:;',
                prev_text: '上一页',
                next_text: '下一页',
                callback: function (page_index) {
                    exports.getList(page_index);
                }
            });

        }

    };
})
;
