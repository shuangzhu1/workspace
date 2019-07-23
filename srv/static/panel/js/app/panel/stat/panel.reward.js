define(function (require, exports) {
    var base = require('/srv/static/panel/js/app/panel/panel.base');
    require('/srv/static/panel/js/jquery/jquery.datetimepicker.js');
    require('/srv/static/panel/js/echarts/themes/pink.js');
    require('/srv/static/panel/js/echarts/themes/blue.js');
    require('/srv/static/panel/js/echarts/themes/green.js');
    require('/srv/static/panel/js/echarts/themes/purple.js');
    require('/srv/static/panel/js/app/panel/stat/pager.js');
    var myChart1, myChart2 = null;

    exports.complexLoad = function (type, reward_type, day, start, end) {
        //一条线
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
                /* feature: {
                 mark: {show: true},
                 magicType: {show: true, type: ['line', 'bar']},
                 saveAsImage: {show: true}
                 },*/
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
                    name: '金额【元】',
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
        //多条线
        var muti_option = {
            title: {
                text: '',
                subtext: ''
            },
            tooltip: {
                trigger: 'axis'
            },
            legend: {
                data: ['QQ', 'QQ空间', "朋友圈", '微信好友', '微博']
            },
            toolbox: {
                show: true,
                /*  feature: {
                 mark: {show: true},
                 dataView: {show: true, readOnly: false},
                 magicType: {show: true, type: ['line', 'bar']},
                 restore: {show: true},
                 saveAsImage: {show: true}
                 }*/
            },
            calculable: true,
            xAxis: [
                {
                    type: 'category',
                    boundaryGap: false,
                    data: '', /*['周一', '周二', '周三', '周四', '周五', '周六', '周日']*/
                },
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
                    name: 'QQ',
                    type: 'line',
                    data: ''
                },
                {
                    name: 'QQ空间',
                    type: 'line',
                    data: ''
                },
                {
                    name: '朋友圈',
                    type: 'line',
                    data: ''
                },
                {
                    name: '微信好友',
                    type: 'line',
                    data: ''
                },
                {
                    name: '微博',
                    type: 'line',
                    data: ''
                }
            ]
        };
        //按天来统计
        if (type == 'total') {
            //   $("#total").html("<div class='loading'><img src='/static/panel/images/admin/loading2.gif'/>&nbsp;&nbsp;数据加载中...</div>");
            $(".total_data").pager({
                'url': '/api/statlist/reward',
                params: {day: day, start: start, end: end, reward_type: reward_type, type: 'total'},
                limit: 5
            });
            base.requestApi('/api/stat/reward', {
                reward_type: reward_type,
                day: day,
                start: start,
                end: end,
                type: 'total'
            }, function (res) {
                if (res.result == 1) {
                    var union = '';//单元
                    if (reward_type == 'cash') {
                        option.series[0].name = '金额【元】';
                        union = '￥';
                    } else {
                        option.series[0].name = '龙豆数';
                    }


                    option.xAxis[0].data = eval(res.data.labels);
                    option.series[0].data = eval(res.data.values);
                    $("." + type).html('【<a href="javascript::">' + union + res.data.count + '</a>】');
                    if (myChart1 == null) {
                         myChart1 = echarts.init(document.getElementById(type), 'blue');//.setOption(option);
                    }
                    myChart1.setOption(option);
                    myChart1.on('click', function (d) {
                        console.log(8888);
                        if (d.componentType == 'series') {
                            $(".total_data").pager({
                                'url': '/api/statlist/reward',
                                params: {day: day, start: start, end: end, time: d.name, reward_type: reward_type},
                                limit: 5
                            });
                        }
                    });
                }
            }, true, true);
        }
        //按平台来统计
        else if (type == 'platform') {
            //  $("#device").html("<div class='loading'><img src='/static/panel/images/admin/loading2.gif'/>&nbsp;&nbsp;数据加载中...</div>");
            $(".platform_data").pager({
                'url': '/api/statlist/reward',
                params: {day: day, start: start, end: end, reward_type: reward_type, type: 'platform'},
                limit: 5
            });
            base.requestApi('/api/stat/reward', {
                reward_type: reward_type,
                day: day,
                start: start,
                end: end,
                type: 'platform'
            }, function (res) {
                if (res.result == 1) {
                    muti_option.xAxis[0].data = eval(res.data.labels);
                    res.data.values = eval(res.data.values);
                    muti_option.series[0].data = eval(res.data.values[0]);
                    muti_option.series[1].data = eval(res.data.values[1]);
                    muti_option.series[2].data = eval(res.data.values[2]);
                    muti_option.series[3].data = eval(res.data.values[3]);
                    muti_option.series[4].data = eval(res.data.values[4]);

                    var union = '';//单元
                    if (reward_type == 'cash') {
                        union = '￥';
                    }
                    $("." + type).html('【<a href="javascript::">' + union + res.data.count + '</a>】');
                    if (myChart2 == null) {
                        myChart2 = echarts.init(document.getElementById(type), 'blue');
                    }
                    myChart2.setOption(muti_option, true);
                    myChart2.on('click', function (d) {
                        if (d.componentType == 'series') {
                            $(".platform_data").pager({
                                'url': '/api/statlist/reward',
                                params: {
                                    day: day,
                                    start: start,
                                    end: end,
                                    reward_type: reward_type,
                                    type: 'platform',
                                    platform: d.seriesName
                                },
                                limit: 5
                            });
                            // console.log(d)
                        }
                    });
                }
            }, true, true);
        }

    };
    exports.complex = function () {

        var reward_type = 'cash';
        var day = 7;

        var start_total = '';
        var end_total = '';

        var start_platform = '';
        var end_platform = '';
        exports.complexLoad('total', reward_type, 7, '', '');
        exports.complexLoad('platform', reward_type, 7, '', '');

        $(".trackable").on('click', function () {
            //按类型筛选
            if ($(this).hasClass('type')) {
                if (!$(this).hasClass('cur')) {
                    $(this).addClass('cur').siblings('.trackable').removeClass('cur');
                    reward_type = $(this).data('id');
                    exports.complexLoad('total', reward_type, 7, '', '');
                    exports.complexLoad('platform', reward_type, 7, '', '');
                }
            }
            //按时间筛选
            else {
                if (!$(this).hasClass('cur')) {
                    $(this).addClass('cur').siblings('.trackable').removeClass('cur');
                    day = $(this).data('id');
                    if ($(this).data('id') == 'custom') {
                        $(this).parent().siblings(".time_filter").show();
                    } else {
                        exports.complexLoad($(this).data('type'), reward_type, $(this).data('id'));
                        $(this).parent().siblings(".time_filter").hide();
                    }
                }
            }

        });
        $('#start').datetimepicker({
            lang: "ch",
            step: 5,
            format: "Y-m-d",
            maxDate: 0,
            timepicker: false,
            onChangeDateTime: function (e) {
                if ($('#start').val() != start_total) {
                    start_total = $('#start').val();
                    end_total = '';
                    $('#end').val("").datetimepicker({minDate: $('#start').val().replace(/-/g, '/')}).focus();
                }

            }
        });
        $('#end').datetimepicker({
            lang: "ch",
            step: 5,
            format: "Y-m-d",
            maxDate: 0,
            timepicker: false,
            onChangeDateTime: function (e) {
                if (end_total != $('#end').val()) {
                    end_total = $('#end').val();
                    exports.complexLoad('total', reward_type, 'custom', start_total, end_total);
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
                if ($('#start1').val() != start_platform) {
                    start_platform = $('#start1').val();
                    end_platform = '';
                    $('#end1').val("").datetimepicker({minDate: $('#start1').val().replace(/-/g, '/')}).focus();
                }

            }
        });
        $('#end1').datetimepicker({
            lang: "ch",
            step: 5,
            format: "Y-m-d",
            maxDate: 0,
            timepicker: false,
            onChangeDateTime: function (e) {
                if (end_platform != $('#end1').val()) {
                    end_platform = $('#end1').val();
                    exports.complexLoad('platform', reward_type, 'custom', start_platform, end_platform);
                }

            }
        });
    }

});