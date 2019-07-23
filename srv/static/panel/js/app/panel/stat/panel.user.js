define(function (require, exports) {
    var base = require('/srv/static/panel/js/app/panel/panel.base');
    require('/srv/static/panel/js/jquery/jquery.datetimepicker.js');
    require('/srv/static/panel/js/echarts/themes/purple.js');
    require('/srv/static/panel/js/echarts/themes/pink.js');
    require('/srv/static/panel/js/echarts/themes/blue.js');
    require('/srv/static/panel/js/echarts/themes/green.js');
    require('/srv/static/panel/js/echarts/map/china.js');

    var chart_type, chart_area, chart_device, chart_total = null;
    exports.complexLoad = function (type, day, start, end) {
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
        var circle_option = {
            title: {
                x: 'center'
            },
            tooltip: {
                trigger: 'item',
                formatter: "{a} <br/>{b} : {c} ({d}%)"
            },
            legend: {
                orient: 'vertical',
                x: 'left',
                padding: 20,
                data: []
            },
            toolbox: {
                show: true,
                feature: {
                    /*  mark: {show: true},
                     dataView: {show: true, readOnly: false},*/
                    magicType: {
                        show: true,
                        type: ['pie', 'funnel'],
                        option: {
                            funnel: {
                                x: '25%',
                                width: '50%',
                                funnelAlign: 'left',
                                max: 1548
                            }
                        }
                    },
                    /*  restore: {show: true},*/
                    saveAsImage: {show: true}
                },
                right: '20'
            },
            calculable: true,
            series: [
                {
                    name: '数量',
                    type: 'pie',
                    radius: '55%',
                    center: ['50%', '60%'],
                    data: []
                }
            ]
        };
        //总统计
        if (type == 'total') {
            //   $("#total").html("<div class='loading'><img src='/static/panel/images/admin/loading2.gif'/>&nbsp;&nbsp;数据加载中...</div>");
            base.requestApi('/srv/api/stat/userComplex', {
                type: type,
                day: day,
                start: start,
                end: end,
            }, function (res) {
                if (res.result == 1) {
                    option.xAxis[0].data = eval(res.data.labels);
                    option.series[0].data = eval(res.data.values);
                    $("." + type).html('【<a href="javascript::">' + res.data.count + '</a>】');
                    if (chart_total == null) {
                        chart_total = echarts.init(document.getElementById(type), 'green');
                    }
                    chart_total.setOption(option, true);
                }
            }, true, true);
        }
        //按设备统计
        else if (type == 'device') {
            //  $("#device").html("<div class='loading'><img src='/static/panel/images/admin/loading2.gif'/>&nbsp;&nbsp;数据加载中...</div>");
            base.requestApi('/srv/api/stat/userComplex', {
                type: type,
                day: day,
                start: start,
                end: end,
            }, function (res) {
                if (res.result == 1) {
                    circle_option.legend.data = eval(res.data.labels);
                    circle_option.series[0].data = eval(res.data.values);
                    if (chart_device == null) {
                        chart_device = echarts.init(document.getElementById(type), 'blue');
                    }
                    console.log(circle_option);
                    chart_device.setOption(circle_option, true);
                }
            }, true, true);
        }
        //按注册方式统计
        else if (type == 'type') {
            //  $("#type").html("<div class='loading'><img src='/static/panel/images/admin/loading2.gif'/>&nbsp;&nbsp;数据加载中...</div>");
            base.requestApi('/srv/api/stat/userComplex', {
                type: type,
                day: day,
                start: start,
                end: end,
            }, function (res) {
                if (res.result == 1) {
                    circle_option.legend.data = eval(res.data.labels);
                    circle_option.series[0].data = eval(res.data.values);
                    /*  echarts.init(document.getElementById(type), 'blue').setOption(circle_option);*/
                    if (chart_type == null) {
                        chart_type = echarts.init(document.getElementById(type), 'blue');
                    }
                    chart_type.setOption(circle_option, true);
                }
            }, true, true);
        }
        else if (type == 'area') {
            // $("#area").html("<div class='loading'><img src='/static/panel/images/admin/loading2.gif'/>&nbsp;&nbsp;数据加载中...</div>");
            var option_map = {
                title: {
                    text: '用户注册区域统计',
                    subtext: '',
                    x: 'center'
                },
                tooltip: {
                    trigger: 'item'
                },
                dataRange: {
                    min: 0,
                    max: 2500,
                    x: 'right',
                    y: 'bottom',
                    text: ['高', '低'],           // 文本，默认为数值文本
                    calculable: true
                },
                roamController: {
                    show: true,
                    x: 'right',
                    mapTypeControl: {
                        'china': true
                    }
                },
                series: [
                    {
                        name: '用户数',
                        type: 'map',
                        mapType: 'china',
                        roam: false,
                        itemStyle: {
                            normal: {label: {show: true}},
                            emphasis: {label: {show: true}}
                        },
                      /*  top:0,
                        bottom:0,*/
                        zoom:1.2,
                        data: ''
                    }
                ]
            };
            base.requestApi('/srv/api/stat/userComplex', {
                type: type,
                day: day,
                start: start,
                end: end
            }, function (res) {
                if (res.result == 1) {
                    option_map.series[0].data = eval(res.data.values);
                    if (chart_area == null) {
                        chart_area = echarts.init(document.getElementById(type), 'blue');
                    }
                    console.log(option_map);
                    chart_area.setOption(option_map, true);
                }
            }, true, true);
        }

        // });

    };
    exports.complex = function () {
        $(function () {
            exports.complexLoad('total', 'today');
            exports.complexLoad('device', 'today');
            exports.complexLoad('type', 'today');
            exports.complexLoad('area', 'yesterday');

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
                    exports.complexLoad('total', 'custom', $('#start').val(), $('#end').val());
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
                    exports.complexLoad('device', 'custom', $('#start1').val(), $('#end1').val());
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
                    exports.complexLoad('type', 'custom', $('#start2').val(), $('#end2').val());
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
                    exports.complexLoad('type', 'custom', $('#start3').val(), $('#end3').val());
                }
            });
            $(".trackable").on('click', function () {
                if (!$(this).hasClass('cur')) {
                    $(this).addClass('cur').siblings('.trackable').removeClass('cur');
                    if ($(this).data('id') == 'custom') {
                        $(this).parent().siblings(".time_filter").show();
                    } else {
                        exports.complexLoad($(this).data('type'), $(this).data('id'));
                        $(this).parent().siblings(".time_filter").hide();
                    }
                }
            });

        })
    }

});