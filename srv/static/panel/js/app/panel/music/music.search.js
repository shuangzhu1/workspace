/**
 * Created by ykuang on 2017/9/26.
 */
define(function (require, exports) {
    var base = require('app/panel/panel.base.js?v=1.3');//公共函数
    require('tools/plupload/plupload.full.min.js');//上传组件
    var storage = require('app/panel/panel.storage.js?v=1.1');//storage
    var search = require('app/app.search');

    var uploader = '';
    exports.searchOnline = function () {
        exports.getPageList({'url': '/api/music/search', 'limit': 20});

        //语音
        var voice_box = document.getElementById("voice_box");

        var voice_this = '';//当前正在播放的音乐
        var voice_latest = '';//最近播放过的音乐


        //右侧浮动播放窗
        var lyric = [];//当前歌曲歌词;
        var lyric_offset = 0;//当前歌词的偏移下标
        var lyric_offset_px = 100;//当前歌词的偏移


        var lyric_ele = $(".lyric"); //歌词容器
        var play_box = $(".playing");//浮动播放窗

        voice_box.addEventListener('playing', function (e) {

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
        //进度条
        voice_box.addEventListener('timeupdate', function () {
            //console.log(this.currentTime);
            if (lyric) {
                var target = lyric_ele.find(" li[data-id='" + lyric_offset + "']");
                var time_offset = lyric[lyric_offset] - (this.currentTime).toFixed(2);//时间差

                if (time_offset < 0.5) {
                    if (!target.hasClass('current')) {
                        target.siblings().removeClass('current');
                        target.addClass("current");
                        lyric_offset += 1;
                        //  console.log(target.height());
                        // console.log(lyric_offset_px);
                        lyric_ele.animate({'scrollTop': lyric_offset_px}, 100);
                        lyric_offset_px = lyric_offset_px + target.height();


                    }
                }
            }
            // var percent = ((this.currentTime / this.duration) * 100).toFixed(1);
            var currentTime = Math.ceil(this.currentTime);

            var m, s = 0;
            if (currentTime > 60) {
                m = Math.ceil(currentTime / 60);
                m = m < 10 ? ('0' + m) : m;
                s = currentTime % 60;
            } else {
                m = '00';
                s = currentTime;
            }
            $(".music_" + voice_this).find('.time_percent').html(m + ':' + (s < 10 ? '0' + s : s))

        });
        //播放结束
        voice_box.addEventListener('ended', function () {
            $(".music_" + voice_this).find('.btnPlay').show();
            $(".music_" + voice_this).find('.btnPause').hide();
            play_box.find(".thumb img").removeClass('rounding');
            voice_this = '';
            if (voice_latest) {
                $(".music_" + voice_latest).next().find(".btnPlay").click();
            }
        }, false);
        //播放
        $(document).on('click', '.btnPlay', function () {
            play($(this).attr('data-song'), $(this).attr('data-src'), 0)
        });
        function play(song, src, float) {

            //有音乐在播放
            if (voice_this) {
                var old = $(".music_" + voice_this);
                old.find('.btnPlay').show();
                old.find('.btnPause').hide();
                old.find('.time_percent').html('')
                //当前歌曲
                if (voice_this == song) {
                    voice_box.play();
                    return
                }
            }
            var current = $(".music_" + song);


            lyric_offset = 0;//初始化歌词偏移量
            lyric_offset_px = 0;//初始化歌词偏移量
            lyric = [];//初始化歌词
            lyric_ele.scrollTop(0);//初始化位置


            var song_id = current.attr('data-mid');
            song_id = song_id != '' ? song_id : song;
            base.requestApi('/api/music/getLyric', {
                song_id: song_id,
                platform: $(".tabs .tab.active").data('val')
            }, function (res) {
                if (res.result == 1) {
                    var html = '<li class="take_place"></li>';
                    if (res.data.lyric.length > 0) {
                        if (Object.keys(res.data.trans).length > 0) {
                            $.each(res.data.lyric, function (key, item) {
                                console.log(res.data.trans[item.time]);
                                //  console.log(key)
                                // console.log(item);
                                if (res.data.trans[item.time] !== undefined) {
                                    html += '<li data-id="' + key + '"><p>' + (item.word ? item.word : '。。。。。。。') + '</p>' +
                                        '<p>' + (res.data.trans[item.time]['word']) + '</p>' +
                                        '</li>';
                                } else {
                                    html += '<li data-id="' + key + '"><p>' + (item.word ? item.word : '。。。。。。。') + '</p>' +
                                        '</li>';
                                }

                                var time = item.time;
                                time = time.split(':');
                                var m = parseInt(time[0]);
                                var s = parseFloat(time[1]);

                                lyric[key] = m * 60 + s;
                                //
                            });
                        } else {
                            $.each(res.data.lyric, function (key, item) {
                                //  console.log(key)
                                // console.log(item);
                                html += '<li data-id="' + key + '">' + (item.word ? item.word : '。。。。。。。') + '</li>';
                                var time = item.time;
                                time = time.split(':');
                                var m = parseInt(time[0]);
                                var s = parseFloat(time[1]);

                                lyric[key] = m * 60 + s;
                                //
                            });
                        }

                    } else {
                        html += '<li class=""><img src="/static/panel/images/admin/no_data.png"  style="background-color: #2D3C4F;margin-left: 50px" />暂无歌词</li>';
                    }
                    lyric_ele.html(html);//歌词填充

                    voice_this = song;
                    voice_latest = voice_this;
                    voice_box.src = res.data.url;//current.find('.btnPlay').attr('data-src');
                    voice_box.play();
                    current.find(".btnPlay").hide();
                    current.find(".btnPause").show();
                    $(".playing_cover").css({'background-image': 'url(' + current.find('.btnPlay').attr('data-thumb') + ')'});
                    play_box.find(".playBtn").attr({'data-song': song, 'data-src': voice_box.src}).hide();
                    play_box.find(".pauseBtn").show();
                    play_box.find(".name").html(current.find('.btnPlay').attr('data-name'));
                    play_box.find(".thumb img").attr('src', current.find('.btnPlay').attr('data-thumb'));
                    play_box.find(".thumb img").addClass('rounding');


                    //右侧悬浮窗
                    if (float == 1) {

                    } else {
                        if (!(play_box.is(":visible"))) {
                            play_box.show()
                        }

                        //  spread(1)

                    }
                }
            }, false, true);


        }


        function pause() {
            voice_box.pause();
            var current = $(".music_" + voice_this);
            current.find(".btnPause").hide();
            current.find(".btnPlay").show();
            play_box.find(".playBtn").show();
            play_box.find(".pauseBtn").hide();
            play_box.find(".thumb img").removeClass('rounding');
        }

        //暂停
        $(document).on('click', '.btnPause', function (e) {
            pause();
            e.stopPropagation();
            e.preventDefault();
        });
        //右侧播放
        $(".playBtn").on('click', '', function (e) {
            play($(this).attr('data-song'), $(this).attr('data-src'), 1);
            e.stopPropagation();
            e.preventDefault();
        }).on('mousedown', function (e) {
            e.stopPropagation();
            e.preventDefault();
        });
        //右侧上一首
        $(".backwardBtn").on('click', function (e) {
            if (voice_latest) {
                $(".music_" + voice_latest).prev().find(".btnPlay").click();
            }
            e.stopPropagation();
            e.preventDefault();
        }).on('mousedown', function (e) {
            e.stopPropagation();
            e.preventDefault();
        });
        //右侧下一首
        $(".forwardBtn").on('click', function (e) {
            if (voice_latest) {
                $(".music_" + voice_latest).next().find(".btnPlay").click();
            }
            e.stopPropagation();
            e.preventDefault();
        }).on('mousedown', function (e) {
            e.stopPropagation();
            e.preventDefault();
        });
        //右侧暂停
        $(".pauseBtn").on('click', function (e) {
            pause();
            e.stopPropagation();
            e.preventDefault();
        }).on('mousedown', function (e) {
            e.stopPropagation();
            e.preventDefault();
        });

        //拖拽
        var playing_draging = false;

        var ox_left = 0;//右边拖动条激活状态下 鼠标上次离左边的距离
        var ox_top = 0;//右边拖动条激活状态下 鼠标上次离上边的距离

        play_box.mousedown(function (e) {
            playing_draging = true;
            ox_left = e.pageX;
            ox_top = e.pageY;
            e.stopPropagation();
            e.preventDefault();

        });
        $(document).mousemove(function (e) {//鼠标移动
            if (playing_draging) {
                var left = e.pageX - ox_left; //离上次移动的位置
                var top = e.pageY - ox_top; //离上次移动的位置

                ox_left = e.pageX;
                ox_top = e.pageY;

                if (left != 0) {
                    var old_left = parseInt(play_box.css('left'));
                    var old_right = parseInt(play_box.css('right'));
                    //往左移动
                    if (left < 0) {
                        if (old_left + left < 0) {
                            play_box.css({'right': old_right + old_left});
                        } else {
                            play_box.css({'right': old_right - left});
                        }
                    }
                    //往右移动
                    else {
                        if (old_right - left < 0) {
                            play_box.css({'right': 0});
                        } else {
                            play_box.css({'right': old_right - left});
                        }
                    }
                }
                if (top != 0) {
                    var old_top = parseInt(play_box.css('top'));
                    var old_bottom = parseInt(play_box.css('bottom'));
                    //往上移动
                    if (top < 0) {
                        if (old_top - top < 0) {
                            play_box.css({'top': 0});
                        } else {
                            play_box.css({'top': old_top + top});
                        }
                    }
                    //往下移动
                    else {
                        if (old_bottom - top < 0) {
                            play_box.css({'top': old_top + old_bottom});
                        } else {
                            play_box.css({'top': old_top + top});
                        }
                    }
                }
            }
        }).mouseup(function (e) {
            playing_draging = false;
            e.stopPropagation();
            e.preventDefault()
        });
        exports.search();


        //$(".playing").hover(function (e) {
        //    // spread(0);
        //    if (actioning != 1) {
        //        actioning = 1;
        //        $(this).animate({'right': '0px'}, 500, '', function () {
        //            actioning = 0;
        //        })
        //    }
        //    //e.stopPropagation();
        //}, function (e) {
        //    //   spread(1);
        //    if (actioning != 2) {
        //        actioning = 2;
        //        $(this).animate({'right': '-300px'}, 500, '', function () {
        //            actioning = 0;
        //        })
        //    }
        //    e.stopPropagation();
        //})
    };
    /*异步加载 列表*/
    exports.getPageList = function (option) {
        var opt = {
            url: '',
            page: 1,
            limit: 10,
            order: '',
            sort: '',
            key: ''
        };
        //重新加载
        var reload = function () {
            $(".listData").html('<td colspan="17" style="height:100px;line-height:100px" class="center">' +
                '<img style="width: 40px" src="/srv/static/panel/images/admin/loading2.gif"></td>');
            base.requestApi(opt.url, opt, function (res) {
                if (res.result == 1) {
                    var html = "";
                    if (res.data.list.length > 0) {
                        for (var i in res.data.list) {
                            html += res.data.list[i];
                        }
                        $(".listData").html(html);
                    }
                    $(".pageBar").html(res.data.bar);
                }
            }, true, true);
        };
        if (option) {
            $.extend(opt, option);
        }
        if (opt.url) {
            reload();
            //分页
            $(".pageBar").on("click", 'li a', function () {
                if ($(this).parent().hasClass("disabled")) {
                    return;
                }
                if ($(this).attr("data-id") !== undefined) {
                    opt.page = $(this).attr('data-id');
                    reload();
                }
            }).on('blur', '.page', function () {
                var page = parseInt($(this).val());
                var limit_page = parseInt($(this).attr('data-limit'));

                if (!isNaN(page) && page >= 1) {
                    if (page > limit_page) {
                        opt.page = limit_page;
                    } else {
                        opt.page = page;
                    }
                    reload();
                }
            }).on('blur', '.page_limit', function () {
                var limit = parseInt($(this).val());
                if (!isNaN(limit) && limit >= 1) {
                    if (limit > 100) {
                        opt.limit = 100;
                    } else {
                        opt.limit = limit;
                    }
                    reload();
                }
            });
            //搜索
            $(".btnSearch").on('click', function () {
                $.extend(opt, $("form").serializeObject());
                opt.page = 1;
                reload();
            });
            //排序
            $(".list .arrow").on('click', function () {
                //之前已被选中
                if ($(this).hasClass("active")) {
                    if ($(this).data('sort') == 'desc') {
                        $(this).data('sort', 'asc');
                        $(this).find('.arrow-down').addClass("disabled").removeClass("active");
                        $(this).find('.arrow-up').addClass("active").removeClass("disabled");

                        opt.sort = 'asc';
                    } else {
                        $(this).data('sort', 'desc');
                        $(this).find('.arrow-up').addClass("disabled").removeClass("active")
                        $(this).find('.arrow-down').addClass("active").removeClass("disabled");
                        opt.sort = 'desc';
                    }
                }
                //之前没有被选中
                else {
                    $(".list .arrow.active").find(".active").removeClass('active');
                    $(".list .arrow.active").removeClass("active");
                    $(this).addClass("active").data('sort', 'asc');

                    $(".list .arrow").find('.arrow-down').removeClass("disabled").removeClass("active");
                    $(".list .arrow").find('.arrow-up').removeClass("disabled").removeClass("active");

                    $(this).find('.arrow-down').addClass("disabled").removeClass("active");
                    $(this).find('.arrow-up').addClass("active").removeClass("disabled");
                    opt.sort = 'asc';
                    opt.order = $(this).data('order');
                }
                opt.page = 1;
                reload();
            });
            //tab 切换
            $(".tabs .tab").on('click', function () {
                var key = $(this).data('key');
                var val = $(this).data('val');
                if ($(this).hasClass('active')) {
                    opt[key] = '';
                    $(this).removeClass("active");
                } else {
                    $(this).siblings('.tab[data-key=' + key + ']').removeClass('active');
                    opt[key] = val;
                    $(this).addClass("active");
                }
                var top_current = $(".top_wrap.top_" + val);
                opt['top_id'] = top_current.find(".top.checked").data('id');
                reload();
            });
            //top 切换
            $(".top_wrap .top").on('click', function () {
                var key ='top_id';
                var val = $(this).data('id');
                if ($(this).hasClass('checked')) {
                    opt[key] = '';
                    $(this).removeClass("checked");
                } else {
                    $(this).siblings().removeClass('checked');
                    opt[key] = val;
                    $(this).addClass("checked");
                }
                opt['top_id'] = val;
                reload();
            });


        }
        return reload;

    };
    exports.search = function () {
        $("#key").on('blur', function () {
            $(".search_result").slideUp();
        }).on('focus', function () {
            $(".search_result").show();//.slideDown();
        });
        search.Search("#key", function (value) {
            if (value == '') {
                $(".search_result").hide();//.slideUp();
                return;
            }
            base.requestApi('/api/music/searchWord', {word: value, platform: $("#platform").val()}, function (res) {
                if (res.result == 1) {
                    var html = "";
                    if (res.data.count > 0) {
                        html = res.data.list;
                    } else {
                    }
                    $(".search_result").html(html).show();//.slideDown();
                }
            }, false, true);
        });
        $(".search_result").on("click", '.s_list li', function () {
            $("#key").val($(this).attr('data-key'));
            $(".submitBtn").click();
        });

        //tab 切换
        $(".tabs .tab").on('click', function () {
            var val = $(this).data('val');
            var current = $(".top_wrap.top_" + val);
            $(".top_wrap").hide();
            current.show();
            $(".top_id").val(current.find(".top.checked").data('id'));
        });
    };

    exports.add = function () {
        uploader = new plupload.Uploader({
            browse_button: 'upload-widget', //触发文件选择对话框的按钮，为那个元素id
            url: '/api/upload/audio', //服务器端的上传页面地址
            flash_swf_url: '/data/Moxie.swf', //swf文件，当需要使用swf方式进行上传时需要配置该参数
            multipart: true,
            multi_selection: false, // 一次上传多个
            filters: {
                max_file_size: '20mb',
                min_file_size: '10kb',
                mime_types: [
                    {title: "Files", extensions: 'mp3,ogg,wav,midi,rm,wma,au,cd,vqf,m4a'}
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
                        $("#audio").data('duration', 0);
                        var url = URL.createObjectURL(file.getNative());
                        $("#audio").attr('src', url).unbind().on('canplaythrough', function (e) {
                            var duration = Math.floor(e.target.duration);
                            $("#audio").attr('data-duration', duration);
                            uploader.setOption({
                                multipart_params: {
                                    duration: duration
                                }
                            })
                        });

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

                        submit(res.data);
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
        var modal = $("#addModal");
        //提交数据
        function submit(url) {
            var cat_id = '';//modal.find(".cat").val();//分类id
            var name = $.trim(modal.find("#name").val());//歌曲名称
            var singer = $.trim(modal.find("#singer").val());//歌手
            var album = $.trim(modal.find("#album").val());//专辑名称
            var thumb = $.trim(modal.find("#thumb").val());//歌曲封面图
            var duration = $("#audio").attr('data-duration');//歌曲封面图
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

        //添加歌曲
        $(".btnAdd").on('click', function () {
            //初始化
            // modal.find(".cat").val(1);//分类id
            modal.find(".cat").each(function () {
                if ($(this).prop('checked') == true) {
                    $(this).prop('checked', false);
                }
            });

            modal.find("#name").val('');//歌曲名称
            modal.find("#singer").val('');//歌手
            modal.find("#album").val('');//专辑名称
            modal.find("#thumb").val('');//歌曲封面图
            modal.find("#sort_num").val(50);//排序
            modal.find("#thumb").val('');//歌曲封面图
            modal.find("#fileUrl").html('').attr('data-original_url', '');//歌曲地址
            modal.find("#song_id").val(0);//音乐id
            if (modal.find("#enable").prop('checked')) {
                modal.find("#enable").click()
            }
            if (modal.find("#is_hot").prop('checked')) {
                modal.find("#is_hot").click()
            }

            modal.modal('show');
            $("#audio").attr('data-duration', 0);//歌曲封面图
        });
        //编辑歌曲
        $(".btnEdit").on('click', function () {
            //初始化
            var cat_id = $(this).data('cat_id').toString();
            cat_id = cat_id.split(',');
            modal.find(".cat").each(function () {
                if ($.inArray($(this).data('id').toString(), cat_id) >= 0) {
                    $(this).prop('checked', true);
                } else {
                    $(this).prop('checked', false);
                }
            });
            // modal.find(".cat").val($(this).data('cat_id'));//分类id
            modal.find("#name").val($(this).data('name'));//歌曲名称
            modal.find("#singer").val($(this).data('singer'));//歌手
            modal.find("#album").val($(this).data('album'));//专辑名称
            modal.find("#thumb").val($(this).data('thumb'));//歌曲封面图
            modal.find("#fileUrl").html($(this).data('mp3')).attr('data-original_url', $(this).data('mp3'));//歌曲地址
            modal.find(".preview-thumb").attr('src', $(this).data('thumb'));//歌曲地址

            modal.find("#song_id").val($(this).data('id'));//音乐id
            modal.find("#sort_num").val($(this).data('sort_num'));//排序
            if ((!modal.find("#enable").prop('checked') && $(this).data('enable') == 0) || (modal.find("#enable").prop('checked') && $(this).data('enable') == 1)) {
                modal.find("#enable").click()
            }
            if ((modal.find("#is_hot").prop('checked') && $(this).data('is_hot') == 0) || (!modal.find("#is_hot").prop('checked') && $(this).data('is_hot') == 1)) {
                modal.find("#is_hot").click()
            }
            modal.modal('show');
            $("#audio").attr('data-duration', $(this).data('time'));//歌曲封面图
        });
        storage.getImg('#upThumb', function (res) {
            $('#thumb').val(res.url);
            $('.preview-thumb').attr('src', res.url);
        }, false, {multipart_params: {img_type: 'music'}});

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
            if (modal.find("#fileUrl").html() != modal.find("#fileUrl").attr('data-original_url')) {
                uploader.start();
            } else {
                submit(modal.find("#fileUrl").html());
            }
        });
        //上下架/热门  //热门
        $(document).on('click', '.btnDown', function () {
            var id = $(this).attr('data-id');
            edit(id, 2);
        }).on('click', '.btnUp', function () {
            var id = $(this).attr('data-id');
            edit(id, 1);
        }).on('click', '.btnHot', function () {
            var id = $(this).attr('data-id');
            edit(id, 3);
        }).on('click', '.btnNotHot', function () {
            var id = $(this).attr('data-id');
            edit(id, 4);
        });

        function edit(id, type) {
            base.requestApi('/api/music/edit', {song_id: id, type: type}, function (res) {
                if (res.result == 1) {
                    base.showTip('ok', "设置成功", 1000, function () {
                        window.location.reload();
                    });
                }
            }, false, true);
        }

        //音乐试听
        //语音
        var voice_box = document.getElementById("voice_box");
        var voice_time = '';//当前试听的音乐
        voice_box.addEventListener('playing', function (e) {

        }, false);
        voice_box.onerror = function (e) {
            //1.用户终止 2.网络错误 3.解码错误 4.URL无效
            if (voice_box.src != window.location.href) {
                if (voice_box.error.code == 4) {
                    alert("资源不存在【" + voice_box.src + '】');
                }
            }
            //alert("Error! 出错了");
        };
        //进度条
        voice_box.addEventListener('timeupdate', function () {
            // var percent = ((this.currentTime / this.duration) * 100).toFixed(1);
            currentTime = Math.ceil(this.currentTime);
            if (currentTime > 60) {
                m = Math.ceil(currentTime / 60);
                m = m < 10 ? ('0' + m) : m;
                s = currentTime % 60;
            } else {
                m = '00';
                s = currentTime;
            }
            $(".music_" + voice_this).find('.time_percent').html(m + ':' + (s < 10 ? '0' + s : s))

        });
        //播放结束
        voice_box.addEventListener('ended', function () {
            voice_this = '';
        }, false);
        //播放
        $(".listData").on('click', '.listenBtn', function () {
            voice_box.src = $(this).attr('data-src');
            voice_this = $(this).attr('data-id')
            voice_box.play();
        });


    }

});