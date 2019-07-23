define(function (require, exports) {
    var base = require('app/panel/panel.base');
    base.selectNone();
    base.selectCheckbox();
    exports.logApi = function () {
        $(".delAllSelected").on('click', function (e) {
            // params
            var data = [];
            $(".listData input.chk").each(function () {
                if ($(this).is(':checked')) {
                    data.push($(this).attr('data-id'));
                }
            });

            //  has no selected
            if (data.length == 0) {
                base.showTip('err', '请选择需要删除的项', 3000);
                return;
            }

            // confirm
            $(this).confirm("你确定删除选中的项?不可恢复", {
                ok: function () {
                    base.requestApi('/api/log/remove', {data: data}, function (res) {
                        if (res.result == 1) {
                            base.showTip('ok', '操作成功！', 3000);
                            window.location.reload();
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
    };
    var Notify = null;

    function Inotify() {
    }

    Inotify.lines = 0;
    Inotify.has_connect = false;//是否已经连接
    Inotify.wsUri = "ws://112.74.15.30:9502"; //websocket ip地址
    //Inotify.wsUri = "ws://120.24.209.32:9502";
    Inotify.websocket = '';
    Inotify.filename = '';//文件名
    Inotify.domain = window.location.host;//域名
    Inotify.line_count = $(".end_row").val();//读取末尾的行数
    Inotify.prototype = {
        init: function (filename) {
            //console.log(output);
            this.testWebSocket();
            if (filename !== 'undefined') {
                Inotify.filename = filename;
            }
        },
        testWebSocket: function () {
            var _this = this;

            Inotify.websocket = new WebSocket(Inotify.wsUri);
            Inotify.has_connect = true;
            Inotify.websocket.onopen = function (evt) {
                _this.onOpen(evt)
            };
            Inotify.websocket.onclose = function (evt) {
                _this.onClose(evt)
            };
            Inotify.websocket.onmessage = function (evt) {
                _this.onMessage(evt)
            };
            Inotify.websocket.onerror = function (evt) {
                _this.onError(evt)
            };
        },
        closeSocket: function () {
            Inotify.websocket.close();
            Inotify.has_connect = false;

        },
        onOpen: function (evt) {
            var data = {};
            data["cmd"] = 2;
            data["domain"] = Inotify.domain;//根据域名找到Log路径，防止跳转到别的目录去了。
            data["filename"] = Inotify.filename;
            data["line_count"] = Inotify.line_count;
            console.log("已连接");
            var jsonString = JSON.stringify(data);
            //console.log(jsonString);
            //doSend("WebSocket rocks");
            this.doSend(jsonString);
            Inotify.has_connect = true;
        },
        onClose: function (evt) {
            //   console.log("DISCONNECTED");
            console.log("连接关闭");
            Inotify.has_connect = false;
        },
        onMessage: function (evt) {
            // console.log(evt.data);
            var data = eval('(' + evt.data + ')');
            if (data.error == '') {
                window.isWebSocketPushing = true;
                this.fillData(data.data_list, data.count, false, data.line_count);
                setTimeout(function(){
                    window.isWebSocketPushing = false;

                },200)
            } else {
                console.log(data.error);
            }
            //   console.log(data);
            //    $('#tail').html($('#tail').html() + data.data_list);
            // lines++
            //  $('#tail').scrollTop(lines * 100)
        },
        onError: function (evt) {
            console.log('<span style="color: red;">ERROR:</span> ' + evt.data);
        },
        doSend: function (message) {
            console.log("SENT: " + message);
            Inotify.websocket.send(message);
        },
        /* watch: function (filename) {
         var data = {};
         data["cmd"] = 2;
         data["domain"] = window.location.host;//根据域名找到Log路径，防止跳转到别的目录去了。
         data["filename"] = filename;
         console.log("CONNECTED");
         var jsonString = JSON.stringify(data);
         // console.log(jsonString);
         this.doSend(jsonString);
         },*/
        fillData: function (data, count, reset, line) {
            $("#loadWrap").show();
            //$(".line_count")
            if (reset) {
                $(".start_line .start").val('');
                $(".start_line .end").val('');
                $(".read-area").html(' <div style="height: 100%; width: 100%; line-height: 100%; vertical-align: middle;text-align: center;">' +
                    '<img src="/static/panel/images/admin/loading.gif">' +
                    '</div>');
                if (data.length == 0) {
                    $('.read-area').html("");
                    total_count = 0;
                } else {
                    total_count = count;
                    $('.read-area').html("");
                    $.each(data, function () {
                        $('.read-area').append(this);
                    });
                }
            } else {

                if (data.length == 0) {
                    $('.read-area').append("");
                    total_count = 0;
                } else {
                    total_count = count;
                    $.each(data, function () {
                        $('.read-area').append(this);
                    });
                    var end_row = parseInt($("input[name='end_row']").val());
                    var data_length = $(".read-area li").length;//显示的数据的行数
                    if (data_length > end_row) {
                        $(".read-area li:lt(" + (data_length - end_row ) + ")").hide();
                    }
                    $('#readArea').scrollTop($('#readArea')[0].scrollHeight);

                    //$(".read-area").scrollTop($);
                }
            }
            setTimeout(function () {
                $("#loadWrap").hide();
                $(".total_line_count").html('共' + line + '行&nbsp; <span class="icon icon-reload refreshBtn" style="color:#eee"></span>');
            }, 1000);
        },


    };

    exports.log = function () {



        /*文件变动监听*/
        var Notify = new Inotify();
        Notify.init();


        var start_line = 1;//文件的起始行数
        var end_row = 0;//获取文件的末尾行数
        var total_count = -1;//文件总行数
        var limit = 200; //每页显示的行数

        $(".tab").on('click', function () {
            if (!$(this).hasClass('active')) {
                $(".tree-panel ul").html("<li><img src='/static/panel/images/admin/loading.gif'/>加载中...</li>")
                Notify.closeSocket();
                $(".tab").removeClass('active');
                $(this).addClass("active");
                var tab = $(this).data('id');
                base.requestApi('/api/log/getFolder', {
                    tab: tab,
                }, function (res) {
                    if (res.result == 1) {
                        $(".tree-panel ul").html(res.data);
                    }
                    //   $('.read-area').html(res.data);
                }, false, true);
            }

        })

        $(document).on('click', '.getSubBtn', function () {
            var obj = $(this).closest('.folder');
            var path = obj.attr('data-path');
            var _this = this;
            if (!$(_this).hasClass('spread')) {
                /*   $(".sub-files[data-path!='" + path + "']").removeClass('spread').hide();*/
                /* $(".getSubBtn[data-path!='" + path + "']").removeClass('spread');*/
                /*  $(".folder[data-path!='" + path + "']").removeClass('spread');*/
                // obj.addClass('spread').siblings().removeClass('spread');
                // obj.siblings().removeClass('spread').addClass('spread');
                getFiles(_this, path, function (res) {
                    obj.find('.sub-files').html(res.data);
                    $(".sub-files[data-path='" + path + "']").addClass('spread').show();
                    $(_this).addClass('spread').find('.icon').show();
                });
            } else {
                obj.find('.sub-files').toggle();
                // obj.siblings().find('.sub-files').toggle();
            }
            Notify.closeSocket();

        });
        $(document).on('click', '.openFileBtn', function () {
            var obj = $(this).closest('.file');
            var file = obj.attr('data-file');
            /*初始化信息 start*/
            total_count = -1;
            start_line = 1;
            limit = 200;
            end_row = 0;
            /*初始化信息 end*/
             Notify.closeSocket();
            getData(file, 0, true, function () {
                Notify.init(file);
            });
        });
        //行数筛选
        $(".lineFilterBtn").on('click', function () {
            var path = $(".read-area").attr('data-path');
            if (path == '') {
                return false;
            }
            var start_line_current = $(".start_line .start").val();
            var end_line = $(".start_line .end").val();

            if (start_line_current == '') {
                return false;
            }
            start_line_current = parseInt(start_line_current);
            if (end_line != '') {
                end_line = parseInt(end_line);
                if (end_line <= start_line_current) {
                    return false;
                }
            }
            start_line = start_line_current;
            end_row = 0;
            /*  Notify.closeSocket();*/
            getData(path, end_line, true);

        });
        //重新加载
        $(document).on('click', ".refreshBtn", function () {
            var path = $(".read-area").attr('data-path');
            if (path == '') {
                return false;
            }
            start_line = 1;
            end_row = 0;
            /*   Notify.closeSocket();*/
            getData(path, 0, true);
        });
        //清屏
        $(document).on('click', ".clearScreen", function (e) {
            $("#readArea").html("");
            $(this).parent().hide();
            e.stopPropagation();
            e.preventDefault();
        });

        //  window.addEventListener("load",.init(), false);

        $(".EndLineFilter").on('click', function () {
            var line = $("input[name='end_row']").val();
            start_line = 1;
            if (line == '' || isNaN(line)) {
                return false;
            }
            var path = $(".read-area").attr('data-path');
            if (path == '') {
                return false;
            }
            end_row = line;
            /*  Notify.closeSocket();*/
            getData(path, 0, true);

            //    Notify.watch(path);

        });

        function getFiles(_this, path, func) {
            $.ajax({
                url: '/api/log/getLogFile',
                async: true,
                data: {
                    path: path
                },
                dataType: 'json',
                type: 'post',
                beforeSend: function () {
                    $(_this).find('.icon').hide();
                },
                success: function (res) {
                    if (typeof func == 'function') {
                        func(res);
                    }

                    if (res.result != 1) {
                        tip.showTip('err', res.error.more ? (res.error.msg + "[" + res.error.more + "]") : res.error.msg, 3000);
                    }

                }
            });
        }

        function getData(file, end_line, reset, callback) {
            base.requestApi('/api/log/openFile', {
                file: file,
                start_line: start_line,
                end_line: end_line,
                end_row: end_row
            }, function (res) {
                fillData(res.data.data_list, res.data.count, reset, res.data.line_count, callback);
                $('.read-area').attr('data-path', file);
                //   $('.read-area').html(res.data);
            }, false, true);
        }

        function fillData(data, count, reset, line, callback) {
            //$(".line_count")
            if (reset) {
                $(".start_line .start").val('');
                $(".start_line .end").val('');
                $(".read-area").html(' <div style="height: 100%; width: 100%; line-height: 100%; vertical-align: middle;text-align: center;">' +
                    '<img src="/static/panel/images/admin/loading.gif">' +
                    '</div>');
                if (end_row > 0) {
                    var temp = (line - end_row) >= 0 ? (line - end_row) : 1;
                    start_line = start_line > temp ? start_line : temp;
                }
                if (data.length == 0) {
                    $('.read-area').html("");
                    total_count = 0;
                } else {
                    total_count = count;
                    $('.read-area').html("");
                    $.each(data, function () {
                        $('.read-area').append(this);
                    });
                    start_line += count;
                }
            } else {
                if (end_row > 0) {
                    var temp = (line - end_row) >= 0 ? (line - end_row) : 1;
                    start_line = start_line > temp ? start_line : temp;
                }
                if (data.length == 0) {
                    $('.read-area').append("");
                    total_count = 0;
                } else {
                    total_count = count;
                    $.each(data, function () {
                        $('.read-area').append(this);
                    });
                    start_line += count;
                }
            }
            $(".total_line_count").html('共' + line + '行&nbsp; <span class="fa fa-repeat refreshBtn" style="color:#eee"></span>');
            if (callback !== null && typeof callback == 'function') {
                callback();
            }
        }

        var nScrollHight = 0; //滚动距离总长(注意不是滚动条的长度)
        var nScrollTop = 0; //滚动到的当前位置
        var nDivHight = $(".read-area").height();
        var padding = parseInt($('.read-area').css('padding-top')) + parseInt($('.read-area').css('padding-bottom'));
        $(".read-area").scroll(function () {
            if( !window.isWebSocketPushing )
            {
                console.log('调用getdata');
                nScrollHight = $(this)[0].scrollHeight;
                nScrollTop = $(this)[0].scrollTop;
                if (nScrollTop + nDivHight + padding >= nScrollHight) {
                    //  if (total_count == limit)
                    getData($(this).attr('data-path'), 0, false);

                }
            }


        });

    };

});