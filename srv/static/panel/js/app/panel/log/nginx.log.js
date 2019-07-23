define(function (require, exports) {
    var base = require('app/panel/panel.base');
    exports.log = function () {

        function Nginx() {
        }
        Nginx.prototype = {
            opt: {
                key: '',
                start_line: 1,
                end_line: 0,
                end_row: 0,
                start_time: '',
                end_time: '',
                limit: 200,
                'loading': false
            },
            init: function () {
                this.getData();
                var __this = this;
                var nScrollHight = 0; //滚动距离总长(注意不是滚动条的长度)
                var nScrollTop = 0; //滚动到的当前位置

                $(".read_content").scroll(function () {
                    var nDivHight = $(".read_content").height();
                    var padding = parseInt($('.read_content').css('padding-top')) + parseInt($('.read_content').css('padding-bottom'));
                    nScrollHight = $(this)[0].scrollHeight;
                    nScrollTop = $(this)[0].scrollTop;
                    if (nScrollTop + nDivHight + padding >= nScrollHight) {
                        if ($("#start_line").val() == '' && $("#end_line").val() == '') {
                            if (!__this.opt.loading) {
                                __this.getData();
                            }
                        }
                        //  if (total_count == limit)
                    }

                });
            },
            setOpt: function (option) {
                this.opt = $.extend(this.opt, option);
            },
            getData: function (refresh) {

                var __this = this;
                if (__this.opt.end_row > 0) {
                    refresh = true;
                    __this.opt.start_line = 1;
                }
                __this.opt.loading = true;
                $(".loading").show();
                base.requestApi('/api/log/nginx', {
                    key: __this.opt.key,
                    start_line: __this.opt.start_line,
                    end_line: __this.opt.end_line,
                    end_row: __this.opt.end_row,
                    start_time: __this.opt.start_time,
                    end_time: __this.opt.end_time,
                    limit: __this.opt.limit
                }, function (res) {
                    if (refresh) {
                        $('.read_content').html("");
                    }
                    if (res.data.data_list.length == 0) {
                        $('.read_content').append("");
                    } else {
                        $.each(res.data.data_list, function () {
                            __this.opt.start_line += 1;
                            $('.read_content').append(this);
                        });
                    }
                    $(".total_line_count").html((__this.opt.key != '' ? "匹配到的内容共" : "文件共") + " <b class='red'>" + res.data.data_count + '</b> 行');
                    $(".loading").hide();
                    setTimeout(function () {
                        __this.opt.loading = false;
                    }, 1000);
                    //fillData(res.data.data_list, res.data.count, reset, res.data.line_count, callback);
                    //$('.read-area').attr('data-path', file);
                }, false, true);
            },
            fillData: function () {

            }

        };

        var nginx = new Nginx();
        nginx.init();

        $(".btnSearch").on('click', function () {
            var start_line = $.trim($("#start_line").val());
            var end_line = $.trim($("#end_line").val());
            var end_row = $.trim($("#end_row").val());
            var key = $.trim($("#key").val());
            var opt = {start_line: 1, end_line: 0, key: '', end_row: 0};
            if (end_row != '' && !isNaN(end_row)) {
                opt.end_row = parseInt(end_row);
            } else {
                if (start_line != '' && !isNaN(start_line)) {
                    opt.start_line = parseInt(start_line);
                }
                if (end_line != '' && !isNaN(end_line)) {
                    opt.end_line = parseInt(end_line);
                }
            }
            if (key != '') {
                opt.key = key;
            }
            nginx.opt = $.extend(nginx.opt, opt);
            /*   nginx.setOpt(opt);*/
            nginx.getData(true);
        });


    }
});