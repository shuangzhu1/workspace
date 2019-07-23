/**
 * Created by ykuang on 2018/1/24.
 */
define(function (require, exports) {
    var base = require('/srv/static/panel/js/app/panel/panel.base');
    require('/srv/static/panel/js/echarts/themes/pink.js');
    require('/srv/static/panel/js/echarts/themes/blue.js');
    require('/srv/static/panel/js/echarts/themes/green.js');
    require('/srv/static/panel/js/echarts/themes/purple.js');
    require('/srv/static/panel/js/jquery/jquery.datetimepicker.js');
    require('/srv/static/panel/js/app/panel/stat/pager.js?v=1.0');


    exports.filter = function () {
        $(".time").on('focus', function () {
            $(this).css({'border-color': '#438eb9', 'border-right': 'none'});
            $(".time_picker[data-id='" + $(this).data('id') + "']").css({
                'background-color': '#438eb9',
                'border-color': '#438eb9',
                'color': "#fff"
            });
        }).on('blur', function () {
            $(this).css({'border-color': '#ccc'});
            $(".time_picker[data-id='" + $(this).data('id') + "']").css({
                'background-color': '#fff',
                'border-color': '#ccc',
                'color': '#666'
            });
        });
        $(".time_picker").on('click', function (e) {
            var destination = $('.time[data-id="' + ($(this).data('id')) + '"]');
            destination.focus();
            e.stopPropagation();
        });

        $('#start').datetimepicker({
            lang: "ch",
            step: 5,
            format: "Y-m-d",
            maxDate: 0,
            timepicker: false,
            onChangeDateTime: function (e) {
                $('#end').val("").datetimepicker({minDate: $('#start').val().replace(/-/g, '/')}).focus();
            }
        });
        $('#end').datetimepicker({
            lang: "ch",
            step: 5,
            format: "Y-m-d",
            maxDate: 0,
            timepicker: false,
            onChangeDateTime: function (e) {
                exports.getData('user', 'custom', $('#start').val(), $('#end').val());
            }
        });
        $('#start1').datetimepicker({
            lang: "ch",
            step: 5,
            format: "Y-m-d",
            maxDate: 0,
            timepicker: false,
            onChangeDateTime: function (e) {
                $('#end1').val("").datetimepicker({minDate: $('#start1').val().replace(/-/g, '/')}).focus();
            }
        });
        $('#end1').datetimepicker({
            lang: "ch",
            step: 5,
            format: "Y-m-d",
            maxDate: 0,
            timepicker: false,
            onChangeDateTime: function (e) {
                exports.getData('money', 'custom', $('#start1').val(), $('#end1').val());
            }
        });
        $('#start2').datetimepicker({
            lang: "ch",
            step: 5,
            format: "Y-m-d",
            maxDate: 0,
            timepicker: false,
            onChangeDateTime: function (e) {
                $('#end2').val("").datetimepicker({minDate: $('#start2').val().replace(/-/g, '/')}).focus();
            }
        });
        $('#end2').datetimepicker({
            lang: "ch",
            step: 5,
            format: "Y-m-d",
            maxDate: 0,
            timepicker: false,
            onChangeDateTime: function (e) {
                exports.getData('package_count', 'custom', $('#start2').val(), $('#end2').val());
            }
        });
        $('#start3').datetimepicker({
            lang: "ch",
            step: 5,
            format: "Y-m-d",
            maxDate: 0,
            timepicker: false,
            onChangeDateTime: function (e) {
                $('#end3').val("").datetimepicker({minDate: $('#start3').val().replace(/-/g, '/')}).focus();
            }
        });
        $('#end3').datetimepicker({
            lang: "ch",
            step: 5,
            format: "Y-m-d",
            maxDate: 0,
            timepicker: false,
            onChangeDateTime: function (e) {
                exports.getList('pick_count', 'custom', $('#start3').val(), $('#end3').val());
            }
        });
        $(".trackable").on('click', function () {
            if (!$(this).hasClass('cur')) {
                $(this).addClass('cur').siblings('.trackable').removeClass('cur');
                if ($(this).data('id') == 'custom') {
                    $(this).parent().siblings(".time_filter").show();
                } else {
                    if ($(this).data('type') == 'pick_count') {
                        exports.getList('pick_count', $(this).data('id'), '', '');
                    } else {
                        exports.getData($(this).data('type'), $(this).data('id'), '', '');
                    }
                    $(this).parent().siblings(".time_filter").hide();
                }
            }
        });

    };
    exports.getList = function (type, day, start, end) {
        if (type == 'pick_count') {
            $(".pick_count .content").pager({
                'url': '/api/statlist/pickUser',
                params: {day: day, start: start, end: end, 'type': type},
                limit: 10
            });
        } else {
            $(".pick_money .content").pager({
                'url': '/api/statlist/pickUser',
                params: {day: day, start: start, end: end, 'type': type},
                limit: 10
            });
        }


    };
    exports.getData = function (type, day, start, end) {
        var option = {
            title: {
                text: '',
                subtext: ''
            },
            tooltip: {
                trigger: 'axis'
            },
            legend: {
                data: ["发出", '领取']
            },
            toolbox: {
                show: true,
                feature: {
                    mark: {show: true},
                    //dataView: {show: true, readOnly: false},
                    //magicType: {show: true, type: ['line', 'bar', 'stack', 'tiled']},
                    //restore: {show: true},
                    //saveAsImage: {show: true}
                }
            },
            calculable: true,
            xAxis: [
                {
                    type: 'category',
                    //boundaryGap: false,
                    data: '',

                }

            ],
            yAxis: [
                {
                    type: 'value',
                    axisLabel: {
                        formatter: '{value}'
                    }
                }
            ],
            series: [
                {
                    name: '发出',
                    type: 'line',
                    data: ''
                },
                {
                    name: '领取',
                    type: 'line',
                    data: ''
                }
            ]
        };
        // $("#" + type).html("<div class='loading'><img src='/static/panel/images/admin/loading2.gif'/>&nbsp;&nbsp;数据加载中...</div>");
        var theme = "blue";//主题
        //用户
        if (type == 'user') {
            theme = "blue";
            option.legend.data = ["发红包", "领红包", "发红包机器人", "发红包真实用户", "领红包真实用户"];
            option.series = [
                {
                    name: '发红包',
                    type: 'line',
                    data: '',
                },
                {
                    name: '领红包',
                    type: 'line',
                    data: ''
                },
                {
                    name: '发红包机器人',
                    type: 'bar',
                    data: '',
                    itemStyle: {
                        normal: {
                            color: 'rgb(77, 182, 172)',
                        }
                    },
                },
                {
                    name: '发红包真实用户',
                    type: 'bar',
                    data: '',
                    itemStyle: {
                        normal: {
                            color: 'rgb(27, 94, 32)',
                        }
                    },
                },
                {
                    name: '领红包真实用户',
                    type: 'bar',
                    data: '',
                    itemStyle: {
                        normal: {
                            color: 'rgb(158, 157, 36)',
                        }
                    },
                }
            ]
        }
        //金额
        else if (type == 'money') {
            theme = "pink";
            option.legend.data = ["发红包", "领红包", "发红包机器人", "发红包真实用户", "领红包真实用户"];
            option.series = [
                {
                    name: '发红包',
                    type: 'line',
                    data: '',
                    itemStyle: {
                        normal: {
                            color: 'rgb(51, 105, 30)',
                        }
                    },
                },
                {
                    name: '领红包',
                    type: 'line',
                    data: ''
                },
                {
                    name: '发红包机器人',
                    type: 'bar',
                    data: ''
                },
                {
                    name: '发红包真实用户',
                    type: 'bar',
                    data: '',
                    itemStyle: {
                        normal: {
                            color: 'rgb(69, 90, 100)',
                        }
                    },
                },
                //{
                //    name: '领红包机器人',
                //    type: 'bar',
                //    data: ''
                //},
                {
                    name: '领红包真实用户',
                    type: 'bar',
                    data: '',
                    itemStyle: {
                        normal: {
                            color: 'rgb(93, 64, 55)',
                        }
                    },
                }
            ]
        }
        //红包个数
        else if (type == 'package_count') {
            theme = "green";
            option.legend.data = ["发红包", "领红包", "发红包机器人", "发红包真实用户", "领红包真实用户"];
            option.series = [
                {
                    name: '发红包',
                    type: 'line',
                    data: '',
                    itemStyle: {
                        normal: {
                            color: 'rgb(255, 193, 7)',
                        }
                    },
                },
                {
                    name: '领红包',
                    type: 'line',
                    data: '',
                    itemStyle: {
                        normal: {
                            color: 'rgb(63, 81, 181)',
                        }
                    }
                },
                {
                    name: '发红包机器人',
                    type: 'bar',
                    data: '',
                    itemStyle: {
                        normal: {
                            color: 'rgb(76, 175, 80)',
                        }
                    },
                },
                {
                    name: '发红包真实用户',
                    type: 'bar',
                    data: '',
                    itemStyle: {
                        normal: {
                            color: 'rgb(136, 14, 79)',
                        }
                    }
                },
                //{
                //    name: '领红包机器人',
                //    type: 'bar',
                //    data: ''
                //},
                {
                    name: '领红包真实用户',
                    type: 'bar',
                    data: '',
                    itemStyle: {
                        normal: {
                            color: 'rgb(156, 39, 176)',
                        }
                    }
                }
            ]
        }
        base.requestApi('/api/stat/squarePackage', {
            'type': type,
            'day': day,
            'start': start,
            'end': end
        }, function (res) {
            if (res.result == 1) {
                res.data.values = eval(res.data.values);
                option.xAxis[0].data = eval(res.data.labels);
                for (var i = 0; i < res.data.values.length; i++) {
                    option.series[i].data = eval(res.data.values[i]);
                }
                echarts.init(document.getElementById(type), theme).setOption(option);
                if (type == 'user') {
                    $(".user td.send").eq(0).html(res.data.day);
                    $(".user td.send").eq(1).html(res.data.total['send']);
                    $(".user td.send").eq(2).html((res.data.total['send'] / res.data.day).toFixed(0));

                    $(".user td.pick").eq(0).html(res.data.day);
                    $(".user td.pick").eq(1).html(res.data.total['pick']);
                    $(".user td.pick").eq(2).html((res.data.total['pick'] / res.data.day).toFixed(0));
                } else if (type == 'money') {
                    for (var i = 0; i < (res.data.total).length; i++) {
                        var item = $(".money td.cl" + i);
                        item.eq(0).html(res.data.day);
                        item.eq(1).html("￥" + res.data.total[i].toFixed(2));
                        item.eq(2).html("￥" + (res.data.total[i] / res.data.day).toFixed(2));
                        // $(".money td.cl" + i).html("￥" + (res.data.total[i].toFixed(2)));
                    }
                } else if (type == 'package_count') {
                    for (var j = 0; j < (res.data.total).length; j++) {
                        var item = $(".package_count td.cl" + j);
                        item.eq(0).html(res.data.day);
                        item.eq(1).html(res.data.total[j]);
                        item.eq(2).html((res.data.total[j] / res.data.day).toFixed(0));
                        //  $(".package_count td.cl" + j + ":eq(2)").html(res.data.total[j] / res.data.day);

                    }
                }
            }
        }, true, true);
    };
    exports.load = function () {
        exports.getData("user", 7, '', '');
        exports.getData("money", 7, '', '');
        exports.getData("package_count", 7, '', '');
        exports.filter();
        exports.getList("pick_count", 'today', '', '')
    }
});