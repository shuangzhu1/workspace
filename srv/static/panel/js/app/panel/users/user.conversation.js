/**
 * Created by ykuang on 2017/12/18.
 */
define(function (require, exports) {
    var base = require('app/panel/panel.base');//公共函数
    require("/static/panel/js/tools/iscroll.js");
    exports.conversation = function (user_id) {
        var __this = this;
        var current_last_id = 0;
        var current_first_id = 0;
        var mix_id = 0;//单聊会话id
        var gid = 0;//群聊ID
        var key = "";//搜索关键字
        var start = ''; //消息开始时间
        var end = '';//消息结束时间
        var chat_type = 'single';//单聊 -single 群聊 -group
        var video = null;//当前视频容器
        var uid = user_id;


        //上下滚动
        var myScroll,
            upIcon = $("#up-icon"),
            downIcon = $("#down-icon");
        var is_loading = false;

        //音频播放
        var voice_timer = false;
        var voice_play = 0;
        var voice_box = document.getElementById("voice_box");

        //初始化
        this.init = function () {
            this.voiceHandle();
            this.videoHandle();
            this.refreshConversationList();
            this.refreshMessageList();
            this.switchTarget();
            this.switchChatType();
            this.load();
            this.search();
        };
        //切换聊天对象
        this.switchTarget = function () {
            $(".con_wrap").on('click', '.item', function () {
                current_last_id = 0;
                current_first_id = 0;
                start = '';
                end = '';
                //时间筛选初始化
                $("#start").val("");
                $("#end").val("");

                var chat_type = $(".tabs .tab.active").data('id');
                $(this).addClass("current").siblings().removeClass('current');
                if (chat_type == 'single') {
                    mix_id = $(this).attr('data-id');
                } else {
                    gid = $(this).attr('data-id');
                }
                $(".filter .username").html($(this).data('name'));
                //$(".filter .username").html($(this).data('name'));
                $(".filter").show();

                //$("#mix_id").val($(this).attr('data-mix_id'));
                __this.refreshMessageList(0)
            })
        };
        //切换聊天类型
        this.switchChatType = function () {
            $(".tabs").on('click', '.tab', function () {
                if (chat_type != $(this).data('id')) {
                    $(this).addClass("active").siblings().removeClass("active");
                    chat_type = $(this).data('id');
                    $(".search").hide();
                    $(".search_" + chat_type).show();
                    mix_id = gid = 0;
                    __this.refreshConversationList();
                    // __this.refreshMessageList();
                }
            })
        };
        //音频处理
        this.voiceHandle = function () {
            //播放结束
            voice_box.addEventListener('ended', function () {
                $(".voice.active").css({'width': '16px'}).removeClass("active");
                voice_timer && clearInterval(voice_timer);
            }, false);

            $("#wrapper").on('click', '.voice', function () {
                if ($(this).hasClass("active")) {
                    $(this).removeClass("active");
                    voice_play = 0;
                    $(this).css({'width': '16px'});
                    voice_timer && clearInterval(voice_timer);
                    voice_box.pause();
                } else {
                    var __self = $(this);
                    $(".voice.active").css({'width': '16px'}).removeClass("active");
                    $(this).addClass('active');
                    voice_timer && clearInterval(voice_timer);

                    voice_play = __self.data('id');
                    var i = 1;
                    voice_box.src = __self.data('src');
                    voice_box.play();

                    voice_timer = setInterval(function () {
                        if (i == 1) {
                            __self.css({'width': '6px'});
                        } else if (i == 2) {
                            __self.css({'width': '9px'});
                        } else {
                            __self.css({'width': '16px'});
                            i = 0;
                        }
                        i++;
                    }, 300)
                }
            });
        };
        //视频处理
        this.videoHandle = function () {
            //视频
            $(".video").on('loadeddata', function () {
                __this.captureImage(this);
            });
            $(".video").on('ended', function () {
                __this.videoEnd(this);
            });
            $(document).on('click', '.play', function () {
                var is_visible = $(this).find('i').is(":visible");
                if ($(".video:visible").length > 0) {
                    __this.videoEnd($(".video:visible"), 'click');
                }
                if (is_visible) {
                    video = $(this).parent().find("video")[0];
                    $(this).parent().find('img').hide();
                    $(this).parent().find('video').show();
                    $(this).find("i").hide();
                    video.play();
                }
                /*  $(this).parent().find('video')[0].webkitRequestFullScreen();
                 $(this).parent().find('video')[0].mozRequestFullScreen();
                 $(this).parent().find('video')[0].requestFullscreen();*/
            });
        };
        //视频截图
        this.captureImage = function (video) {
            var canvas = document.createElement("canvas");
            canvas.width = video.videoWidth;
            canvas.height = video.videoHeight;
            canvas.getContext('2d').drawImage(video, 0, 0, canvas.width, canvas.height);

            $(".video_thumb[data-id='" + $(video).attr('data-id') + "']").attr('src', canvas.toDataURL("image/png")).show();
            $(".video_box[data-id='" + $(video).attr('data-id') + "']").find(".play i").css(
                {
                    'margin-top': ((180 / video.videoWidth) * video.videoHeight) / 2 - 25
                });
            myScroll.refresh();
        };
        //视频播放完成
        this.videoEnd = function (current_video, type) {
            try {
                if (type == 'click') {
                    video.pause();
                }
            } catch (e) {
            }
            $(current_video).hide();
            $(current_video).siblings(".video_thumb").show();
            $(current_video).siblings('.play').find("i").show();
        };
        //刷新聊天历史记录
        this.refreshMessageList = function (type) {
            if (is_loading) {
                return
            }
            if (chat_type == 'single' && mix_id == 0) {
                //初始化一下
                $("#scroller-content ul").html('<li  style="width: 100%;text-align:center;margin-top: 100px"> <img src="/srv/static/panel/images/13915.png"/></li>');
                $(".filter").hide();
                return;
            }
            else if (chat_type == 'group' && gid == 0) {
                //初始化一下
                $("#scroller-content ul").html('<li  style="width: 100%;text-align:center;margin-top: 100px"> <img src="/srv/static/panel/images/13915.png"/></li>');
                $(".filter").hide();
                return;
            }
            $(".loading").animate({top: 50});
            is_loading = true;

            var limit = $("#limit").val();
            var search_key = $("#search_key").val();
            if (isNaN(limit) || limit <= 0) {
                limit = 50;
            }
            //下拉
            if (type == 1) {
                base.requestApi('/api/user/getConMes', {
                    mix_id: mix_id,
                    chat_type: chat_type,
                    gid: gid,
                    first_id: current_first_id,
                    start: start,
                    end: end,
                    limit: limit,
                    search_key:search_key
                }, function (res) {
                    if (res.result == 1) {
                        $(".total_count").html(res.data.data_count);
                        if (res.data.list.length > 0) {
                            var html = "";
                            for (var i in res.data.list) {
                                html += res.data.list[i]
                            }
                            $(".loading").animate({top: -80}, 1000, '', function () {
                                $("#scroller-content ul").prepend(html);
                                /*  $.each(res.data.list, function () {
                                 console.log(this);

                                 });*/
                                if (res.data.hide_tip) {
                                    // console.log(res.data.hide_tip);
                                    $(".tip[data-id='" + res.data.hide_tip + "']").remove();
                                }
                                myScroll.refresh();

                                //视频加载
                                if (res.data.video_ids.length > 0) {
                                    $.each(res.data.video_ids, function () {
                                        $(".video[data-id='" + this + "']").on('canplaythrough', function () {
                                            __this.captureImage(this);
                                        }).on('ended', function () {
                                            __this.videoEnd(this);
                                        });
                                    })
                                }
                                current_first_id = res.data.first_id;
                            });

                        } else {
                            $(".loading").animate({top: -80}, 1000, '', function () {
                            })
                            //$("#scroller-content ul").html('<li style="height: 500px;border-bottom: none;"> <img src="/static/panel/images/admin/no_data.png" style="background-color: #fff;"/>&nbsp;暂无数据 </li>');
                        }
                        setTimeout(function () {
                            is_loading = false
                        }, 1000);
                    }
                }, false, true)
            }
            //上拉
            else if (type == 2) {
                base.requestApi('/api/user/getConMes', {
                    mix_id: mix_id,
                    chat_type: chat_type,
                    gid: gid,
                    last_id: current_last_id,
                    start: start,
                    end: end,
                    limit: limit,
                    search_key:search_key
                }, function (res) {
                    if (res.result == 1) {
                        $(".total_count").html(res.data.data_count);
                        $(".loading").animate({top: -80}, 1000, '', function () {
                            if (res.data.list.length > 0) {
                                $.each(res.data.list, function () {
                                    $("#scroller-content ul").append(this);
                                });
                                myScroll.refresh();
                                /*   myScroll.scrollTo(0, myScroll.maxScrollY, 1000);*/
                                current_last_id = res.data.last_id;
                                //视频加载
                                if (res.data.video_ids.length > 0) {
                                    $.each(res.data.video_ids, function () {
                                        $(".video[data-id='" + this + "']").on('canplaythrough', function () {
                                            __this.captureImage(this);
                                        }).on('ended', function () {
                                            __this.videoEnd(this);
                                        });
                                    })
                                }
                            } else {
                                $(".loading").animate({top: -80}, 1000, '', function () {
                                })
                                //  $("#scroller-content ul").html('<li style="height: 80px;border-bottom: none"> <img src="/static/panel/images/admin/no_data.png" style="background-color: #fff;"/>&nbsp;暂无数据 </li>');
                            }
                            setTimeout(function () {
                                is_loading = false
                            }, 1000);


                        });

                    }
                }, false, true)
            }
            //重新加载
            else {
                base.requestApi('/api/user/getConMes', {
                    mix_id: mix_id,
                    chat_type: chat_type,
                    gid: gid,
                    start: start,
                    end: end,
                    limit: limit,
                    search_key:search_key
                }, function (res) {
                    if (res.result == 1) {
                        $(".total_count").html(res.data.data_count);
                        $(".loading").animate({top: -80}, 1000, '', function () {
                            if (res.data.list.length > 0) {
                                var html = "";
                                $.each(res.data.list, function () {
                                    html += this;
                                });
                                $("#scroller-content ul").html(html);
                                myScroll.refresh();
                                /*   myScroll.scrollTo(0, myScroll.maxScrollY, 1000);*/
                                current_last_id = res.data.last_id;
                                current_first_id = res.data.first_id;

                                //视频加载
                                if (res.data.video_ids.length > 0) {
                                    $.each(res.data.video_ids, function () {
                                        $(".video[data-id='" + this + "']").on('canplaythrough', function () {
                                            console.log(this);
                                            __this.captureImage(this);
                                        }).on('ended', function () {
                                            __this.videoEnd(this);
                                        });
                                    })
                                }
                            } else {
                                $(".loading").animate({top: -80}, 1000, '', function () {
                                })
                                $("#scroller-content ul").html('<li style="height: 80px;border-bottom: none;text-align:center;margin-top: 50px"> <img src="/static/panel/images/admin/no_data.png" style="background-color: #fff;"/>&nbsp;暂无数据 </li>');
                            }
                        })

                    }
                    /*  if (($("#scroller-content ul").height() >= 500)) {
                     $("#scroller-pullUp").show();
                     }*/
                    setTimeout(function () {
                        is_loading = false
                    }, 1000);


                }, false, true)
            }
        };
        //刷新会话列表记录
        this.refreshConversationList = function () {
            $(".con_list").html("<li><img src='/srv/static/panel/images/admin/loading2.gif'> 数据加载中</li>")
            base.requestApi('/api/user/getCon', {chat_type: chat_type, uid: uid, key: key}, function (res) {
                if (res.result == 1) {
                    $(".con_list").html(res.data.list)
                }
            }, true, true);
            mix_id = 0;
            gid = 0;
            __this.refreshMessageList();
        };
        //搜索
        this.search = function () {
            //会话列表搜索
            $(".searchBtn").on('click', function () {
                key = $.trim($(".search_" + $(this).data('id')).find('input').val());
                __this.refreshConversationList(0);
            });
            //消息历史搜索
            $(".btnMsgSearch").on('click', function () {
                start = $.trim($("#start").val());
                end = $.trim($("#end").val());
                __this.refreshMessageList(0)
            })

            $(function () {
                $(".search_input").keyup(function (event) {
                    //回车
                    if (event.keyCode === 13) {
                        key = $(this).val();
                        __this.refreshConversationList(0);
                    }
                });

            });
        };
        //上拉下拉加载刷新
        this.load = function () {
            myScroll = new IScroll('#wrapper', {
                probeType: 3,
                'scrollbars': true,
                'mouseWheel': true,
                'interactiveScrollbars': true,
                'shrinkScrollbars': 'scale',
                'fadeScrollbars': false,
                'preventDefault': false
            });
            myScroll.on("scrollEnd", function () {
                if (this.y == 0) {
                    __this.refreshMessageList(1)
                }
                else if (this.maxScrollY == this.y) {
                    __this.refreshMessageList(2)
                }
            });
            myScroll.on("scroll", function () {
                var y = this.y,
                    maxY = this.maxScrollY - y,
                    downHasClass = downIcon.hasClass("reverse_icon"),
                    upHasClass = upIcon.hasClass("reverse_icon");

                if (y >= 40) {
                    !downHasClass && downIcon.addClass("reverse_icon");
                    return "";
                } else if (y < 40 && y > 0) {
                    downHasClass && downIcon.removeClass("reverse_icon");
                    return "";
                }

                if (maxY >= 40) {
                    !upHasClass && upIcon.addClass("reverse_icon");
                    return "";
                } else if (maxY < 40 && maxY >= 0) {
                    upHasClass && upIcon.removeClass("reverse_icon");
                    return "";
                }
            });

            myScroll.on("slideDown", function () {
                if (this.y > 40) {
                    __this.refreshMessageList(1);
                    upIcon.removeClass("reverse_icon")
                }
            });

            myScroll.on("slideUp", function () {
                if (this.maxScrollY - this.y > 40) {
                    __this.refreshMessageList(2);
                    upIcon.removeClass("reverse_icon")
                }
            });

        }
        this.init()
    };
});