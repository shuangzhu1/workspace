/**
 * Created by ykuang on 2017/12/15.
 */
define(function (require, exports) {
    var base = require('/srv/static/panel/js/app/panel/panel.base');
    require('/srv/static/panel/js/jquery/jquery.datetimepicker.js');
    require('/srv/static/panel/js/echarts/themes/pink.js');
    require('/srv/static/panel/js/echarts/themes/blue.js');
    require('/srv/static/panel/js/echarts/themes/green.js');
    require('/srv/static/panel/js/echarts/themes/purple.js');
    require('/srv/static/panel/js/app/panel/stat/pager.js');
    var myChart1, myChart2 = null;
    var package_type = '';//红包类型
    exports.complexLoad = function (type, day, start, end) {
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

        //按天来统计
        if (type == 'total') {
            //   $("#total").html("<div class='loading'><img src='/static/panel/images/admin/loading2.gif'/>&nbsp;&nbsp;数据加载中...</div>");
            base.requestApi('/api/stat/package', {
                day: day,
                start: start,
                end: end,
                type: 'total',
                package_type: package_type
            }, function (res) {
                if (res.result == 1) {
                    var union = '';//单元
                    option.series[0].name = '金额【元】';


                    option.xAxis[0].data = eval(res.data.labels);
                    option.series[0].data = eval(res.data.values);
                    $("." + type).html('【<a href="javascript::">' + union + res.data.count + '</a>】');
                    if (myChart1 == null) {
                        myChart1 = echarts.init(document.getElementById(type), 'blue');//.setOption(option);
                    }
                    myChart1.setOption(option);
                }
            }, true, true);
        }

    };
    exports.complex = function (pt) {

        package_type = pt != undefined ? pt : '';
        var day = 7;

        var start_total = '';
        var end_total = '';
        exports.complexLoad('total', 7, '', '');

        $(".trackable").on('click', function () {
            //按类型筛选
            if ($(this).hasClass('type')) {
                if (!$(this).hasClass('cur')) {
                    $(this).addClass('cur').siblings('.trackable').removeClass('cur');
                    exports.complexLoad('total', 7, '', '');
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
                        exports.complexLoad($(this).data('type'), $(this).data('id'));
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
                    exports.complexLoad('total', 'custom', start_total, end_total);
                }

            }
        });
    }

});