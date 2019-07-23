define(function (require, exports) {
    var base = require("app/panel/panel.base");
    require("/static/panel/js/tools/Url.js");
    exports.apply = function () {
        //语音
        var voice_timer = false;
        var voice_play = 0;
        var voice_box = document.getElementById("voice_box");
        var voice_this = '';
        //播放结束
        voice_box.addEventListener('ended', function () {
            $(".voice.active").css({'width': '16px'}).removeClass("active");
            voice_timer && clearInterval(voice_timer);
        }, false);
        voice_box.addEventListener('playing', function () {
            var i = 1;
            voice_timer = setInterval(function () {
                if (i == 1) {
                    voice_this.css({'width': '6px'});
                } else if (i == 2) {
                    voice_this.css({'width': '9px'});
                } else {
                    voice_this.css({'width': '16px'});
                    i = 0;
                }
                i++;
            }, 300)
        }, false);
        voice_box.onerror = function (e) {
            console.log(voice_box.src);
            //1.用户终止 2.网络错误 3.解码错误 4.URL无效
            if (voice_box.src != window.location.href) {
                if (voice_box.error.code == 4) {
                    alert("资源不存在【" + voice_box.src + '】');
                }
            }
            //alert("Error! 出错了");
        };
        $(".listData").on('click', '.voice', function () {
            if ($(this).hasClass("active")) {
                $(this).removeClass("active");
                voice_play = 0;
                $(this).css({'width': '16px'});
                voice_timer && clearInterval(voice_timer);
                voice_box.pause();
            } else {
                var __this = $(this);
                voice_this = $(this);
                $(".voice.active").css({'width': '16px'}).removeClass("active");
                $(this).addClass('active');
                voice_timer && clearInterval(voice_timer);

                voice_play = $(this).data('id');

                voice_box.src = __this.data('src');
                voice_box.play();
            }
        });

        //审核通过
        $(" .listData").on('click', '.checkBtn', function (e) {
            // params
            var id = $(this).attr('data-id');
            // confirm
            $(this).confirm("你确定审核通过吗?", {
                ok: function () {
                    base.requestApi('/api/rent/applyCheckSuccess', {id: id}, function (res) {
                        if (res.result == 1) {
                            tip.showTip('ok', '操作成功！', 3000, function () {
                                window.location.reload();
                            });
                            //
                        }
                    });
                },
                cancel: function () {
                    return false;
                }
            });

            // api request

            e.stopImmediatePropagation();
        });
        //审核不通过
        $(" .listData").on('click', '.failBtn', function (e) {
            // params
            var id = $(this).attr('data-id');

            $("#apply_id").val(id);
            $('#checkModal').modal('show');
            // api request
            e.stopImmediatePropagation();
        });
        $("#checkModal #sureBtn").on('click', function () {
            var reason = $("#reason").val();
            if (!reason) {
                base.showTip('err', '请输入审核失败原因', 1000);
                return false;
            }
            base.requestApi('/api/rent/applyCheckFail', {
                id: $("#apply_id").val(),
                reason: reason,
                type: 'check'
            }, function (res) {
                if (res.result == 1) {
                    tip.showTip('ok', '操作成功！', 3000, function () {
                        $('#checkModal').modal('hide');
                        window.location.reload();
                    });

                }
            });
        });

        $(".btnSearch").on('click', function () {
            var opt = {page: 1};
            $.extend(opt, $("form").serializeObject());
            var url = new Url();
            url.setArgs(opt);
            window.location.href = url.getUrl();
        })
    }
    exports.lngLat = function () {
        $(function () {
            // 百度地图API功能
            var map = new BMap.Map("mapWrap");
            map.enableScrollWheelZoom();
            var point = new BMap.Point(113.961974, 22.547832);
            var marker = new BMap.Marker(point);
            marker.enableDragging();
            map.addOverlay(marker);
            map.centerAndZoom(point, 15);
            map.panBy(500, 250);
            function changeLngLat(lng, lat) {
                if (!isNaN(lat) && !isNaN(lng) && lat > 0 && lng > 0) {
                    map.clearOverlays();
                    point = new BMap.Point(lng, lat);
                    marker = new BMap.Marker(point);
                    // marker.setPosition(point);
                    map.centerAndZoom(point, 15);
                    map.addOverlay(marker);     // 将标注添加到地图中
                    map.panBy(400, 250);
                }
            }

            $(".lngLat").on('click', function () {
                $("#addressModal").modal("show");
                changeLngLat($(this).data('lng'), $(this).data('lat'));
            })
        })

    }
})
;