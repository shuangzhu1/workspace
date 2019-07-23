/**
 * Created by ykuang on 2016/12/19.
 */
define(function (require, exports) {

    var base = require('app/panel/panel.base');//公共函数
    require('tools/plupload/plupload.full.min.js');//上传组件
    var oss = require('/static/panel/js/app/app.oss.js');//oss上传
    var md5 = require('tools/browser-md5-file.js');//md5

    var uploader = '';
    var oss_upload = oss.oss();
    exports.uploadApk = function () {

        uploader = new plupload.Uploader({
            browse_button: 'upload-widget', //触发文件选择对话框的按钮，为那个元素id
            url: '/api/upload/app', //服务器端的上传页面地址
            flash_swf_url: '/data/Moxie.swf', //swf文件，当需要使用swf方式进行上传时需要配置该参数
            multipart: true,
            multi_selection: false, // 一次上传多个
            filters: {
                max_file_size: '100mb',
                min_file_size: '1mb',
                mime_types: [
                    {title: "Files", extensions: 'apk,ipa'}
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
                        // console.log(file);
                        submit(res.data);
                    } else {
                        tip.showTip("err", "上传失败", 1000);
                        //   console.log(res.error.msg + '【' + res.error.more + '】');
                        // $(elem + ' .err').html(res.error.msg + '【' + res.error.more + '】');
                    }
                },
                BeforeUpload: function (up, file) {
                    oss_upload(up, file.name, true);
                    browserMD5File(file.getNative(), function (err, md5) {
                        /*    console.log(md5); // 97027eb624f85892c69c4bcec8ab0f11
                         //  */
                        $("#file_MD5").val(md5);
                    });

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
    //提交数据
    function submit(data) {
        var modal = $("#versionModal");
        var version_id = $("input[name='version_id']").val();
        var version = modal.find("#version").val().trim();
        var os = modal.find("#os").val().trim();
        var detail = modal.find("#detail").val().trim();
        var limit_version = modal.find("#limit_version_" + os).val().trim();
        var app_md5 = $("#file_MD5").val();
        var status = $('input[name="status"]').val();
        $("#msgModal").modal('hide');
        if (data != '') {
            data = {
                url: data.filename,
                md5: app_md5
            };
        } else {
            data = {};
        }
        base.requestApi('/api/site/addVersion', {
            version_id: version_id,
            version: version,
            detail: detail,
            limit_version: limit_version,
            os: os,
            status:status,
            app: data
        }, function (res) {
            if (res.result == 1) {
                base.showTip('ok', res.data, 1000);
                modal.find(".success-widget").show();
                modal.find(".success-widget .success_msg").html(res.data);
                modal.find(".error-widget").hide();
                setTimeout(function () {
                    window.location.reload();
                }, 1000)
            } else {
                modal.find(".success-widget").hide();
                modal.find(".error-widget .error_msg").html(res.error.msg);
                modal.find(".error-widget").show();
            }
        }, false, true);
    }

    exports.edit = function () {
        var modal = $("#versionModal");
        var file_upload = exports.uploadApk();

        //系统切换
        $("#os").on('change', function () {
            var os = $(this).val();
            modal.find(".limit_version_" + os).show();
            modal.find(".limit_version_" + (os == 'android' ? 'ios' : 'android')).hide();
        });

        //发布版本
        $(".btnAdd").on('click', function () {
            modal.find("input[name='version_id']").val('');
            modal.find("input[name='status']").val('');
            modal.find("#version").val('');
            modal.find("#os").val('android');
            modal.find("#detail").val('');
            modal.find(".error-widget").hide();
            modal.find(".success-widget").hide();
            modal.find('.modal-title').html("发布版本 <i class='fa blue  fa-mobile-phone'></i> ");
            if (!(modal.find("#enable").attr('checked') == 'checked')) {
                modal.find("#enable").click();
            }
            modal.find(".limit_version_android").show();

            modal.find(".limit_version_ios").hide();
            modal.modal('show');
        });
        //编辑版本
        $(".editBtn").on('click', function () {
            var __this = $(this);
            $("input[name='version_id']").val(__this.data('id'));
            modal.find("#version").val(__this.data('version'));
            modal.find("#os").val(__this.data('os'));
            modal.find("#detail").val(__this.data('detail'));
            modal.find(".error-widget").hide();
            modal.find(".success-widget").hide();
            modal.find('.modal-title').html("编辑版本 <i class='fa blue  fa-mobile-phone'></i> ");
            if (!(modal.find("#enable").attr('checked') == 'checked')) {
                modal.find("#enable").click();
            }
            modal.find("#limit_version_" + __this.data('os')).val(__this.data('limit_version'));
            modal.find(".limit_version_android").hide();
            modal.find(".limit_version_ios").hide();
            modal.find(".limit_version_" + __this.data('os')).show();

            modal.modal('show');
        });
        //删除版本
        $(".removeBtn").on('click', function () {
            var id = $(this).attr('data-id');
            $(this).confirm("确定要删除吗?不可逆", {
                ok: function () {
                    base.requestApi('/api/site/delVersion', {
                        version_id: id,
                    }, function (res) {
                        if (res.result === 1) {
                            base.showTip('ok', "删除成功", 1000,function () {
                                window.location.reload();
                            });

                        }
                    }, false, true);
                },
                cancel: function () {
                    return false;
                }
            });
        });

        //发布、取消发布版本
        $(".release,.unrelease").on('click', function () {
            var action = $(this).attr('data-action');
            var id = $(this).attr('data-id');
            var warning = '';
            if(action === 'release')
                warning = '确定要发布该版本？';
            else
                warning = '确定撤回该版本？';
            $(this).confirm(warning, {
                ok: function () {
                    base.requestApi('/api/site/release', {
                        id:id,
                        action:action
                    }, function (res) {
                        if (res.result === 1) {
                            base.showTip('ok', "操作成功", 1000,function () {
                                window.location.reload();
                            });
                        }
                    }, false, true);
                },
                cancel: function () {
                    return false;
                }
            });
        });

        //确定
        modal.find(".sureBtn").on('click', function () {
            var version_id = $("input[name='version_id']").val();
            var version = modal.find("#version").val().trim();
            var detail = modal.find("#detail").val().trim();
            var os = modal.find("#os").val().trim();
            $('input[name="status"]').val($(this).data('status'));

            if (version == '') {
                modal.find(".error-widget .error_msg").html("请输入版本号");
                modal.find(".error-widget").show();
                return false;
            }
            if (!/^[0-9]+\.[0-9]+\.[0-9]+$/.test(version)) {
                modal.find(".error-widget .error_msg").html("请输入正确的版本号");
                modal.find(".error-widget").show();
                return false;
            }

            if (version_id == 0 && modal.find("#limit_version_" + os).find('option').length > 1) {
                var option = modal.find("#limit_version_" + os).find('option')[1];
                //if (parseInt($(option).val().replace(/\./g, '')) >= parseInt(version.replace(/\./g, ''))) {
                //    modal.find(".error-widget .error_msg").html("版本号必须大于已发布的最新版本号【" + $(option).val() + '】');
                //    modal.find(".error-widget").show();
                //    return false;
                //}
            }
            if (os != 'ios' && $("#fileUrl").html() == '' && version_id == 0) {
                modal.find(".error-widget .error_msg").html("请选择文件");
                modal.find(".error-widget").show();
                return false;
            }
            if (detail == '') {
                modal.find(".error-widget .error_msg").html("请输入版本详情");
                modal.find(".error-widget").show();
                return false;
            }

            //编辑版本
            if (version_id > 0) {
                $(this).confirm("确定要修改吗?", {
                    ok: function () {
                        if (os == 'android' && $("#fileUrl").html() != '') {
                            oss_upload(uploader, '', false);
                            file_upload.start();
                        } else {
                            submit('');
                        }
                    },
                    cancel: function () {
                        return false;
                    }
                });
            }
            //添加版本
            else {
                $(this).confirm("确定要发布吗?", {
                    ok: function () {
                        if (os == 'ios') {
                            submit('itms://itunes.apple.com/cn/app/kong-long-gu/id1208329358?mt=8');
                        } else {
                            oss_upload(uploader, '', false);
                            file_upload.start();
                        }
                    },
                    cancel: function () {
                        return false;
                    }
                });
            }
        })

    }

});