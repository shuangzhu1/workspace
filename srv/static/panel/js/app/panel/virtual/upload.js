define(function (require, exports) {
    require("tools/jquery.imgReady.js");
    /*  require("app/panel/panel.base");*/

    var site_url = '';
    var api = {
        'img': site_url + '/api/upload/img',
//        'img':  'http://bleapi.estt.com.cn/api/user/updateAvatar',
        'file': site_url + '/api/upload/file',
        'video': site_url + '/api/upload/video',
        'audio': site_url + '/api/upload/audio'
    };

    var ext = {
        'img': 'jpg,png,gif,jpeg,bmp,ico',
        'file': 'zip,rar,doc,xls,ppt,docx,pptx,xlsx,txt',
        'video': 'mp4,3gp,ogg,webm,flv,f4v',
        'audio': 'mp3,m4a,ogg,spx,oga'
    };

    var maxSize = {
        'img': '5mb',
        'file': '20mb',
        'video': '200mb',
        'audio': '10mb'
    };

    var minSize = {
        'img': '0',
        'file': '2kb',
        'video': '100kb',
        'audio': '1mb'
    };
    require('tools/plupload/plupload.min'); //导入图片上传插件
    // require('tools/plupload/plupload.min'); //导入图片上传插件

    /**
     * upload widget
     *
     * useful code:
     <span class="upload-widget" data-unique="1"></span>
     <script>
     seajs.use('app/app.upload', function (api) {
            api.upload('.upload-widget[data-unique="1"]', {'type': 'img'}, function (res) {
                console.log(res);
            });
        });
     </script>
     *
     * options
     * @param elem :  wrap element
     * @param option
     * {
            'type':'img',// 必传参数：img,file,media
            'multi_selection': false, // 一次选择中，允许上传的数量
            'auto_upload': true, // true, 自动上传。 false, 手动上传
            'file_data_name':'file',
            'multipart_params': {} // 额外参数。键值对，用于后端验证等
       }
     * @param func callback function
     * @returns {boolean}
     */
    function previewImage(file, callback) {//file为plupload事件监听函数参数中的file对象,callback为预览图片准备完成的回调函数
        if (!file || !/image\//.test(file.type)) return; //确保文件是图片
        if (file.type == 'image/gif') {//gif使用FileReader进行预览,因为mOxie.Image只支持jpg和png
            var fr = new mOxie.FileReader();
            fr.onload = function () {
                var result = fr.result;
                //   fr.destroy();
                //   fr = null;
                callback(result);
            };
            fr.readAsDataURL(file.getSource());
        } else {
            var preloader = new mOxie.Image();
            preloader.onload = function () {
                //preloader.downsize(550, 400);//先压缩一下要预览的图片,宽300，高300
                var imgsrc = preloader.type == 'image/jpeg' ? preloader.getAsDataURL('image/jpeg', 80) : preloader.getAsDataURL(); //得到图片src,实质为一个base64编码的数据
                callback && callback(imgsrc, file); //callback传入的参数为预览图片的url
                preloader.destroy();
                preloader = null;
            };
            preloader.load(file.getSource());
        }
    }

    exports.upload = function (elem, opt, func) {
        var unique = '';
        var type = opt.type; // img,media,file

        // choose type to upload
        var url = api[type]; //img,file,media

        if (url == undefined) {
            $(elem + ' .up_file_list').html('please choose type');
            return false
        }

        var typeName = {
            'video': '视频',
            'audio': '音频',
            'img': '图片',
            'file': '文件'
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
            auto_upload: true, // true, 自动上传。 false, 手动上传
            multi_selection: false,
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
            multipart_params: {session: app.sess},
            flash_swf_url: '/data/Moxie.swf', // flash上传必要文件
            filters: {
                max_file_size: maxSize[type],
                min_file_size: minSize[type],
                mime_types: [
                    {title: "Files", extensions: ext[type]}
                ],
                prevent_duplicates: false // Do not let duplicates into the queue. Dispatches `plupload.FILE_DUPLICATE_ERROR`.
            },
            init: {
                PostInit: function () {
                    var initText = '请选择' + typeName[type];
                    if (option.multi_selection) initText = '按CTRL即可多选';
                    $(elem + ' .up_file_list').html('<span>' + initText + '</span>');
                },

                // 添加文件后触发
                FilesAdded: function (up, files) {

                    plupload.each(files, function (file) {
                        previewImage(file, function (imgsrc) {
                            var image = new Image();
                            image.src = imgsrc;
                            image.onload = function () {
                                var up_img = $(".img_list").length;
                                if (up_img > 8) {
                                    tip.showTip("err", "图片最多9张", 1000);
                                    $("#browse_files_button_undefined").hide();
                                    return false
                                }
                                var width = image.width;//图片的宽度
                                var height = image.height;//图片的高度

                                var html = '  <li class="img_list"><img class="imgReady" data-id="' + file.id + '" data-width="' + width + '" data-height="' + height + '" src="' + imgsrc + '" style="max-width:80px;" alt="">' +
                                    '<span class="upImg"><label class="fa fa-remove red removeBtn"></label></span></li>';
                                $('.pub-all-pic .add-more').before(html);
                                if (up_img == 8) {
                                    $("#browse_files_button_undefined").hide();
                                }
                            }

                            /*   image.onunload=function(){

                             }*/
                        })
                    });
                }
            }
        });
        // init
        uploader.init();
        func(uploader);

    };
    exports.uploadVideoImg = function (elem, opt, func, func2) {
        var unique = '';
        var type = opt.type; // img,media,file

        // choose type to upload
        var url = api[type]; //img,file,media

        if (url == undefined) {
            $(elem + ' .up_file_list').html('please choose type');
            return false
        }

        var typeName = {
            'video': '视频',
            'audio': '音频',
            'img': '图片',
            'file': '文件'
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
            multi_selection: false,
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
            multipart_params: {session: app.sess},
            flash_swf_url: '/data/Moxie.swf', // flash上传必要文件
            filters: {
                max_file_size: maxSize[type],
                min_file_size: minSize[type],
                mime_types: [
                    {title: "Files", extensions: ext[type]}
                ],
                prevent_duplicates: false // Do not let duplicates into the queue. Dispatches `plupload.FILE_DUPLICATE_ERROR`.
            },
            init: {
                PostInit: function () {
                    var initText = '请选择' + typeName[type];
                    if (option.multi_selection) initText = '按CTRL即可多选';
                    $(elem + ' .up_file_list').html('<span>' + initText + '</span>');
                },

                // 添加文件后触发
                FilesAdded: function (up, files) {
                    $(".percent").html("");

                    //  up.files=[up.files[up.files.length-1]];
                    // console.log(up.files);
                    if (up.files.length > 1) {
                        uploader.removeFile(up.files[0])
                    }
                    plupload.each(files, function (file) {
                        //截图
                        if (unique == 1) {
                            previewImage(file, function (imgsrc) {
                                var image = new Image();
                                image.src = imgsrc;
                                var width = image.width;//图片的宽度
                                var height = image.height;//图片的高度
                                $("#videoThumb").attr('src', imgsrc).attr('data-width', width).attr('data-height', height);
                            })
                        }
                        //视频
                        else {
                            $("#video").data('duration', 0);
                            var url = URL.createObjectURL(file.getNative());
                            $("#video").attr('src', url).unbind().on('canplaythrough', function (e) {
                                $("#video").data('duration', e.target.duration);
                                uploader.setOption({
                                    multipart_params: {
                                        duration: e.target.duration,
                                        app_uid: $(".user_item.checked").attr('data-id')
                                    }
                                })
                            });
                            $("#videoUrl").html(file.name);
                            $("#video_ID").val($(".videoSection").find('input[type="file"]').attr('id'));
                            //console.log(up);
                        }

                    });
                },
                // 百分比进度条
                UploadProgress: function (up, file) {
                    if (parseInt(file.percent) != 100) {
                        $(".percent").html("%" + file.percent);
                    } else {
                        $(".percent").html("【正在上传至OSS...】");
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
                        if (option.extra) res.data.extra = option.extra;
                        if (typeof func2 == 'function') func2(res.data);

                    } else {
                        tip.showTip("err", "上传失败", 1000);
                        //   console.log(res.error.msg + '【' + res.error.more + '】');
                        // $(elem + ' .err').html(res.error.msg + '【' + res.error.more + '】');
                    }
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
    }
    exports.uploadVideoImg2 = function (elem, opt, func, func2, func3) {
        var unique = '';

        var type = opt.type; // img,media,file

        // choose type to upload
        var url = api[type]; //img,file,media

        if (url == undefined) {
            $(elem + ' .up_file_list').html('please choose type');
            return false
        }

        var typeName = {
            'video': '视频'
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
            multipart_params: {session: app.sess},
            flash_swf_url: '/data/Moxie.swf', // flash上传必要文件
            filters: {
                max_file_size: maxSize[type],
                min_file_size: minSize[type],
                mime_types: [
                    {title: "Files", extensions: ext[type]}
                ],
                prevent_duplicates: false // Do not let duplicates into the queue. Dispatches `plupload.FILE_DUPLICATE_ERROR`.
            },
            init: {
                PostInit: function () {
                    var initText = '请选择' + typeName[type];
                    if (option.multi_selection) initText = '按CTRL即可多选';
                    $(elem + ' .up_file_list').html('<span>' + initText + '</span>');
                },

                // 添加文件后触发
                FilesAdded: function (up, files) {
                    $(".percent").html("");

                    //  up.files=[up.files[up.files.length-1]];
                    // console.log(up.files);
                    /*   if(up.files.length>1){
                     uploader.removeFile(up.files[0])
                     }*/
                    $("#videoUrl").html("");
                    plupload.each(files, function (file) {
                        //视频
                        $("#video").data('duration', 0);
                        var url = URL.createObjectURL(file.getNative());
                        $("#video").attr('src', url).unbind().on('canplaythrough', function (e) {
                            $("#video").data('duration', e.target.duration);
                            uploader.setOption({
                                multipart_params: {
                                    duration: e.target.duration
                                }
                            })
                        });
                        var html = $("#videoUrl").html();
                        $("#videoUrl").html((html ? (html + "," ) : '') + file.name);
                        //$("#video_ID").val($(".videoSection").find('input[type="file"]').attr('id'));
                        //console.log(up);


                    });
                },
                // 百分比进度条
                UploadProgress: function (up, file) {
                    if (parseInt(file.percent) != 100) {
                        $(".percent").html("上传" + file.name + ":%" + file.percent);
                    } else {
                        $(".percent").html("【正在上传" + file.name + "至OSS...】");
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
                        if (option.extra) res.data.extra = option.extra;
                        if (typeof func2 == 'function') func2(res.data);

                    } else {
                        tip.showTip("err", "上传失败", 1000);
                        //   console.log(res.error.msg + '【' + res.error.more + '】');
                        // $(elem + ' .err').html(res.error.msg + '【' + res.error.more + '】');
                    }
                },
                UploadComplete: function (up, files) {
                    func3();
                    console.log("complete");
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
    }

});
