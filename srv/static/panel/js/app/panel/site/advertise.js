define(function (require, exports) {
    var base = require('app/panel/panel.base');//公共函数
    var store = require('app/panel/panel.storage.js?v=1.0');
    require('jquery/jquery.easing.min');
    //添加广告
    exports.addAdvertise = function () {
        //添加广告
        $(".list .add_btn").on('click', function () {
            $("#ads_key").val($(this).attr('data-key'));
            $("#adsModal").modal("show");
        });
        // 主图
        store.getImg('#uploadMainPic', function (res) {
            $('#thumb').val(res.url);
            $('#thumbPreview').attr('src', res.url);
        });
        // 添加广告
        $(document).on('click', '.submitBtn', function (e) {
            var data_base = $('#adsForm').serializeObject();
            var $key = $("#ads_key").val();
            if (!$key) {
                return;
            }
            data_base.department_id = $key;

            if (!data_base.thumb) {
                $('#thumb').focus();
                tip.showTip('err', '请上传广告图', 3000);
                return;
            }
            var data = {
                data_base: data_base
            };

            // ajax 提交
            base.requestApi('/api/ads/add', data, function (res) {
                if (res.result == 1) {

                    tip.showTip("ok", "添加成功", 1000);
                    setTimeout(function () {
                        window.location.reload();
                    }, 1000);

                }
            });
        })

    };

    //添加广告
    exports.addAdvertiseApp = function () {
        //添加广告
        $(".list .add_btn").on('click', function () {
            $("#ads_key").val($('#ads_key_list').val());
            $("#adsModal").modal("show");
        });
        // 主图
        store.getImg('#uploadMainPic', function (res) {
            $('#thumb').val(res.url);
            $('#thumbPreview').attr('src', res.url);
        });
        // 添加广告
        $(document).on('click', '.submitBtn', function (e) {
            var data_base = $('#adsForm').serializeObject();
            var $key = $("#ads_key").val();
            if (!$key) {
                return;
            }
            var type_input = $("input[name='content_type']:checked");
            if (type_input.length == 0) {
                tip.showTip('err', '请选择广告模型', 3000);
                return false;
            }
            var content_type = type_input.attr('data-type');//内容模型
            var content_name = type_input.attr('data-name');//内容模型名称
            if (content_type == "link") {
               /* if ($.trim($(".content_type_link").val()) == '') {
                    tip.showTip('err', '请输入链接地址', 3000);
                    $(".content_type_link").focus();
                    return false;
                }*/
                data_base.content_value = $(".content_type_link").val();
            } else {
                //引导数据
                data_base.content_value = "";
            }
            data_base.content_type = content_type;

            if (!data_base.thumb) {
                $('#thumb').focus();
                tip.showTip('err', '请上传广告图', 3000);
                return;
            }
            var data = {
                data_base: data_base
            };

            // ajax 提交
            base.requestApi('/api/ads/addApp', data, function (res) {
                if (res.result == 1) {
                    tip.showTip("ok", "添加成功", 1000);
                    setTimeout(function () {
                        window.location.reload();
                    }, 1000);

                }
            });
        })

    };
    //修改广告
    exports.upAdvertise = function () {
        //删除广告
        $(".list .del_btn").on('click', function () {
            var id = $(this).attr('data-id');
            base.requestApi('/api/ads/del', {id: id}, function (res) {
                if (res.result == 1) {
                    tip.showTip("ok", "删除成功", 1000);
                    setTimeout(function () {
                        $(".item_" + id).remove();
                    }, 1000);

                }
            });
        });
        //添加广告
        $(".list .up_btn").on('click', function () {
            $("#ads_key").val($(this).attr('data-key'));
            $("#adsForm .title").val($(this).attr('data-title'));
            $("#adsForm #thumb").val($(this).attr('data-img'));
            $("#adsForm .link").val($(this).attr('data-link'));
            // $("#adsForm .pic-preview").attr('src', $(this).attr('data-img'));
            $("#adsForm .submitBtn").attr('data-id', $(this).attr('data-id'));
            $("#adsModal").modal("show");
        });
        // 主图
        store.getImg('#uploadMainPic', function (res) {
            $('#thumb').val(res.url);
            $('#thumbPreview').attr('src', res.url);
        });
        // 添加广告
        $(document).on('click', '.submitBtn', function (e) {
                var data_base = $('#adsForm').serializeObject();
                if (!data_base.thumb) {
                    $('#thumb').focus();
                    tip.showTip('err', '请上传广告图', 3000);
                    return;
                }
                var data = {
                    data_base: data_base,
                    id: $("#adsForm .submitBtn").attr('data-id')
                };
                // ajax 提交
                base.requestApi('/api/ads/update', data, function (res) {
                    if (res.result == 1) {

                        tip.showTip("ok", "修改成功", 1000);
                        setTimeout(function () {
                            window.location.reload();
                        }, 1000);
                    }
                });
            }
        )

    };
    //修改广告
    exports.upAppAdvertise = function () {
        //删除广告
        $(".list .del_btn").on('click', function () {
            var id = $(this).attr('data-id');
            base.requestApi('/api/ads/del', {id: id}, function (res) {
                if (res.result == 1) {
                    tip.showTip("ok", "删除成功", 1000, function () {
                        $(".item[data-id='" + id + "']").remove();
                    });

                }
            });
        });

        $("#content_list").on('click', 'li', function () {
            var type = $("input[name='content_type']:checked").val();
            $(".content_type_" + type).attr('data-id', $(this).attr('data-id')).val($(this).attr('data-val'));
        });
        //添加广告
        $(".list .up_btn").on('click', function () {
            $("#ads_key").val($(this).attr('data-key'));
            $("#adsForm .title").val($(this).attr('data-title'));
            $("#adsForm #thumb").val($(this).attr('data-img'));
            $("#thumbPreview").attr('src', $(this).attr('data-img'));
            $("#sort").val($(this).attr('data-sort'));
            var type = $(this).attr('data-type');
            // $("#adsForm .pic-preview").attr('src', $(this).attr('data-img'));
            $(".submitBtn").attr('data-id', $(this).attr('data-id'));
            $("input[name='content_type'][value='" + type + "']").click();
            if (type != 'link') {
                $(".content_type_" + type).attr('data-id', $(this).attr('data-val'));
            } else {
                $(".content_type_" + type).val($(this).attr('data-val'));
            }
            $("#adsModal").modal("show");
        });
        // 主图
        store.getImg('#uploadMainPic', function (res) {
            $('#thumb').val(res.url);
            $('#thumbPreview').attr('src', res.url);
        });
        //发布/取消发布
        $(document).on('change', '.status', function (e) {
            var id = $(this).attr('data-id');
            var enable = $(this).prop('checked') ? '1' : '0';
            base.requestApi('/api/ads/enable', {
                    id: id,
                    enable: enable
                },
                function (res) {
                    if (res.result == 1) {
                        tip.showTip("ok", "修改成功", 1000);
                        /*  setTimeout(function () {
                         window.location.reload();
                         }, 1000);*/
                    }
                }
            )
            ;
        });
        // 修改广告
        $(document).on('click', '.submitBtn', function (e) {
            var data_base = $('#adsForm').serializeObject();
            var type_input = $("input[name='content_type']:checked");
            if (type_input.length == 0) {
                tip.showTip('err', '请选择广告模型', 3000);
                return false;
            }
            var content_type = type_input.attr('data-type');//内容模型
            var content_name = type_input.attr('data-name');//内容模型名称
            if (content_type == "link") {
                /*  if ($.trim($(".content_type_link").val()) == '') {
                 tip.showTip('err', '请输入链接地址', 3000);
                 $(".content_type_link").focus();
                 return false;
                 }*/
                data_base.content_value = $(".content_type_link").val();
            } else {
                //引导数据
                data_base.content_value = "";
            }
            data_base.content_type = content_type;

            if (!data_base.thumb) {
                $('#thumb').focus();
                tip.showTip('err', '请上传广告图', 3000);
                return;
            }
            var data = {
                data_base: data_base,
                id: $(".submitBtn").attr('data-id')
            };
            // ajax 提交
            base.requestApi('/api/ads/updateApp', data, function (res) {
                if (res.result == 1) {
                    tip.showTip("ok", "修改成功", 1000);
                    setTimeout(function () {
                        window.location.reload();
                    }, 1000);

                }
            });
        })

    };

})