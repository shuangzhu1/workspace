define(function (require, exports) {
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
        'video': '1mb',
        'audio': '1mb'
    };

    require('tools/plupload/plupload.min'); //导入图片上传插件

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
                        var up_img = $(".uped").length;
                        if (up_img > 7) {
                            tip.showTip("err", "图片最多4张", 1000);
                            $("#browse_files_button_undefined").hide();
                            return false
                        }
                    });
                },

                // 百分比进度条
                UploadProgress: function (up, file) {
                    $('#' + file.id + ' b:first').html('<span>' + file.percent + '%</span>');
                },

                // 队列有改变时触发
                QueueChanged: function () {
                    //上传文件
                    if (option.auto_upload == true) {
                        uploader.start();
                    } else {
                        $(document).on('click', elem + ' .upload-button', function () {
                            uploader.start();
                        });
                    }
                },

                // 上传成功后触发
                FileUploaded: function (up, file, obj) {
                    var res = $.parseJSON(obj.response); //PHP上传成功后返回的参数
                    if (res.result == 1) {
                        if (option.extra) res.data.extra = option.extra;
                        if (typeof func == 'function') func(res.data);
                        console.log(res.data.url);

                        var html = '<b style="position: relative;display:inline-block;width: 82px;height: 82px;" class="wrap_b_uped">' +
                            '<div class="iss-img-close" style="cursor: pointer;display:none;position: absolute;width:82px;height:82px;margin:0;top:0;left:0;background:rgba(255,255,255,0.4);text-align: center;line-height: 80px;"><img style="display:inline-block;margin-top:30px;width: 20px;height: 20px;border: 0" src="/static/home/images/icon/111.png"/></div>' +
                            '<img style="margin-right:4px" class="uped" src="' + res.data.url + '"/></b>';
                        /*var html='<div style="position: relative">' +
                         '<div class="img-close" style="cursor: pointer;color: red;position: absolute;right: 7px;top: 0;">X</div>' +
                         '<img class="uped" src="'+res.data.url+'"/>' +
                         '</div>';*/

                        $('.pub-all-pic').append(html);

                    } else {
                        tip.showTip('err',   '【' + res.error.more + '】', 1000);
                    }
                },

                // 发生错误时触发
                Error: function (up, err) {
                    tip.showTip('err', err.message, 1000);
                }
            }
        });

        // init
        uploader.init();
    }

});
