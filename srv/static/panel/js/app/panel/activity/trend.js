define(function (require,exports) {
    var base = require('app/panel/panel.base');
    var ecConfig = require('echarts/config');

    //时间范围选择按钮
    $(".trackable").on('click', function () {
        if (!$(this).hasClass('cur')) {
            $(this).addClass('cur').siblings('.trackable').removeClass('cur');
            if ($(this).data('id') == 'custom') {
                $(this).parent().siblings(".time_filter").show();
            } else {
                var start,end;
                var date_type = $(this).data('id');
                var now  = new Date();
                var yesterday = new Date(now.setDate( now.getDate() - 1 ));
                end =  yesterday.getFullYear() + '/' + (yesterday.getMonth() + 1) + '/' + yesterday.getDate();

                if( date_type == 7 )
                {
                    var theStartDay = new Date( yesterday.setDate( yesterday.getDate() - 6 ));
                    start = theStartDay.getFullYear() + '/' + ( theStartDay.getMonth() + 1) + '/' + theStartDay.getDate();
                }else if( date_type == 30 )
                {
                    var theStartDay = new Date( yesterday.setDate( yesterday.getDate() - 29 ));
                    start = theStartDay.getFullYear() + '/' + ( theStartDay.getMonth() + 1) + '/' + theStartDay.getDate();
                }
                exports.getData($(this).parent().data('type'), start, end);
                $(this).parent().siblings(".time_filter").hide();
            }
        }
    });

    //自定义区间确认按钮
    $('.custom-date-ok').on('click',function () {
        var start,end;
        start = $(this).closest('div').find('.start').val();
        end = $(this).closest('div').find('.end').val();
        exports.getData($(this).closest('div').data('type'),start,end)
    });
    exports.getData = function (type,start,end) { //获取图表所需数据
        var option = {
            title: {
                text: '',
                subtext: ''
            },
            legend: {
                data:['所有','红包雨','知识问答']
            },
            tooltip: {
                trigger: 'axis'
            },
            toolbox: {
                show: true,
                feature: {
                    /*mark: {show: true},
                    magicType: {show: true, type: ['line', 'bar']},*/
                    saveAsImage: {show: true}
                },
                right: '50'
            },
            calculable: true,
            xAxis: [
                {
                    type: 'category',
                    data: ''
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
                    name: '',
                    type: 'line',
                    data: '', /*[50, 2, 30, 1, 80, 1, 100]*/
                    z:3,
                    markPoint: {
                        data: [
                            {type: 'max', name: '最大值'},
                            {type: 'min', name: '最小值'}
                        ]
                    }
                },
                {
                    name: '',
                    type: 'bar',
                    stack: '红包雨+知识问答',
                    z: 2,
                    data: ''
                },
                {
                    name: '',
                    type: 'bar',
                    stack: '红包雨+知识问答',
                    z: 1,
                    data: ''
                }
            ]
        };

        switch( type )
        {
            case 'join_user':
                base.requestApi('/api/activity/trend',{type:type,start:start,end:end},function (res) {
                    option.xAxis[0].data = res.data.xAxis;
                    option.yAxis[0].axisLabel.formatter = '{value} 个';
                    option.series[0].name = '所有';
                    option.series[0].data = res.data.total.value;
                    option.series[1].name = '红包雨';
                    option.series[1].data = res.data.redbag.value;
                    option.series[2].name = '知识问答';
                    option.series[2].data = res.data.qa.value;
                    echarts.init(document.getElementById(type),'macarons').setOption(option,true);
                },true,true);
                break;
            case 'platform_income':
                base.requestApi('/api/activity/trend',{type:type,start:start,end:end},function (res) {
                    option.xAxis[0].data = res.data.xAxis;
                    option.yAxis[0].axisLabel.formatter = '{value} 元';
                    option.series[0].name = '所有';
                    option.series[0].data = res.data.total.value;
                    option.series[1].name = '红包雨';
                    option.series[1].data = res.data.redbag.value;
                    option.series[2].name = '知识问答';
                    option.series[2].data = res.data.qa.value;
                    echarts.init(document.getElementById(type),'macarons').setOption(option,true);
                },true,true);
                break;
            case 'ac_count':
                base.requestApi('/api/activity/trend',{type:type,start:start,end:end},function (res) {
                    option.xAxis[0].data = res.data.xAxis;
                    option.yAxis[0].axisLabel.formatter = '{value} 次';
                    option.series[0].name = '所有';
                    option.series[0].data = res.data.total.value;
                    option.series[1].name = '红包雨';
                    option.series[1].data = res.data.redbag.value;
                    option.series[2].name = '知识问答';
                    option.series[2].data = res.data.qa.value;
                    echarts.init(document.getElementById(type),'macarons').setOption(option,true);
                },true,true);
                break;
            case 'ac_money':
                base.requestApi('/api/activity/trend',{type:type,start:start,end:end},function (res) {
                    option.xAxis[0].data = res.data.xAxis;
                    option.yAxis[0].axisLabel.formatter = '{value} 元';
                    option.series[0].name = '所有';
                    option.series[0].data = res.data.total.value;
                    option.series[1].name = '红包雨';
                    option.series[1].data = res.data.redbag.value;
                    option.series[2].name = '知识问答';
                    option.series[2].data = res.data.qa.value;
                    echarts.init(document.getElementById(type),'macarons').setOption(option,true);
                },true,true);
                break;
            case 'ac_count_platform':
                base.requestApi('/api/activity/trend',{type:type,start:start,end:end},function (res) {
                    option.xAxis[0].data = res.data.xAxis;
                    option.yAxis[0].axisLabel.formatter = '{value} 次';
                    option.series[0].name = '所有';
                    option.series[0].data = res.data.total.value;
                    option.series[1].name = '红包雨';
                    option.series[1].data = res.data.redbag.value;
                    option.series[2].name = '知识问答';
                    option.series[2].data = res.data.qa.value;
                    echarts.init(document.getElementById(type),'macarons').setOption(option,true);
                },true,true);
                break;
            case 'ac_money_platform':
                base.requestApi('/api/activity/trend',{type:type,start:start,end:end},function (res) {
                    option.xAxis[0].data = res.data.xAxis;
                    option.yAxis[0].axisLabel.formatter = '{value} 元';
                    option.series[0].name = '所有';
                    option.series[0].data = res.data.total.value;
                    option.series[1].name = '红包雨';
                    option.series[1].data = res.data.redbag.value;
                    option.series[2].name = '知识问答';
                    option.series[2].data = res.data.qa.value;
                    echarts.init(document.getElementById(type),'macarons').setOption(option,true);
                },true,true);
                break;
            default:
                base.tip.showTip('err','参数错误',2000);
        }


    };

    exports.getData('join_user');
    exports.getData('platform_income');
    exports.getData("ac_count");
    exports.getData("ac_money");
    exports.getData("ac_count_platform");
    exports.getData("ac_money_platform");
    //选择活动类型
    $('#ac_type').on('change',function () {
        exports.getData($('#ac_type').val())
    });
    //选择日期
    $('#query').on('click',function(){
        exports.getData($('#ac_type').val());
    });


});