define(function (require, exports) {
    var base = require('app/panel/panel.base.js?v=1.3');

    base.selectCheckbox();

    exports.Video = function (option) {
        var opt = {
            'url': '/api/show/video',
            "is_recommend":0
        };
        opt.callback = function () {
            $("[data-rel='tooltip']").tooltip();
        }
        $.extend(opt, option);

        var  page= base.pageList(opt);

        $(function () {

            var VideoJs = false;
            var myPlayer = false;
            $("#videoPlay").on("hide.bs.modal", function () {
                myPlayer.pause();
            });
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
            }).on('click', ".delBtn", function () {
                //屏蔽
                var id = $(this).data('id');
                base.requestApi('/api/show/del', {id: id}, function (res) {
                    if (res.result == 1) {
                        $(".item[data-id='" + id + "']").remove();
                        // window.location.reload();
                    }
                });
            }).on('click', ".recoveryBtn", function () {
                //回复正常
                var id = $(this).data('id');
                base.requestApi('/api/show/recovery', {id: id}, function (res) {
                    if (res.result == 1) {
                        $(".item[data-id='" + id + "']").remove();
                        // window.location.reload();
                    }
                });
            }).on('click',".recommendBtn",function () {
                //推荐
                var id = $(this).data('id');
                base.requestApi('/api/show/recommend', {id: id}, function (res) {
                    if (res.result == 1) {
                        $(".item[data-id='" + id + "']").remove();
                        //tip.showTip('ok','推荐成功',1000)
                    }
                });
            }).on('click',".unRecommendBtn",function () {
                //取消推荐
                var id = $(this).data('id');
                base.requestApi('/api/show/unRecommend', {id: id}, function (res) {
                    if (res.result == 1) {
                        $(".item[data-id='" + id + "']").remove();
                        //tip.showTip('ok','取消推荐成功',1000)
                    }
                });
            });


            $(".tabs .tab").on('click', function () {
                if ($(this).attr('data-key') == 'is_recommend') {
                    $(".btnBatchRemove").show();
                    $(".btnBatchRec").hide();
                } else {
                    $(".btnBatchRemove").hide();
                    $(".btnBatchRec").show();
                }
            });
            //批量屏蔽
            $(".btnBatchRemove").on('click', function () {
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
                base.requestApi('/api/show/removeBatch', {id: data}, function (res) {
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
            });
            //批量回复
            $(".btnBatchRec").on('click', function () {
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
                base.requestApi('/api/show/recBatch', {id: data}, function (res) {
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

            });


        })
    };

})