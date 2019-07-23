define(function (require, exports) {
    var base = require('/srv/static/panel/js/app/panel/panel.base');
    require('/srv/static/panel/js/jquery/jquery.datetimepicker.js');
    require('/srv/static/panel/js/echarts/themes/pink.js');
    require('/srv/static/panel/js/echarts/themes/blue.js');
    require('/srv/static/panel/js/echarts/themes/green.js');
    require('/srv/static/panel/js/echarts/themes/purple.js');
    require('/srv/static/panel/js/app/panel/stat/pager.js');

    var day = 'today'; //日期
    var start = ''; //开始
    var end = '';   //结束

    var active_day = '7';//活跃群聊筛选-日期
    var active_start = '';//活跃群聊筛选-开始
    var active_end = '';//活跃群聊筛选-结束

    var chart_total = null;
    var complex_total, complex_activeGroup = null;
    exports.load = function () {
        var option = {
            title: {
                text: '',
                subtext: ''
            },
            tooltip: {
                trigger: 'axis'
            },
            toolbox: {
                show: true,
                feature: {
                    mark: {show: true},
                    magicType: {show: true, type: ['line', 'bar']},
                    saveAsImage: {show: true}
                },
                right: '50'
            },
            calculable: true,
            xAxis: [
                {
                    type: 'category',
                    boundaryGap: false,
                    data: '', /*['周一', '周二', '周三', '周四', '周五', '周六', '周日']*/
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
                    name: '数量',
                    type: 'line',
                    data: '', /*[50, 2, 30, 1, 80, 1, 100]*/
                    markPoint: {
                        data: [
                            {type: 'max', name: '最大值'},
                            {type: 'min', name: '最小值'}
                        ]
                    }
                }
            ]
        };
        /*     myChart_total.setOption(total_option);
         myChart_discuss.setOption(total_option);
         myChart_user.setOption(total_option);
         myChart_invite.setOption(total_option);*/
        // $("#total").html("<div class='loading'><img src='/static/panel/images/admin/loading2.gif'/>&nbsp;&nbsp;数据加载中...</div>")
        $("#total").siblings(".loading").show();
        $("#total").hide();
        $(".total_data").pager({
            'url': '/srv/api/statlist/group',
            params: {day: day, start: start, end: end, 'type': 'total'},
            limit: 10
        });
        base.requestApi('/srv/api/stat/group', {day: day, start: start, end: end}, function (res) {
            if (res.result == 1) {
                option.xAxis[0].data = eval(res.data.labels);
                option.series[0].data = eval(res.data.values);
                $(".total").html('【<a href="javascript::">' + res.data.count + '</a>】');
                $("#total").siblings(".loading").hide();
                $("#total").show();
                if (chart_total == null) {
                    chart_total = echarts.init(document.getElementById('total'), 'blue');
                }
                chart_total.setOption(option);
                chart_total.on('click', function (d) {
                    if (d.componentType == 'series') {
                        $(".total_data").pager({
                            'url': '/api/statlist/group',
                            params: {day: day, start: start, end: end, time: d.name},
                            limit: 5
                        });
                    }
                });
            }
        }, true, true);
    };
    //首页统计
    exports.singleWelcome = function (type, day) {
        var option = {
            title: {
                text: '',
                subtext: ''
            },
            tooltip: {
                trigger: 'axis'
            },
            toolbox: {
                show: true,
                feature: {
                    mark: {show: true},
                    magicType: {show: true, type: ['line', 'bar']},
                    saveAsImage: {show: true}
                },
                right: '50'
            },
            calculable: true,
            xAxis: [
                {
                    type: 'category',
                    boundaryGap: false,
                    data: '', /*['周一', '周二', '周三', '周四', '周五', '周六', '周日']*/
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
                    name: '数量',
                    type: 'line',
                    data: '', /*[50, 2, 30, 1, 80, 1, 100]*/
                    markPoint: {
                        data: [
                            {type: 'max', name: '最大值'},
                            {type: 'min', name: '最小值'}
                        ]
                    }
                }
            ]
        };

        base.requestApi('/srv/api/stat/group', {day: day, start: start, end: end}, function (res) {
            // $("#" + type).html("<div class='loading'><img src='/static/panel/images/admin/loading2.gif'/>&nbsp;&nbsp;数据加载中...</div>");
            if (res.result == 1) {
                option.xAxis[0].data = eval(res.data.labels);
                option.series[0].data = eval(res.data.values);
                $("." + type).html('【<a href="javascript::">' + res.data.count + '</a>】');
                var myChart = echarts.init(document.getElementById(type), 'green');
                myChart.setOption(option);
            }
        }, true, true);

    };
    exports.complexLoad = function (type) {
        var option = {
            title: {
                text: '',
                subtext: ''
            },
            tooltip: {
                trigger: 'axis'
            },
            toolbox: {
                show: true,
                feature: {
                    mark: {show: true},
                    magicType: {show: true, type: ['line', 'bar']},
                    saveAsImage: {show: true}
                },
                right: '50'
            },
            calculable: true,
            xAxis: [
                {
                    type: 'category',
                    boundaryGap: false,
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
                    name: '数量',
                    type: 'line',
                    data: '',
                    markPoint: {
                        data: [
                            {type: 'max', name: '最大值'},
                            {type: 'min', name: '最小值'}
                        ]
                    }
                }
            ]
        };

        if (type == 'total') {
            $("#total").siblings(".loading").show();
            $("#total").hide();
            //$("#total").html("<div class='loading'><img src='/static/panel/images/admin/loading2.gif'/>&nbsp;&nbsp;数据加载中...</div>");
            $(".total_data").pager({
                'url': '/srv/api/statlist/group',
                params: {day: day, start: start, end: end},
                limit: 5
            });
            base.requestApi('/srv/api/stat/groupComplex', {
                type: 'total',
                day: day,
                start: start,
                end: end,
            }, function (res) {
                if (res.result == 1) {
                    option.xAxis[0].data = eval(res.data.labels);
                    option.series[0].data = eval(res.data.values);
                    $(".total_count").html('【<a href="javascript::">' + res.data.count + '</a>】');
                    $("#total").siblings(".loading").hide();
                    $("#total").show();
                    if (complex_total == null) {
                        complex_total = echarts.init(document.getElementById('total'), 'blue');
                    }
                    complex_total.setOption(option);
                    complex_total.on('click', function (d) {
                        if (d.componentType == 'series') {
                            $(".total_data").pager({
                                'url': '/api/statlist/group',
                                params: {day: day, start: start, end: end, time: d.name},
                                limit: 5
                            });
                        }
                    });
                }
            }, true, true);
        } else if (type == 'active_group') {
            $("#active_group").siblings(".loading").show();
            $("#active_group").hide();
            //  $("#active_group").html("<div class='loading'><img src='/static/panel/images/admin/loading2.gif'/>&nbsp;&nbsp;数据加载中...</div>");
            $(".active_data").pager({
                'url': '/srv/api/statlist/group',
                params: {day: active_day, start: active_start, end: active_end, type: 'active'},
                limit: 5
            });
            base.requestApi('/srv/api/stat/groupComplex', {
                type: "active_group",
                day: active_day,
                start: active_start,
                end: active_end,
            }, function (res) {
                if (res.result == 1) {
                    option.xAxis[0].data = eval(res.data.labels);
                    option.series[0].data = eval(res.data.values);
                    $(".active_count").html('【<a href="javascript::">' + res.data.count + '</a>】');
                    $("#active_group").siblings(".loading").hide();
                    $("#active_group").show();
                    if(complex_activeGroup==null){
                        complex_activeGroup=echarts.init(document.getElementById('active_group'), 'green');;
                    }
                    complex_activeGroup.setOption(option);
                    complex_activeGroup.on('click', function (d) {
                        if (d.componentType == 'series') {
                            $(".active_data").pager({
                                'url': '/api/statlist/group',
                                params: {
                                    day: active_day,
                                    start: active_start,
                                    end: active_end,
                                    time: d.name,
                                    type: 'active'
                                },
                                limit: 5
                            });
                        }
                    });
                }
            }, true, true);
        }
    };
    exports.single = function () {
        $(function () {
            exports.load();
            $(".trackable").on('click', function () {
                if (!$(this).hasClass('current')) {
                    $(this).addClass('current').siblings('.trackable').removeClass('current')
                    day = $(this).data('id');
                    exports.load();
                }
            })
        })
    };
    exports.complex = function () {
        $(function () {
            exports.complexLoad('total');
            exports.complexLoad('active_group');

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
                    start = $('#start').val();
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
                    if (end !== $('#end').val()) {
                        end = $('#end').val();
                        day = 'custom';
                        exports.complexLoad('total');
                    }
                }
            });
            $('#start1').datetimepicker({
                lang: "ch",
                step: 5,
                format: "Y-m-d",
                maxDate: 0,
                timepicker: false,
                onChangeDateTime: function (e) {
                    active_start = $('#start1').val();
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
                    if (active_end !== $('#end1').val()) {
                        active_end = $('#end1').val();
                        active_day = 'custom';
                        exports.complexLoad('active_group');
                    }
                }
            });
            $(".trackable").on('click', function () {
                if (!$(this).hasClass('cur')) {
                    $(this).addClass('cur').siblings('.trackable').removeClass('cur');
                    if ($(this).data('type') == 'total') {
                        day = $(this).data('id');
                    } else {
                        active_day = $(this).data('id');
                    }

                    if ($(this).data('id') == 'custom') {
                        $(this).parent().siblings(".time_filter").show();
                    } else {
                        exports.complexLoad($(this).data('type'));
                        $(this).parent().siblings(".time_filter").hide();
                    }
                }
            });

        })
    }

});