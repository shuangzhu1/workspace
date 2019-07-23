define(function (require, exports) {
    //趋势图
    var base = require('/srv/static/panel/js/app/panel/panel.base');
    require('/srv/static/panel/js/jquery/jquery.datetimepicker.js');
    require('/srv/static/panel/js/echarts/themes/pink.js');
    require('/srv/static/panel/js/echarts/themes/blue.js');
    require('/srv/static/panel/js/echarts/themes/green.js');
    require('/srv/static/panel/js/echarts/themes/purple.js');
    exports.complexLoad = function (type,start, end) {
        var option = {
            title: {
                text: '',
                subtext: ''
            },
            legend : {
                data:[]
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
            dataZoom: [{
                type: 'inside',
                start: 0,
                end: 100
            }, {
                start: 0,
                end: 100,
                handleIcon: 'M10.7,11.9v-1.3H9.3v1.3c-4.9,0.3-8.8,4.4-8.8,9.4c0,5,3.9,9.1,8.8,9.4v1.3h1.3v-1.3c4.9-0.3,8.8-4.4,8.8-9.4C19.5,16.3,15.6,12.2,10.7,11.9z M13.3,24.4H6.7V23h6.6V24.4z M13.3,19.6H6.7v-1.4h6.6V19.6z',
                handleSize: '80%',
                handleStyle: {
                    color: '#fff',
                    shadowBlur: 3,
                    shadowColor: 'rgba(0, 0, 0, 0.6)',
                    shadowOffsetX: 2,
                    shadowOffsetY: 2
                }
            }],
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
                subtext: '',
                subtextStyle:{
                    color:'#fffff',
                    fontSize:13,
                    fontWeight:'bold'

                },
                x: 'center',
                textAlign:'left'
            },
            tooltip: {
                trigger: 'item',
                formatter: "{a} <br/>{b} : {c} ({d}%)"
            },
            legend: {
                orient: 'vertical',
                x: 'left',
                padding:20,
                data: [],
                selected:{}
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
        //发送ajax请求
        $("#redbag_total_money").html("<div class='loading'><img src='/srv/static/panel/images/admin/loading2.gif'/>&nbsp;&nbsp;数据加载中...</div>");
        base.requestApi('/api/stat/promo', {
            type: type,
            start: start,
            end: end,
        }, function (res) {
            if (res.result == 1) {
                option.legend.data = eval(res.data.labels);
                option.xAxis[0].data = eval(res.data.days);
                option.series = eval(res.data.values);
                if(type != 'user')//推广花费
                {
                    option.legend.selected = {
                        '新注册用户':false,
                        '激活用户':false,
                        '总花费':true,
                        '一级花费':true,
                        '二级花费':true,
                        '三级花费':true
                    };
                }else//推广成效
                {

                    option.legend.selected = {
                        '新注册用户':true,
                        '激活用户':true,
                        '总花费':false,
                        '一级花费':false,
                        '二级花费':false,
                        '三级花费':false
                    };
                }
                echarts.init(document.getElementById('chart_area'),'blue').setOption(option);
                $('.start').datetimepicker({
                    lang: "ch",
                    step: 5,
                    format: "Y/m/d",
                    minDate:res.data.range.begin,
                    maxDate: res.data.range.end,
                    timepicker: false,
                    onChangeDateTime: function (e) {

                    }
                });
                $('.end').datetimepicker({
                    lang: "ch",
                    step: 5,
                    format: "Y/m/d",
                    minDate:res.data.range.begin,
                    maxDate: res.data.range.end,
                    timepicker: false,
                    onChangeDateTime: function (e) {

                    }
                });
                //更新汇总数据
                $('#range').html((start == undefined ? res.data.range.begin : start) + ' - ' + (end == undefined ? res.data.range.end : end));
                $('.item div:eq(0) span:last-child').html(res['data']['summary']['form']['new_user']);
                $('.item div:eq(1) span:last-child').html(res['data']['summary']['form']['activate_user']);
                $('.item div:eq(2) span:last-child').html(((res['data']['summary']['form']['activate_user'] / res['data']['summary']['form']['new_user']) * 100).toFixed(2)  + '%');
                $('.item div:eq(3) span:last-child').html(res['data']['summary']['form']['level1_cost'] / 100);
                $('.item div:eq(4) span:last-child').html(res['data']['summary']['form']['level2_cost'] / 100);
                $('.item div:eq(5) span:last-child').html(res['data']['summary']['form']['level3_cost'] / 100);
                $('.item div:eq(6) span:last-child').html(res['data']['summary']['form']['total_cost'] / 100);

            }
        }, true, true);
    };
    exports.complex = function () {
        $(function () {
            exports.complexLoad('user');
            $(".trackable").on('click', function () {
                if (!$(this).hasClass('cur')) {
                    $(this).addClass('cur').siblings('.trackable').removeClass('cur');
                    if ($(this).data('id') == 'custom') {
                        $(this).parent().siblings(".time_filter").show();
                    } else {
                        var type = '';
                        $('.promo_type a').each(function () {
                            if( $(this).hasClass('cur') )
                                type = $(this).attr('data-type');

                        })
                        exports.complexLoad(type);
                        $(this).parent().siblings(".time_filter").hide();
                    }
                }
            });

            $(".promo_type").on('click', function () {
                if (!$(this).hasClass('cur')) {
                    $('.promo_type').each(function () {
                        if($(this).hasClass('cur'))
                        {
                            $(this).removeClass('cur');
                            return false;
                        }

                    })
                    $(this).addClass('cur');
                    var type = $(this).attr('data-type')
                    exports.complexLoad(type);

                }
            });
            $('#chart_area_send').on('click',function () {
                var ele = $(this).closest('div'),
                    start = $(ele).find('.start').val(),
                    end = $(ele).find('.end').val(),
                    type = '';
                $('.promo a').each(function () {
                    if( $(this).hasClass('cur') )
                        type = $(this).attr('data-type');

                })
                if( start == '' || end == '')
                {

                    alert('请选择查询起始日期！！');
                    return;
                }
                if( start > end )
                {
                    alert('起始时间大于结束时间！');
                    return;
                }

                exports.complexLoad(type, start, end);
            })
        })
    }

});
