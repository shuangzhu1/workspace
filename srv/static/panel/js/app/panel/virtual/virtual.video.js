define(function (require, exports) {
    var base = require('app/panel/panel.base.js?v=1.0');
    var uploader = require('app/panel/virtual/upload.js?v=1.1');
    var page = base.pageList({'url': '/api/video/virtualList'});
    base.selectCheckbox();
    //视频提交数据
    function submit(url) {
        // videoThumb = ($("#videoThumb").attr('src') + '?' + $("#videoThumb").data('width') + 'x' + $("#videoThumb").data('height'));
        var is_publish = 0;
        if ($("#videoModal #is_publish").prop('checked') == true) {
            is_publish = 1;
        }
        var app_uid = 0;

        if ($(".user_item.checked").length > 0) {
            app_uid = $(".user_item.checked").attr('data-id');//发布用户
        }

        var data = {
            url: url,
            app_uid: app_uid,
            is_publish: is_publish,
            title: $("#videoModal #title").val()
        };
        base.requestApi('/api/video/add', data, function (res) {
            if (res.result == 1) {
                /*   tip.showTip('ok', res.data, 2000, function () {
                 page()
                 });*/
            }
        });
    }

    exports.addVideo = function () {
        $(function () {
            var video_upload = '';
            uploader.uploadVideoImg2('.upload-widget[data-unique="2"]', {
                'type': 'video'
            }, function (res) {
                video_upload = res;
            }, submit, function () {
                page()
                $("#msgModal").modal('hide');
            });

            $(".user_item").on('click', function () {
                if ($(this).hasClass('checked')) {
                    $(this).removeClass("checked");
                } else {
                    $(this).addClass("checked");
                    $(this).siblings().removeClass("checked");
                }
            });

            //保存
            $("#sureBtn").on('click', function () {
                if ($("#videoUrl").html() == '') {
                    tip.showTip('err', '请选择视频', 1000);
                    return false
                }
                video_upload.start();
            });


            $(".list").on('click', '.publishBtn', function () {
                var id = $(this).attr('data-id');
                base.requestApi('/api/video/publish', {id: id}, function (res) {
                    if (res.result == 1) {
                        tip.showTip('ok', res.data, 2000, function () {
                            $(".item[data-id=" + id + "]").remove();
                            if ($(".item").length == 0) {
                                page();
                            }
                        });
                    }
                });
            }).on('click', '.btnBatch', function () {
                //批量发布视频
                var data = [];
                $(".item input[type='checkbox']").each(function () {
                    if ($(this).prop('checked') == true) {
                        data.push($(this).data('id'))
                    }
                });
                if (data.length == 0) {
                    tip.showTip('err', '请选择需要发布的视频', 1000);
                    return;
                }
                base.requestApi('/api/video/publishBatch', {id: data}, function (res) {
                    if (res.result == 1) {
                        tip.showTip('ok', res.data, 2000, function () {
                            for (var i in data) {
                                $(".item[data-id=" + data[i] + "]").remove();
                                if ($(".item").length == 0) {
                                    page();
                                }
                            }
                        });
                    }
                });
            }).on('click', '.btnSaveTitle', function () {
                var id = $(this).attr('data-id');
                var title = $(this).siblings(".title").val();
                base.requestApi('/api/video/setTitle', {id: id, title: title}, function (res) {
                    if (res.result == 1) {
                        tip.showTip('ok', "编辑成功", 2000, function () {

                        });
                    }
                });
            });


            var VideoJs = false;
            var myPlayer = false;
            $(".listData").on('click', '.btnScan', function () {
                var url = $(this).attr('data-url');

                url = url.split('?');
                if (VideoJs == false) {
                    VideoJs = videojs('my-video');
                    videojs("my-video").ready(function () {
                        myPlayer = this;
                        myPlayer.src(url[1]);
                        myPlayer.play();
                    });
                } else {
                    myPlayer.src(url[1]);
                    myPlayer.play();
                }
                $("#videoPlay").modal("show");
            });
            $("#videoPlay").on("hide.bs.modal", function () {
                myPlayer.pause();
            });
            $(".btnAdd").on('click', function () {
                $("#videoModal").modal("show");
                video_upload.refresh();
            })

        })
    };

})