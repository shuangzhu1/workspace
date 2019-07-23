/**
 * Created by ykuang on 2016/12/19.
 */
define(function (require, exports) {
    var base = require('app/panel/panel.base.js?v=1.0');//公共函数
    var storage = require('app/panel/panel.storage.js?v=1.0');//storage

    base.selectNone();
    base.selectCheckbox();
    exports.uploadZip = function () {
        uploader = new plupload.Uploader({
            browse_button: 'upload-widget', //触发文件选择对话框的按钮，为那个元素id
            url: '/api/upload/gift', //服务器端的上传页面地址
            flash_swf_url: '/data/Moxie.swf', //swf文件，当需要使用swf方式进行上传时需要配置该参数
            multipart: true,
            multi_selection: false, // 一次上传多个
            filters: {
                max_file_size: '20mb',
                min_file_size: '10kb',
                mime_types: [
                    {title: "Files", extensions: 'zip,rar'}
                ],
                prevent_duplicates: false // Do not let duplicates into the queue. Dispatches `plupload.FILE_DUPLICATE_ERROR`.
            },
            init: {
                PostInit: function () {
                },

                // 添加文件后触发
                FilesAdded: function (up, files) {
                    $(".percent").html("");
                    plupload.each(files, function (file) {
                        $("#fileUrl").html(file.name);
                        $("#file_ID").val($(".fileSection").find('input[type="file"]').attr('id'));
                        //$("#audio").data('duration', 0);
                        // var url = URL.createObjectURL(file.getNative());
                        //$("#audio").attr('src', url).unbind().on('canplaythrough', function (e) {
                        //    var duration = Math.floor(e.target.duration);
                        //    $("#audio").attr('data-duration', duration);
                        //    uploader.setOption({
                        //        multipart_params: {
                        //            duration: duration
                        //        }
                        //    })
                        //});

                    });
                },
                // 百分比进度条
                UploadProgress: function (up, file) {
                    if (parseInt(file.percent) != 100) {
                        $(".percent").html("%" + file.percent);
                    } else {
                        $(".percent").html("【正在处理中...】");
                    }
                    var modal = $("#msgModal");
                    modal.modal('show');
                    console.log(file.percent);
                    // $('#' + file.id + ' b:first').html('<span>' + file.percent + '%</span>');
                },
                // 上传成功后触发
                FileUploaded: function (up, file, obj) {
                    var res = $.parseJSON(obj.response); //PHP上传成功后返回的参数
                    if (res.result == 1) {
                        exports.setAnimation(res.data);
                    } else {
                        tip.showTip("err", "上传失败", 1000);
                        //   console.log(res.error.msg + '【' + res.error.more + '】');
                        // $(elem + ' .err').html(res.error.msg + '【' + res.error.more + '】');
                    }
                },

                // 发生错误时触发
                Error: function (up, err) {
                    console.log(err.message);
                    // $(elem + ' .err').html(err.message);
                }
            }
        });
        uploader.init();
        return uploader;
    };
    //设置动效地址
    exports.setAnimation = function (url) {
        var page = base.pageList({'url': '/api/gift/getList'});
        base.requestApi('/api/gift/setAnimation', {
            gift_id: $("#gift_id").val(),
            animate: url
        }, function (res) {
            if (res.result == 1) {
                base.showTip('ok', res.data, 1000, function () {
                    $("#msgModal").modal('hide');
                    $("#tagModal").modal("hide");
                    page();
                });
            } else {
            }
        }, true, true);
    };
    exports.edit = function () {
        var uploader = exports.uploadZip();
        var page = base.pageList({'url': '/api/gift/getList'});
        storage.getImg('#upTagThumb', function (res) {
            $('#thumb').val(res.url);
            $('.preview-thumb').attr('src', res.url);
        }, false);
        var modal = $("#tagModal");
        //礼物编辑
        $(".listData").on('click', '.editBtn', function () {
            modal.find("#name").val($(this).data('name'));
            /*   modal.find("#sort_num").val($(this).data('sort_num'));*/
            modal.find("#gift_id").val($(this).data('id'));
            modal.find("#thumb").val($(this).data('thumb'));
            // modal.find("#is_vip").val($(this).data('vip'));
            modal.find("#coins").val($(this).data('coins'));
            modal.find("#charm").val($(this).data('charm'));
            modal.find("#fileUrl").html($(this).data('animation'));

            modal.find(".preview-thumb").attr('src', $(this).data('thumb'));
            if (($(this).data('enable') == '1' && !modal.find("#enable").prop('checked')) || ($(this).data('enable') == '0' && modal.find("#enable").attr('checked'))) {
                modal.find("#enable").click();
            }
            if (($(this).data('vip') == '1' && !modal.find("#is_vip").prop('checked')) || ($(this).data('vip') == '0' && modal.find("#is_vip").prop('checked'))) {
                modal.find("#is_vip").click();
            }
            if (($(this).data('recommend') == '1' && !modal.find("#is_recommend").prop('checked')) || ($(this).data('recommend') == '0' && modal.find("#is_recommend").prop('checked'))) {
                modal.find("#is_recommend").click();
            }
            modal.find(".error-widget").hide();
            modal.find(".success-widget").hide();
            modal.find('.modal-title').html("礼物编辑");
            modal.modal('show');
        });
        //添加礼物
        $(".btnAdd").on('click', function () {
            modal.find("#name").val('');
            /*    modal.find("#sort_num").val(50);*/
            modal.find("#gift_id").val(0);
            modal.find("#thumb").val('');
            modal.find("#coins").val(0);
            modal.find("#charm").val(0);
            modal.find(".preview-thumb").attr('src', '');
            modal.find(".error-widget").hide();
            modal.find(".success-widget").hide();
            modal.find('.modal-title').html("添加礼物");
            if (!(modal.find("#enable").is(":checked"))) {
                modal.find("#enable").click();
            }
            if ((modal.find("#is_vip").is(":checked"))) {
                modal.find("#is_vip").click();
            }
            if ((modal.find("#is_recommend").is(":checked"))) {
                modal.find("#is_recommend").click();
            }
            modal.modal('show');
        });

        //禁用
        $(".listData").on('click', '.lockBtn', function () {
            var gift_id = $(this).attr('data-id');
            $(this).confirm("确定要禁用吗?", {
                ok: function () {
                    base.requestApi('/api/gift/lock', {
                        gift_id: gift_id,
                    }, function (res) {
                        if (res.result == 1) {
                            base.showTip('ok', res.data, 1000, function () {
                                page();
                                //  window.location.reload()
                            });
                        } else {
                        }
                    });
                },
                cancel: function () {
                    return false;
                }
            });
        }).on('click', ".delBtn", function () {
            //删除
            var gift_id = $(this).attr('data-id');
            $(this).confirm("确定要删除吗?", {
                ok: function () {
                    base.requestApi('/api/gift/remove', {
                        gift_id: gift_id
                    }, function (res) {
                        if (res.result == 1) {
                            base.showTip('ok', res.data, 1000, function () {
                                page();
                            });
                        } else {
                        }
                    });
                },
                cancel: function () {
                    return false;
                }
            });
        }).on('click', ".unLockBtn", function () {
            //解除禁用
            var gift_id = $(this).attr('data-id');
            $(this).confirm("确定要解除禁用吗?", {
                ok: function () {
                    base.requestApi('/api/gift/unLock', {
                        gift_id: gift_id,
                    }, function (res) {
                        if (res.result == 1) {
                            base.showTip('ok', res.data, 1000, function () {
                                page();
                            });
                        } else {
                        }
                    });
                },
                cancel: function () {
                    return false;
                }
            });
        }).on('click', '.recommendBtn', function () {
            var gift_id = $(this).attr('data-id');
            var recommend = $(this).prop('checked') == true ? 1 : 0;
            base.requestApi('/api/gift/recommend', {
                gift_id: gift_id,
                recommend: recommend
            }, function (res) {
                if (res.result == 1) {
                    base.showTip('ok', res.data, 1000, function () {
                        page();
                    });
                } else {
                }
            });
        });
        //确定
        modal.find("#sureBtn").on('click', function () {
            var gift_id = modal.find("#gift_id").val();
            var name = modal.find("#name").val().trim();
            var thumb = modal.find("#thumb").val().trim();
            var coins = modal.find("#coins").val().trim();
            var charm = modal.find("#charm").val().trim();

            var is_vip = 0;
            /*   var sort_num = parseInt(modal.find("#sort_num").val().trim());*/
            var enable = 1;
            var is_recommend = 0;
            if (!(modal.find("#enable").is(":checked"))) {
                enable = 0;
            }
            if ((modal.find("#is_vip").is(":checked"))) {
                is_vip = 1;
            }
            if ((modal.find("#is_recommend").is(":checked"))) {
                is_recommend = 1;
            }
            if (name == '') {
                modal.find(".error-widget .error_msg").html("请输入标签名称");
                modal.find(".error-widget").show();
                return false;
            }
            if (isNaN(charm) || !/^[0-9]+$/.test(charm)) {
                modal.find(".error-widget .error_msg").html("魅力值必须为整数");
                modal.find(".error-widget").show();
                return false;
            }
            if (isNaN(coins) || !/^[0-9]+$/.test(coins)) {
                modal.find(".error-widget .error_msg").html("龙豆值必需为一个整数");
                modal.find(".error-widget").show();
                return false;
            }
            //编辑礼物
            if (gift_id > 0) {
                /*   $(this).confirm("确定要修改吗?", {
                 ok: function () {*/
                base.requestApi('/api/gift/edit', {
                    gift_id: gift_id,
                    name: name,
                    coins: coins,
                    is_vip: is_vip,
                    is_recommend: is_recommend,
                    /*     sort_num: sort_num,*/
                    enable: enable,
                    thumb: thumb,
                    charm: charm
                }, function (res) {
                    if (res.result == 1) {
                        if ($("#file_ID").val() != '') {
                            uploader.setOption({
                                multipart_params: {
                                    id: gift_id
                                }
                            });
                            uploader.start();
                        } else {
                            base.showTip('ok', res.data, 1000);
                            modal.find(".success-widget").show();
                            modal.find(".success-widget .success_msg").html(res.data);
                            modal.find(".error-widget").hide();
                            setTimeout(function () {
                                modal.modal('hide');
                                page();
                            }, 1000);
                        }

                        /*  $(".close").on('click', function () {
                         page();
                         })*/
                    } else {
                        modal.find(".success-widget").hide();
                        modal.find(".error-widget .error_msg").html(res.error.msg);
                        modal.find(".error-widget").show();
                    }
                }, true, true);
                /*   },
                 cancel: function () {
                 return false;
                 }
                 });*/
            }
            //添加礼物
            else {
                /*   $(this).confirm("确定要添加吗?", {
                 ok: function () {*/
                base.requestApi('/api/gift/edit', {
                    gift_id: gift_id,
                    name: name,
                    coins: coins,
                    is_vip: is_vip,
                    is_recommend: is_recommend,
                    /*      sort_num: sort_num,*/
                    enable: enable,
                    thumb: thumb
                }, function (res) {
                    if (res.result == 1) {
                        if ($("#file_ID").val() != '') {
                            $("#gift_id").val(res.data);
                            uploader.setOption({
                                multipart_params: {
                                    id: res.data
                                }
                            });
                            uploader.start();
                        } else {
                            base.showTip('ok', res.data, 1000);
                            modal.find(".success-widget").show();
                            modal.find(".success-widget .success_msg").html(res.data);
                            modal.find(".error-widget").hide();
                            setTimeout(function () {
                                modal.modal('hide');
                                page();
                            }, 1000);
                        }

                        /* $(".close").on('click', function () {
                         page();
                         })*/

                    } else {
                        modal.find(".success-widget").hide();
                        modal.find(".error-widget .error_msg").html(res.error.msg);
                        modal.find(".error-widget").show();
                    }
                }, true, true);
                /*  },
                 cancel: function () {
                 return false;
                 }
                 });*/
            }
        })

    };
    exports.record = function (opt) {
        var page = base.pageList(opt);
    }

});