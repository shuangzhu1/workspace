/**
 * Created by ykuang on 6/25/17.
 */
define(function (require, exports, module) {
    var base = require('app/panel/panel.base.js?v=1.0');//公共函数
    var store = require('app/panel/panel.storage');//公共函数
    var color = require('app/app.color');
    require('tools/plupload/plupload.min'); //导入图片上传插件

    base.selectNone();
    base.selectCheckbox();
    //  var page = base.pageList({'url': '/api/user/list'});

    /**
     * publish panorama or not
     *
     * @param btn
     * @param referer
     */

    /**
     * del panorama
     * @param btn
     */
    exports.page = function (option) {
        opt = {'url': '/api/user/list'};
        opt = $.extend(opt, option);
        var page = base.pageList(opt);
    };
    exports.delUsers = function (btn) {
        $(" .listData").on('click', btn, function (e) {
            // params
            var id = $(this).attr('data-id');
            var data = [id];
            // confirm
            var cm = window.confirm('你确定需要该条数据吗？');
            if (!cm) {
                return;
            }

            // api request
            base.requestApi('/api/user/delUser', {data: data}, function (res) {
                if (res.result == 1) {
                    $('.list .listData .item[data-id="' + id + '"]').fadeOut();
                    setTimeout(function () {
                        $('.list .listData .item[data-id="' + id + '"]').remove();
                    }, 1000);
                    base.showTip('ok', '删除成功！', 2000, function () {
                        page();
                    });
                }
            });
            e.stopImmediatePropagation();
        });
    };


    /*
     永久封号
     */
    exports.deleteUsers = function (btn) {
        $(" .listData").on('click', btn, function (e) {
            // params
            var id = $(this).attr('data-id');
            var data = [id];
            // confirm
            $(this).confirm("封号将会屏蔽其所有动态", {
                ok: function () {
                    base.requestApi('/api/user/deleteUser', {data: data}, function (res) {
                        if (res.result == 1) {
                            base.showTip('ok', '操作成功！', 2000, function () {
                                page();
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
    /*
     解除永久封号
     */
    exports.recoveryUsers = function (btn) {
        $(" .listData").on('click', btn, function (e) {
            // params
            var id = $(this).attr('data-id');
            var data = [id];
            // confirm
            $(this).confirm("您确认要解封该用户吗?", {
                ok: function () {
                    base.requestApi('/api/user/recoveryUser', {data: data}, function (res) {
                        if (res.result == 1) {
                            base.showTip('ok', '操作成功！', 2000, function () {
                                page();
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
    /*
     禁用账号
     */
    exports.forbidUsers = function (btn) {
        $(" .listData").on('click', btn, function (e) {
            // params
            var id = $(this).attr('data-id');
            var data = [id];
            // confirm
            $(this).confirm("您确认要禁用该用户吗?", {
                ok: function () {
                    base.requestApi('/api/user/forbidUsers', {data: data}, function (res) {
                        if (res.result == 1) {
                            base.showTip('ok', '操作成功！', 2000, function () {
                                page();
                            });

                            //window.location.reload();
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
    /*
     账号解除禁用
     */
    exports.unForbidUsers = function (btn) {
        $(" .listData").on('click', btn, function (e) {
            // params
            var id = $(this).attr('data-id');
            var data = [id];
            // confirm
            $(this).confirm("您确认要对该用户解除禁用吗?", {
                ok: function () {
                    base.requestApi('/api/user/unForbidUsers', {data: data}, function (res) {
                        if (res.result == 1) {
                            base.showTip('ok', '操作成功！', 2000, function () {
                                page();
                            });
                            // window.location.reload();

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
    exports.sendWarning = function () {
        $(".warningSuggest").on('change', function () {
            $(".msgValue").val($(this).find("option:selected").text())
        });
        $(".warningBtn").on('click', function () {
            var value = $.trim($(".msgValue").val())
            var uid = $(this).data('id')

            if (value == '' || !uid) {
                return false;
            }
            base.requestApi('/api/user/sendWarning', {'msg': value, 'uid': uid}, function (res) {
                if (res.result == 1) {
                    base.showTip('ok', '发送成功！', 1000, function () {
                    });

                }
            });
        })
    };

    /**
     * del panorama
     * @param btn
     */
    exports.delAlldelUsers = function (btn) {
        $(".user-list").on('click', btn, function (e) {
            var data = [];
            $(".list .listData input.chk").each(function () {
                if ($(this).attr('checked') == true || $(this).attr('checked') == 'checked') {
                    data.push($(this).attr('data-id'));
                }
            });

            //  has no selected
            if (data.length == 0) {
                base.showTip('err', '请选择需要删除的项', 3000);
                return;
            }
            // confirm
            var cm = window.confirm('你确定需要删除选中的数据吗？');
            if (!cm) {
                return;
            }

            // api request
            base.requestApi('/api/user/delUser', {'data': data}, function (res) {
                if (res.result == 1) {
                    for (var i = 0; i < data.length; i++) {
                        $('.listData .item[data-id="' + data[i] + '"]').remove();
                    }
                    base.showTip('ok', '操作成功！', 2000, function () {
                        page();
                    });

                }
            });
            e.stopImmediatePropagation();
        });
    };
    exports.exportsUsers = function (btn) {
        $(".user-list").on('click', btn, function (e) {
            var data = '';
            $(".list .listData input.chk").each(function () {
                if ($(this).attr('checked') == true || $(this).attr('checked') == 'checked') {
                    data += $(this).attr('data-id') + ',';
                }
            });

            //  has no selected
            if (data == '') {
                base.showTip('err', '请选择需要导出的用户', 3000);
                return;
            }
            // confirm
            var cm = window.confirm('你确定需要导出选中的数据吗？');
            if (!cm) {
                return;
            }
            window.location.href = '/api/user/exportUser?data=' + data.substring(0, data.length - 1);


        });
    };
    exports.exportsAllUsers = function (btn) {
        $(".user-list").on('click', btn, function (e) {
            var cm = window.confirm('你确定需要导出所有用户数据吗？');
            if (!cm) {
                return;
            }
            window.location.href = '/api/user/exportAllUser';
        });
    };
    //上传头像
    exports.uploadAvatar = function (elem, opt, func) {

        var unique = '';

        var type = opt.type; // img,media,file

        // choose type to upload
        var url = '/api/upload/avatar'; //img,file,media

        if (url == undefined) {
            $(elem + ' .up_file_list').html('please choose type');
            return false
        }

        var typeName = {
            'img': '图片'
        };

        // 根据widget设置选项
        $(elem).each(function () {
            unique = $(this).attr('data-unique');
            $(this).find('.browse-button').attr('id', 'browse_files_button_' + unique);
            $(this).find('.drop_element').attr('id', 'upload_drop_element_' + unique);
        });

        // default option
        var option = {
            type: 'img',// 必传参数：img,file,media
            auto_upload: false, // true, 自动上传。 false, 手动上传
            multi_selection: true,
            multipart_params: {},
            file_data_name: 'file'
        };


        // extend option
        option = $.extend({}, option, opt);
        var uploader = new plupload.Uploader({
            runtimes: 'html5,flash',
            url: url, // 请求url
            drop_element: 'upload_drop_element_' + unique, // 拖拽上传放置区域
            browse_button: 'browse_files_button_' + unique, // 选择图片按钮ID
            file_data_name: option.file_data_name,
            multi_selection: option.multi_selection, // 一次上传多张
            dragdrop: true,
            unique_names: true,
            multipart: true,
            multipart_params: {},
            flash_swf_url: '/data/Moxie.swf', // flash上传必要文件
            filters: {
                mime_types: [
                    {title: "Files", extensions: 'jpg,png,gif,jpeg,bmp,ico'}
                ],
                prevent_duplicates: false // Do not let duplicates into the queue. Dispatches `plupload.FILE_DUPLICATE_ERROR`.
            },
            init: {
                PostInit: function () {
                },
                // 发生错误时触发
                Error: function (up, err) {
                    tip.showTip("err", err.message, 1000);
                    console.log(err.message);
                    // $(elem + ' .err').html(err.message);
                }
            }
        });
        // init
        uploader.init();
        func(uploader);
    };
    exports.edit = function () {
        var modal = $("#editModal");
        var uploader = "";
        var dest_url = '';
        var avata_buffer = '';
        $('.list').on('click', '.editBtn', function () {
            var detail = JSON.parse(base64.decode($(this).data('detail')));
            modal.find("#selectAvator img").attr('src', detail.avatar);
            modal.find("#save").data('id', detail.user_id);
            modal.find("input[name='username']").val(detail.username);
            modal.find("input[name='sex'][value='" + detail.sex + "']").click();
            modal.modal('show');
            dest_url = detail.avatar;
        });

        exports.uploadAvatar('.upload-widget[data-unique="1"]', {'type': 'img'}, function (res) {
            uploader = res;
            uploader.bind('UploadFile', function () {
                modal.find(".success_msg").html("正在上传头像至服务器....");
                modal.find(".success-widget").show();
            });
            uploader.bind('FilesAdded', function (up, files) {
                console.log(up.files);
                if (up.files.length > 1) {
                    uploader.removeFile(up.files[0])
                }
            });
            uploader.bind('UploadComplete', function () {
                submit()
            });
            uploader.bind('FileUploaded', function (up, file, obj) {
                var res = $.parseJSON(obj.response); //PHP上传成功后返回的参数
                dest_url = res.data;
            });
            uploader.bind('UploadProgress', function (up, file) {
                console.log(file.percent);
            })
        });
        function submit() {
            uploader.refresh();
            var uid = modal.find('#save').data('id');
            var url = dest_url;
            modal.find(".success_msg").html("正在上传头像至OSS....");
            var username = modal.find("input[name='username']").val().trim();
            var sex = modal.find('input[name="sex"]:checked').val();
            var data = {
                uid: uid,
                avatar: url,
                username: username,
                sex: sex
            };

            base.requestApi("/api/user/editProfile", data, function (result) {
                if (result.result == 1) {
                    //  tip.showTip('ok', "编辑成功", 1000, function () {
                    window.location.href = '/users/index?type=3';
                    //    });
                }
            }, true, true)
        }

        //保存头像
        $("#editModal").on('click', "#save", function () {
            if (avata_buffer != '') {
                var uid = $(this).data('id');
                uploader.refresh();
                uploader.setOption({
                    multipart_params: {
                        uid: uid
                    }
                });
                uploader.addFile(avata_buffer);
                setTimeout(function () {
                    uploader.start();
                }, 1000);
            } else {
                submit();
            }

        });


        //选择头像
        $("#selectAvator").on('click', function () {
            var fileTag = document.getElementById('fileAvator');
            fileTag.onchange = function () {
                var image = document.getElementById('crop-source');
                var files = this.files;
                var file;
                if (files && files.length) {
                    file = files[0];
                    if (/^image\/\w+/.test(file.type)) {
                        uploadedImageType = file.type;
                        image.src = window.URL.createObjectURL(file);
                        var cropper = new Cropper(image, {
                            autoCropArea: true,
                            minContainerWidth: 600,
                            minContainerHeight: 400,
                            aspectRatio: 1,
                            crop: function () {
                                console.log(9999);
                                var result = this.cropper.getCroppedCanvas({fillColor: "#fff"});
                                var t_file = new mOxie.File(null, result.toDataURL(file.type));
                                t_file.name = "filename.jpg"; //
                                avata_buffer = t_file;
                                $('#preview').attr('src', result.toDataURL(file.type));
                                $("#crop-avator").modal('show');

                            }
                        });
                        /*new cropper 结束*/
                        $('#myModal').on('hide.bs.modal', function () {
                            cropper.destroy();
                        });
                        //裁剪完成按钮点击事件
                        $("#complete-crop").on('click', function () {
                            $("#selectAvator img").attr('src', $('#preview').attr('src'));
                            cropper.destroy();
                            $("#crop-avator").modal('hide');
                        });
                        $('#crop-avator').on('hide.bs.modal', function () {
                            $(document.body).unbind('keydown');
                            //cropper.destroy();
                        });
                        //方向键移动裁剪窗
                        $(document.body).on('keydown', function (e) {

                            if (!cropper.cropped || this.scrollTop > 300) {
                                return;
                            }

                            switch (e.which) {
                                case 37:
                                    e.preventDefault();
                                    cropper.move(-1, 0);
                                    break;

                                case 38:
                                    e.preventDefault();
                                    cropper.move(0, -1);
                                    break;

                                case 39:
                                    e.preventDefault();
                                    cropper.move(1, 0);
                                    break;

                                case 40:
                                    e.preventDefault();
                                    cropper.move(0, 1);
                                    break;
                            }

                        });

                    } else {
                        window.alert('Please choose an image file.');
                    }
                }
            };
            fileTag.click();

        });
    }


});