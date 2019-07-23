/**
 * Created by ykuang on 2017/10/12.
 */
define(function (require, exports) {
    var storage = require('app/panel/panel.storage.js?v=1.1');//storage
    var base = require('app/panel/panel.base');//公共函数

    exports.cut = function (music_url, music) {
        var request = new XMLHttpRequest();
        request.open('GET', "/api/music/getBuffer?url=" + base64.encode(music_url), true);
        request.responseType = 'arraybuffer';

        var wavesurfer = null; //波纹初始化
        var duration = 0; //音频时长
        var limit_start_audio = 0;//限制音频的播放区间起始
        var limit_end_audio = 0;//限制音频的播放区间结束
        var playing = false;//是否正在播放
        var skipLength = 2;//跳动的步伐(秒)
        var px_percent = 0;//每个像素代表的时间

        var limit_time = 20;//最小为20秒区间
        var max_time = 60;//最大为60秒区间
        var limit_px = 0;//最小的区域像素
        var max_px = 0;//最大的区域像素

        var play_box_width = parseFloat($(".play_right").width()); //整个波型播放区域的宽度

        request.onprogress = function (e) {
            if (e.lengthComputable) {
                var percent = ((e.loaded / e.total) * 100);
                /*  console.log(percent);
                 if (percent >= 100) {
                 $(".loading").remove();
                 }*/
                $(".loading_percent").css({'width': percent + '%'})

            }

        };
        //下面就是对音频文件的异步解析
        request.onload = function () {
            console.log(request.response.byteLength);
            if (request.response.byteLength <= 10000) {
                tip.showTip("err", "资源不可用", 1000);
                $("#waveform").html("<p class='red' style='line-height: 80px;font-size: 15px;bold;padding: 10px'><i class='fa fa-chain-broken'></i>资源不可用</p>");
                return;
            }
            wavesurfer = WaveSurfer.create({
                container: '#waveform',
                height: 130,
                progressColor: '#5e7280',
                cursorWidth: 2,
                cursorColor: "#2c6aa0",
                dragSelection: false,
                updateTime: function (w) {
                    $(".duration_top").css({left: w - 31 + 'px'});
                }
                /* scrollParent: true*/
            });
            wavesurfer.loadArrayBuffer(request.response);
            wavesurfer.on('ready', function () {
                $(".loading").remove();
                // wavesurfer.seekTo(0.3)
                //  wavesurfer.play();
                duration = wavesurfer.getDuration();
                px_percent = duration / parseFloat($(".play_right").width());
                limit_px = limit_time / px_percent;
                max_px = max_time / px_percent;
                $("#shadow").css({'right': play_box_width - limit_time / px_percent}).show();
                $(".duration_top").show();
                $(".btnWrap").show();
                $(".play_btn_wrap").show();

            });

            wavesurfer.on('audioprocess', function (t) {
                audioprocess(t)
            });
            wavesurfer.on('loading', function (t) {
                $(".loading_percent").css({'width': t + '%'})
            });
            wavesurfer.on('finish', function (t) {
                playing = false;
            });
            wavesurfer.on('pause', function (t) {
                playing = false;
            })
        };
        request.send();
        //顶部时间条
        function set_percent(t) {
            $(".duration_time").attr('data-time', t);
            // $(".duration_top").css({left: Math.floor(-30 + (t / duration) * play_box_width) + 'px'});
            t = t.toString();
            var t_arr = t.split('.');

            var t_left = parseInt(t_arr[0]);//秒部分
            var t_right = t_arr.length > 1 ? t_arr[1].substr(0, 1) : 0; //毫秒部分
            //console.log(t_left);
            //console.log(t_right);

            var m = '00';
            var ms = "0";
            if (t_left >= 60) {
                m = '0' + parseInt(t_left / 60);
            }
            var s = Math.ceil(t_left % 60);
            s = s < 10 ? '0' + s : s;

            $(".duration_s").html(m + ':' + s + '.');
            $(".duration_ms").html(t_right);
        }

        //进度条
        function audioprocess(t) {
            if (limit_start_audio == limit_end_audio || (t >= limit_start_audio && t <= limit_end_audio)) {
            } else {
                wavesurfer.stop();
                playing = false;
                $("#playBtn").show();
                $("#pauseBtn").hide();

                if (t >= limit_end_audio) {
                    wavesurfer.seekTo(limit_start_audio / duration);
                    t = limit_start_audio;
                }
            }
            set_percent(t);
        }

        //开始播放
        function play() {
            wavesurfer.play();
            playing = true;
            $("#playBtn").hide();
            $("#pauseBtn").show();
        }

        //播放 暂停 前进 后退
        $(document).on('click', '#playBtn', function () {
            wavesurfer.play();
            playing = true;
            $("#pauseBtn").show();
            $(this).hide();
        }).on('click', '#pauseBtn', function () {
            wavesurfer.pause();
            playing = false;
            $("#playBtn").show();
            $(this).hide();
        }).on('click', '#btnForward', function () {
            if (limit_start_audio != limit_end_audio) {
                if (wavesurfer.getCurrentTime() + skipLength > limit_end_audio) {
                    wavesurfer.seekTo(limit_end_audio / duration);
                    set_percent(limit_end_audio);
                } else {
                    wavesurfer.seekTo((wavesurfer.getCurrentTime() + skipLength) / duration);
                    set_percent(wavesurfer.getCurrentTime() + skipLength);
                }
            } else {
                if (wavesurfer.getCurrentTime() + skipLength > duration) {
                    wavesurfer.seekTo(1);
                    set_percent(duration);
                } else {
                    wavesurfer.seekTo((wavesurfer.getCurrentTime() + skipLength) / duration);
                    set_percent(wavesurfer.getCurrentTime() + skipLength);
                }
            }
        }).on('click', '#btnBackward', function () {
            if (limit_start_audio != limit_end_audio) {
                if (wavesurfer.getCurrentTime() - skipLength > limit_start_audio) {
                    wavesurfer.seekTo(limit_start_audio / duration);
                    set_percent(limit_start_audio);
                } else {
                    wavesurfer.seekTo((limit_start_audio) / duration);
                    set_percent(limit_start_audio);
                }
            } else {
                if (wavesurfer.getCurrentTime() < skipLength) {
                    wavesurfer.seekTo(0);
                    set_percent(0);
                } else {
                    wavesurfer.seekTo((wavesurfer.getCurrentTime() - skipLength) / duration);
                    set_percent(wavesurfer.getCurrentTime() - skipLength);
                }
            }
        });

        //剪切
        $("#btnCut").on('click', function () {
            var start = px_percent * parseFloat($("#shadow").css('left'));
            var time = px_percent * parseFloat($("#shadow").width());
            base.requestApi('/api/music/cut', {start: start, t: time, item: music}, function (res) {
                if (res.result == 1) {
                    base.showTip("ok", "剪切成功", 1000, function () {
                        $(".mp3Url").html(res.data);
                        $("#addModal").modal("show");
                    });

                    // base.showTip("ok", "添加成功", 1000)
                }
            });
        });


        var tag_left = false;//左边拖动条是否处于激活状态
        var ox_left = 0; //左边拖动条激活状态下 鼠标上次离左边的距离
        var tag_right = false;////右边拖动条是否处于激活状态
        var ox_right = 0;//右边拖动条激活状态下 鼠标上次离左边的距离

        var tag_shadow = false;//区块被选中
        var ox_shadow = 0;//区块激活状态下 鼠标上次离左边的距离

        $('#cut_left_drag').mousedown(function (e) {
            ox_left = e.pageX;
            tag_left = true;
            e.stopPropagation();
            e.preventDefault()
        });
        $('#cut_right_drag').mousedown(function (e) {
            ox_right = e.pageX;
            tag_right = true;
            e.stopPropagation();
            e.preventDefault()
        });
        $('#shadow').mousedown(function (e) {
            ox_shadow = e.pageX;
            tag_shadow = true;
            e.stopPropagation();
            e.preventDefault()
        });

        $(document).mousemove(function (e) {//鼠标移动
            if (tag_left) {
                parent_left = parseFloat($("#shadow").css('left')); //选择区域离左边的距离
                parent_width = parseFloat($("#shadow").width()); //拖动块的宽度
                if (parent_left >= 0) {
                    var left = e.pageX - ox_left; //离上次移动的位置
                    ox_left = e.pageX;
                    if (left != 0) {
                        //往左移动
                        if (left < 0) {
                            //离开了最左边界
                            if (left + parent_left < 0) {
                                $("#shadow").css('left', 0);
                                $(this).css({'left': '-8px'});
                                left = -parent_left;

                                wavesurfer.seekTo(0);
                                limit_start_audio = 0;
                            } else {

                                //超出了最大区域限制
                                if (parent_width - left >= max_px) {
                                    parent_left = parent_left - (max_px - parent_width);
                                    $("#shadow").css('left', parent_left);
                                    var percent = parent_left / play_box_width;
                                    wavesurfer.seekTo(percent);
                                    limit_start_audio = percent * duration;

                                } else {
                                    $("#shadow").css('left', parent_left + left);
                                    var percent = (parent_left + left) / play_box_width;
                                    wavesurfer.seekTo(percent);
                                    limit_start_audio = percent * duration;
                                }


                            }
                        }
                        //往右移动
                        else {
                            //离右边边界超过了一定范围
                            if (parent_width - left < limit_px) {
                                left = parent_width - limit_px;
                                left = left > 0 ? left : 0
                            }
                            $("#shadow").css('left', parent_left + left);
                            var percent = (parent_left + left) / play_box_width;
                            wavesurfer.seekTo(percent);
                            limit_start_audio = percent * duration;
                        }
                        limit_end_audio = ((parseFloat($("#shadow").css('left')) + parseFloat($("#shadow").width())) / play_box_width ) * duration;
                        console.log(parseFloat($("#shadow").width()) * px_percent);
                        $("#shadow .cut_time").html((parseFloat($("#shadow").width()) * px_percent).toFixed(0) + ".00");
                        if (!playing) {
                            set_percent(limit_start_audio);
                            play();
                        }
                    }

                }
            }
            else if (tag_right) {
                limit_end_audio = true;
                var parent_right = parseFloat($("#shadow").css('right')); //拖动块离右边的距离
                var parent_left = parseFloat($("#shadow").css('left'));//拖动块离左边的距离
                var parent_width = parseFloat($("#shadow").width());//拖动块的宽度

                if (parent_right >= 0) {
                    var left = e.pageX - ox_right; //离上次移动的位置
                    ox_right = e.pageX;
                    if (left != 0) {
                        //往左移动
                        if (left < 0) {
                            //离开了最左边界
                            if (parent_width + left < limit_px) {
                                $("#shadow").css('right', play_box_width - parent_left - limit_px);
                                $(this).css({'right': '-8px'});
                                var percent = (parent_left + limit_px) / play_box_width;
                                wavesurfer.seekTo(percent - 0.01);
                                limit_end_audio = percent * duration;

                            } else {
                                $("#shadow").css('right', parent_right - left);
                                var percent = (play_box_width - (parent_right - left)) / play_box_width;
                                wavesurfer.seekTo(percent - 0.01);
                                limit_end_audio = percent * duration;
                            }
                        }
                        //往右移动
                        else {
                            //离右边边界超过了一定范围
                            if (parent_right - left < 0) {
                                $("#shadow").css('right', 0);
                                $(this).css({'right': '-8px'});
                                wavesurfer.seekTo(0.99);
                                limit_end_audio = duration;
                            } else {
                                //超出了最大区域限制
                                if (parent_width + left >= max_px) {
                                    parent_right = parent_right - (max_px - parent_width);
                                    $("#shadow").css('right', parent_right);
                                    var percent = (play_box_width - parent_right) / play_box_width;
                                    wavesurfer.seekTo(percent - 0.01);
                                    limit_end_audio = percent * duration;
                                } else {
                                    $("#shadow").css('right', parent_right - left);
                                    var percent = (play_box_width - (parent_right - left)) / play_box_width;
                                    wavesurfer.seekTo(percent - 0.01);
                                    limit_end_audio = percent * duration;
                                }


                            }
                        }
                        limit_start_audio = ((parseFloat($("#shadow").css('left'))) / play_box_width ) * duration;
                        $("#shadow .cut_time").html((parseFloat($("#shadow").width()) * px_percent).toFixed(0) + ".00");
                        if (!playing) {
                            set_percent(limit_end_audio);
                            play();
                        }
                    }

                }
            }
            else if (tag_shadow) {
                limit_end_audio = true;
                var shadow_right = parseFloat($("#shadow").css('right')); //拖动块离右边的距离
                var shadow_left = parseFloat($("#shadow").css('left'));//拖动块离左边的距离
                var shadow_width = parseFloat($("#shadow").width());//拖动块的宽度

                if (shadow_left >= 0 && shadow_right >= 0) {
                    var left = e.pageX - ox_shadow; //离上次移动的位置
                    ox_shadow = e.pageX;
                    if (left != 0) {
                        //往左移动
                        if (left < 0) {
                            //离开了最左边界
                            if (shadow_left + left <= 0) {
                                $("#shadow").css({'left': 0, 'right': shadow_right + shadow_left});
                                wavesurfer.seekTo(0);
                                var percent = shadow_width / play_box_width;
                                limit_end_audio = percent * duration;

                            } else {
                                $("#shadow").css({'left': shadow_left + left, 'right': shadow_right - left});
                                var percent = (shadow_left + left) / play_box_width;
                                wavesurfer.seekTo(percent);
                                limit_end_audio = ((shadow_left + left + shadow_width) / play_box_width) * duration;
                            }
                        }
                        //往右移动
                        else {
                            // console.log(shadow_right)
                            //  console.log(left);
                            //离右边边界超过了一定范围
                            if (shadow_right - left <= 0) {
                                $("#shadow").css({'right': 0, 'left': shadow_left + shadow_right});
                                wavesurfer.seekTo(0.99);
                                limit_end_audio = duration;
                            } else {
                                $("#shadow").css({'right': shadow_right - left, 'left': shadow_left + left});
                                var percent = (shadow_left + left + shadow_width) / play_box_width;
                                wavesurfer.seekTo(percent - 0.01);
                                limit_end_audio = ((shadow_left + left + shadow_width) / play_box_width) * duration;
                            }
                        }
                        limit_start_audio = ((parseFloat($("#shadow").css('left'))) / play_box_width ) * duration;
                        if (!playing) {
                            set_percent(limit_end_audio);
                            play();
                        }
                    }
                }
            }
            e.stopPropagation();
            e.preventDefault()
        }).mouseup(function (e) {
            tag_left = false;
            tag_right = false;
            tag_shadow = false;
            e.stopPropagation();
            e.preventDefault()
        });

        /**##########音乐试听start#######**/

        var modal = $("#addModal");
        // modal.modal("show");

        storage.getImg('#upThumb', function (res) {
            $('#thumb').val(res.url);
            $('.preview-thumb').attr('src', res.url);
        }, false, {multipart_params: {img_type: 'music'}});

        var listening = false;//正在音乐试听
        var voice_box = document.getElementById("voice_box");//音乐试听
        //播放
        $(document).on('click', '.listenBtn', function () {
            listen_play($(this).siblings(".mp3Url").html())
        });

        voice_box.addEventListener('playing', function (e) {

        }, false);
        voice_box.addEventListener('timeupdate', function (e) {
            currentTime = Math.ceil(this.currentTime);
            if (currentTime > 60) {
                m = Math.ceil(currentTime / 60);
                m = m < 10 ? ('0' + m) : m;
                s = currentTime % 60;
            } else {
                m = '00';
                s = currentTime;
            }
            $(".playing .time").html(m + ':' + (s < 10 ? '0' + s : s));
        }, false);
        //播放结束
        voice_box.addEventListener('ended', function () {
            listening = false;
            listen_pause();
        }, false);
        function listen_play(src) {
            voice_box.src = src;
            voice_box.play();
            $(".playing .playBtn").attr({'data-src': voice_box.src}).hide();
            $(".playing .pauseBtn").show();
            $(".playing .name").html(modal.find("#name").val());
            $(".playing .thumb img").attr('src', modal.find("#thumb").val());
            $(".playing .thumb img").addClass('rounding');
            if (!($(".play").is(":visible"))) {
                $(".playing").show()
            }

            listening = true;
        }

        function listen_pause() {
            voice_box.pause();
            $(".playing .playBtn").show();
            $(".playing .pauseBtn").hide();
            $(".playing .thumb img").removeClass('rounding');
            listening = false;
        }

        //右侧播放
        $(document).on('click', '.playBtn', function () {
            listen_play($(this).attr('data-src'))
        });
        //右侧暂停
        $(document).on('click', '.pauseBtn', function () {
            listen_pause()
        });
        $(".playing").hover(function (e) {
            $(this).animate({'right': '0px'}, 500, '', function () {
            });
            //e.stopPropagation();
        }, function (e) {
            $(this).animate({'right': '-300px'}, 500, '', function () {
            });
            e.stopPropagation();
        })

        /**##########音乐试听end#######**/
    };
    exports.add = function () {
        var modal = $("#addModal");
        //确定
        modal.find("#sureBtn").on('click', function () {
            var cat_id = '';//modal.find(".cat").val();//分类id
            var name = $.trim(modal.find("#name").val());//歌曲名称
            var singer = $.trim(modal.find("#singer").val());//歌手
            var album = $.trim(modal.find("#album").val());//专辑名称
            var thumb = $.trim(modal.find("#thumb").val());//歌曲封面图
            var song_id = modal.find("#song_id").val();//歌曲ID

            modal.find(".cat").each(function () {
                if ($(this).prop('checked') == true) {
                    cat_id += "," + $(this).data('id');
                }
            });
            if (cat_id == '') {
                modal.find(".error-widget .error_msg").html("请选择歌曲分类");
                modal.find(".error-widget").show();
                return false;
            }
            cat_id = cat_id.substr(1);
            /* if (!cat_id) {
             modal.find(".error-widget .error_msg").html("请选择歌曲分类");
             modal.find(".error-widget").show();
             modal.find(".cat").focus();
             return false;
             }*/
            if (name == '') {
                modal.find(".error-widget .error_msg").html("请输入歌曲名称");
                modal.find(".error-widget").show();
                modal.find("#name").focus();
                return false;
            }
            if (singer == '') {
                modal.find(".error-widget .error_msg").html("请输入歌手姓名");
                modal.find(".error-widget").show();
                modal.find("#singer").focus();
                return false;
            }
            if (thumb == '') {
                modal.find(".error-widget .error_msg").html("请上传歌曲封面");
                modal.find(".error-widget").show();
                return false;
            }
            if (modal.find("#fileUrl").html() == '') {
                modal.find(".error-widget .error_msg").html("请选择音乐文件");
                modal.find(".error-widget").show();
                return false;
            }
            submit(modal.find(".mp3Url").html());
        });

        //提交数据
        function submit(url) {
            var cat_id = '';//modal.find(".cat").val();//分类id
            var name = $.trim(modal.find("#name").val());//歌曲名称
            var singer = $.trim(modal.find("#singer").val());//歌手
            var album = $.trim(modal.find("#album").val());//专辑名称
            var thumb = $.trim(modal.find("#thumb").val());//歌曲封面图
            var duration = $("#audio").attr('data-duration');//歌曲长度
            var song_id = modal.find("#song_id").val();
            var sort_num = $.trim(modal.find("#sort_num").val());//排序
            var enable = 1;//是否可用
            var is_hot = 0;//是否热门
            if (modal.find("#enable").prop('checked')) {
                enable = 0;
            }
            if (modal.find("#is_hot").prop('checked')) {
                is_hot = 1;
            }
            modal.find(".cat").each(function () {
                if ($(this).prop('checked') == true) {
                    cat_id += "," + $(this).data('id');
                }
            });
            cat_id = cat_id.substr(1);
            duration = url.split('_t_')[1];
            duration = duration.split('.')[0];
            $("#msgModal").modal('hide');
            var data = {
                song_id: song_id,
                cat_id: cat_id,
                name: name,
                singer: singer,
                album: album,
                thumb: thumb,
                mp3: url,
                duration: duration,
                sort_num: sort_num,
                enable: enable,
                is_hot: is_hot
            };
            base.requestApi('/api/music/addMusic', {data: data}, function (res) {
                if (res.result == 1) {
                    base.showTip('ok', "添加成功", 1000, function () {
                        modal.modal("hide");
                    });
                    modal.find(".success-widget").show();
                    modal.find(".success-widget .success_msg").html(res.data);
                    modal.find(".error-widget").hide();
                } else {
                    modal.find(".success-widget").hide();
                    modal.find(".error-widget .error_msg").html(res.error.msg);
                    modal.find(".error-widget").show();
                }
            }, false, true);
        }

    }

});