define(function (require, exports) {
    var base = require('app/panel/panel.base');
    /**
     * del post
     * @param btn
     */
    base.selectNone();
    base.selectCheckbox();
    exports.del = function () {
        $(".list").on('click', '.delBtn', function (e) {
            // params
            var id = $(this).attr('data-id');
            var data = [id];
            // confirm
            var __this = $(this);
            $(this).confirm("您确认屏蔽该条数据吗?", {
                ok: function () {
                    base.requestApi('/api/discuss/del', {data: data}, function (res) {
                        if (res.result == 1) {
                            base.showTip('ok', '屏蔽成功！', 1000);
                            __this.html("恢复");
                            __this.removeClass("delBtn").addClass("btn-success recoveryBtn");
                        }
                    });
                },
                cancel: function () {
                    return false;
                }
            });

            e.stopImmediatePropagation();
        }).on('click', '.recoveryBtn', function (e) {
            // params
            var id = $(this).attr('data-id');
            var data = [id];
            var __this = $(this);
            // confirm
            $(this).confirm("您确认恢复该条数据吗?", {
                ok: function () {
                    base.requestApi('/api/discuss/recovery', {data: data}, function (res) {
                        if (res.result == 1) {
                            base.showTip('ok', '恢复成功！', 1000);
                            __this.html("屏蔽");
                            __this.removeClass("btn-success recoveryBtn").addClass("delBtn");
                        }
                    });
                },
                cancel: function () {
                    return false;
                }
            });

            e.stopImmediatePropagation();
        }).on('click', '.delAllSelected', function (e) {
            var data = [];
            $(".list .listData input.chk").each(function () {
                if ($(this).attr('checked') == true || $(this).attr('checked') == 'checked') {
                    data.push($(this).attr('data-id'));
                }
            });

            //  has no selected
            if (data.length == 0) {
                base.showTip('err', '请选择需要屏蔽的项', 3000);
                return;
            }
            // confirm
            var cm = window.confirm('你确定需要屏蔽选中的数据吗？');
            if (!cm) {
                return;
            }

            var __this = $(this);
            $(this).confirm("您确认屏蔽选中的数据吗?", {
                ok: function () {
                    base.requestApi('/api/discuss/del', {data: data}, function (res) {
                        if (res.result == 1) {
                            base.showTip('ok', '屏蔽成功！', 1000, function () {
                                window.location.reload()
                            });
                        }
                    });
                },
                cancel: function () {
                    return false;
                }
            });


            e.stopImmediatePropagation();
        });
    };
    exports.recommend = function () {
        //推荐
        $(".recommendBtn").on('click', function () {
            var id = $(this).attr('data-id');
            var data = [id];
            base.requestApi('/api/discuss/recommend', {data: data, type: 1}, function (res) {
                if (res.result == 1) {
                    base.showTip('ok', '推荐成功！', 1000, function () {
                        window.location.reload()
                    });
                }
            });
        });
        //取消推荐
        $(".delRecommendBtnBtn").on('click', function () {
            var id = $(this).attr('data-id');
            var data = [id];
            base.requestApi('/api/discuss/recommend', {data: data, type: 0}, function (res) {
                if (res.result == 1) {
                    base.showTip('ok', '取消推荐成功！', 1000, function () {
                        window.location.reload()
                    });
                }
            });
        })
    };
    exports.hideTag = function () {
        //在标签页显示
        $(".showTag").on('click', function () {
            var id = $(this).attr('data-id');
            var data = [id];
            base.requestApi('/api/discuss/showTag', {data: data, type: 1}, function (res) {
                if (res.result == 1) {
                    base.showTip('ok', '设置成功！', 1000, function () {
                        window.location.reload()
                    });
                }
            });
        });
        //在标签页隐藏
        $(".hideTag").on('click', function () {
            var id = $(this).attr('data-id');
            var data = [id];
            base.requestApi('/api/discuss/showTag', {data: data, type: 0}, function (res) {
                if (res.result == 1) {
                    base.showTip('ok', '设置成功！', 1000, function () {
                        window.location.reload()
                    });
                }
            });
        })
    };
    exports.btnBillboard = function () {
        //推荐今日榜单
        $(".btnBillboard").on('click', function () {
            var id = $(this).attr('data-id');
            base.requestApi('/api/discuss/billboard', {discuss_id: id, type: 1}, function (res) {
                if (res.result == 1) {
                    base.showTip('ok', '设置成功！', 1000, function () {
                        window.location.reload()
                    });
                }
            });
        });
        //取消今日榜单
        $(".btnBillboardRemove").on('click', function () {
            var id = $(this).attr('data-id');
            base.requestApi('/api/discuss/billboard', {discuss_id: id, type: 0}, function (res) {
                if (res.result == 1) {
                    base.showTip('ok', '设置成功！', 1000, function () {
                        window.location.reload()
                    });
                }
            });
        });
    };
    exports.edit = function () {
        $(function () {
            var tags = $(".tagBtn").attr('data-val');
            if (tags != '') {
                tags = tags.split(',');
                for (var i in tags) {
                    $("input[name='tag'][data-val='" + tags[i] + "']").click();
                }
            }
        });
        $(".tagBtn").on('click', function () {
            $("#tagModal").modal("show");
        });
        $("#tagModal").on('click', '#sureBtn', function () {
            var tag = '';//标签
            var discuss_id = $("#discuss_id").val();
            $(".tag").each(function () {
                if ($(this).prop('checked')) {
                    tag += ',' + $(this).val();
                }
            });
            tag = tag ? tag.substr(1) : '';
            if (tag == '') {
                $(this).confirm("你没有选中任何标签,确定要清空标签吗?", {
                    ok: function () {
                        base.requestApi('/api/discuss/editTag', {tag: tag, discuss_id: discuss_id}, function (res) {
                            if (res.result == 1) {
                                base.showTip('ok', '修改成功！', 1000, function () {
                                    window.location.reload()
                                });
                            }
                        });
                    },
                    cancel: function () {
                        return false;
                    }
                });
            } else {
                base.requestApi('/api/discuss/editTag', {tag: tag, discuss_id: discuss_id}, function (res) {
                    if (res.result == 1) {
                        base.showTip('ok', '修改成功！', 1000, function () {
                            window.location.reload()
                        });
                    }
                });
            }
        });
    }
})
;
