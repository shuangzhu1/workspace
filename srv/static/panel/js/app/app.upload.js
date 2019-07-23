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
            var str = '<a class="browse-button btn btn-success" href="javascript:;">选择' + typeName[type] + '</a><a class="upload-btn btn btn-warning" href="javascript:;">上传</a><em class="drop_element"></em>' +
                '<span class="up_file_list"></span><span class="err"></span>';
            $(this).html(str);
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
        multipart_params = {};
        if (option.multipart_params !== undefined) {
            multipart_params = option.multipart_params;
        }
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
            multipart_params: multipart_params,
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
                        $(elem + ' .up_file_list').append('<div id="' + file.id + '" class="queue-file">' + file.name + ' <b></b></div>');
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
                        $(document).on('click', elem + ' .upload-btn', function () {
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
                    } else {
                        $(elem + ' .err').html(res.error.msg + '【' + res.error.more + '】');
                    }
                },

                // 发生错误时触发
                Error: function (up, err) {
                    $(elem + ' .err').html(err.message);
                }
            }
        });

        // init
        uploader.init();

        /**
         @class plupload Uploader settings
         @constructor

         @param {Object} settings For detailed information about each option check documentation.
         @param {String|DOMElement} settings.browse_button id of the DOM element or DOM element itself to use as file dialog trigger.
         @param {String} settings.url URL of the server-side upload handler.
         @param {Number|String} [settings.chunk_size=0] Chunk size in bytes to slice the file into. Shorcuts with b, kb, mb, gb, tb suffixes also supported. `e.g. 204800 or "204800b" or "200kb"`. By default - disabled.
         @param {String} [settings.container] id of the DOM element to use as a container for uploader structures. Defaults to document.body.
         @param {String|DOMElement} [settings.drop_element] id of the DOM element or DOM element itself to use as a drop zone for Drag-n-Drop.
         @param {String} [settings.file_data_name="file"] Name for the file field in Multipart formated message.
         @param {Object} [settings.filters={}] Set of file type filters.
         @param {Array} [settings.filters.mime_types=[]] List of file types to accept, each one defined by title and list of extensions. `e.g. {title : "Image files", extensions : "jpg,jpeg,gif,png"}`. Dispatches `plupload.FILE_EXTENSION_ERROR`
         @param {String|Number} [settings.filters.max_file_size=0] Maximum file size that the user can pick, in bytes. Optionally supports b, kb, mb, gb, tb suffixes. `e.g. "10mb" or "1gb"`. By default - not set. Dispatches `plupload.FILE_SIZE_ERROR`.
         @param {String|Number} [settings.filters.min_file_size=0] Maximum file size that the user can pick, in bytes. Optionally supports b, kb, mb, gb, tb suffixes. `e.g. "10mb" or "1gb"`. By default - not set. Dispatches `plupload.FILE_SIZE_ERROR`.
         @param {Boolean} [settings.filters.prevent_duplicates=false] Do not let duplicates into the queue. Dispatches `plupload.FILE_DUPLICATE_ERROR`.
         @param {String} [settings.flash_swf_url] URL of the Flash swf.
         @param {Object} [settings.headers] Custom headers to send with the upload. Hash of name/value pairs.
         @param {Number} [settings.max_retries=0] How many times to retry the chunk or file, before triggering Error event.
         @param {Boolean} [settings.multipart=true] Whether to send file and additional parameters as Multipart formated message.
         @param {Object} [settings.multipart_params] Hash of key/value pairs to send with every file upload.
         @param {Boolean} [settings.multi_selection=true] Enable ability to select multiple files at once in file dialog.
         @param {String|Object} [settings.required_features] Either comma-separated list or hash of required features that chosen runtime should absolutely possess.
         @param {Object} [settings.resize] Enable resizng of images on client-side. Applies to `image/jpeg` and `image/png` only. `e.g. {width : 200, height : 200, quality : 90, crop: true}`
         @param {Number} [settings.resize.width] If image is bigger, it will be resized.
         @param {Number} [settings.resize.height] If image is bigger, it will be resized.
         @param {Number} [settings.resize.quality=90] Compression quality for jpegs (1-100).
         @param {Boolean} [settings.resize.crop=false] Whether to crop images to exact dimensions. By default they will be resized proportionally.
         @param {String} [settings.runtimes="html5,flash,silverlight,html4"] Comma separated list of runtimes, that Plupload will try in turn, moving to the next if previous fails.
         @param {String} [settings.silverlight_xap_url] URL of the Silverlight xap.
         @param {Boolean} [settings.unique_names=false] If true will generate unique filenames for uploaded files.
         */

    }

});
