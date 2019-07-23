define(function (require,exports,module) {
    var base = require('app/panel/panel.base');

    //日期插件
    $('.start,.end').datetimepicker({
        lang: "ch",
        step: 5,
        format: "Y/m/d",
        maxDate: moment().subtract(1,'days').format('YYYY/MM/DD'),
        minDate: '2018/01/03',
        timepicker: false,
        allowBlank: true
    });
    //自定义时间，查询按钮
    $('.query-btn').click(function(){
        exports.reload();
    });
    //切换按钮
    $('.control-bar > div > a').on('click',function () {
        var _this = this;
        var $cur = $(_this).closest('div').children('a.cur');
        if( $cur.is(_this))//点击激活对象直接返回
            return false;
        $cur.removeClass('cur');
        $(_this).addClass('cur');
        //显示、隐藏自定义日期
        if( $(_this).closest('div').hasClass('date-btn') )
        {
            if( $(_this).attr('data-day') === 'custom' )
            {
                $(_this).closest('div').siblings('div.date-selected').hide();
                $(_this).closest('div').siblings('div.time_filter').show();
            }else
            {
                $(_this).closest('div').siblings('div.time_filter').hide();
                $(_this).closest('div').siblings('div.date-selected').show();
                exports.reload();
            }
        }else
            exports.reload();

    });

    //echarts配置
    var option_line = {
        title : {
            text: ''
        },
        tooltip : {
            trigger: 'axis'
        },
        legend: {
            data:[]
        },
        toolbox: {
            show : true,
            feature : {
                saveAsImage : {show: true}
            }
        },
        calculable : false,
        xAxis : [
            {
                type : 'category',
                boundaryGap : false,
                data : []
            }
        ],
        yAxis : [
            {
                type : 'value',
                axisLabel : {
                    formatter: '{value} 个'
                }
            }
        ],
        series : [
            {
                name:'',
                type:'line',
                data:[],
                smooth:true,
                markPoint : {
                    data : [
                        {type : 'max', name: '最大值'},
                        /*{type : 'min', name: '最小值'}*/
                    ]
                }
                /*
                markLine : {
                    data : [
                        {type : 'average', name: '平均值'}
                    ]
                }*/
            }
        ]
    };
    var option_pie = {
        title : {
            text: '',
            x:'center'
        },
        tooltip : {
            trigger: 'item',
            formatter: "{a} <br/>{b} : {c} ({d}%)"
        },
        legend: {
            orient : 'vertical',
            x : 'left',
            data:[]
        },
        toolbox: {
            show : true,
            feature : {
                saveAsImage : {show: true}
            }
        },
        calculable : false,
        series : [
            {
                name:'各渠道产生占比',
                type:'pie',
                radius : '55%',
                center: ['50%', '45%'],
                data:[]
            }
        ]
    };

    exports.reload = function () {
        //获取基础参数
        var type = $('.type-btn').find('a.cur').attr('data-type');
        var date_type = $('.date-btn').find('a.cur').attr('data-day');
        var begin  = moment().subtract(1,'days').format('YYYYMMDD');
        var end = begin;
        if( parseInt(date_type) === 3 )
        {
            begin = moment().subtract(3,'days').format('YYYYMMDD');
        }
        if( parseInt(date_type) === 7 )
        {
            begin = moment().subtract(7,'days').format('YYYYMMDD');
        }
        if( parseInt(date_type) === 30 )
        {
            begin = moment().subtract(30,'days').format('YYYYMMDD');
        }
        if( isNaN(parseInt(date_type)) )
        {
            var $timePicker = $('.time_filter');
            var s = $timePicker.find('.start').val();
            var e = $timePicker.find('.end').val();
            if( $.trim(s) !== '' )
                begin = moment(s).format('YYYYMMDD');
            else
                begin = '';
            if( $.trim(e) !== '' )
                end = moment(e).format('YYYYMMDD');
            else
                end = '';
        }
        /*$('#income-line,#income-pie,#income-defray-line').prev().each(function () {
            $(this).isHid;
        });*/
        base.requestApi('/api/stat/virtualCoin',{type:type,begin:begin,end:end},function (res) {
            if( res.result === 1 )
            {
                var data = res.data;
                //显示当前统计范围
                $('.date-selected').find('span').eq(1).text(data.range.begin);
                $('.date-selected').find('span').eq(2).text(data.range.end);
                //填充图表数据
                var from = [
                    {
                    'alipay':'支付宝购买',
                    'cash':'余额充值',
                    'ios':'iOS内购',
                    'wechat':'微信充值',
                    'public_account':'公众号充值',
                    'system_reward':'系统奖励'
                    },
                    {
                        'activity':'活动',
                        'square_redbag':'广场红包',

                    }
                ];
                var legend_data = [
                    ['支付宝购买','余额充值','iOS内购','微信充值','公众号充值','系统奖励'],
                    ['活动','广场红包']
                ];
                var chart_option_1 = $.extend(true,{},option_line);
                chart_option_1.legend.data = legend_data[type];
                chart_option_1.xAxis[0].data = data.days;
                if( parseInt(type) === 1)
                    chart_option_1.yAxis[0].axisLabel.formatter = '{value} 元';
                var i = 0;
                for( var x in data.income )
                {
                    if( x === 'items')
                        continue;
                    if( i !== 0)
                        chart_option_1.series[i] = $.extend(true,{},chart_option_1.series[0]);

                    chart_option_1.series[i].name = from[type][x];
                    chart_option_1.series[i].data = data.income[x].items;
                    delete chart_option_1.series[i].markPoint;
                    delete chart_option_1.series[i].markLine;
                    i++;
                }
                var chart_option_2 = $.extend(true,{},option_pie);
                chart_option_2.legend.data = legend_data[type];
                for ( var y in data.income )
                {
                    if( y === 'items' )
                        continue;
                    chart_option_2.series[0].data.push({value:data.income[y].total,name:from[type][y]});
                }

                var chart_option_3 = $.extend(true,{},option_line);
                chart_option_3.legend.data = ['产生','消费'];
                chart_option_3.xAxis[0].data = data.days;
                if( parseInt(type) === 1)
                    chart_option_3.yAxis[0].axisLabel.formatter = '{value} 元';
                chart_option_3.series[0].name = '产生';
                chart_option_3.series[0].data = data.income.items;
                chart_option_3.series[0].itemStyle = {normal: {areaStyle: {type: 'default'}}};
                chart_option_3.series[1] = $.extend(true,{},chart_option_3.series[0]);
                chart_option_3.series[1].name = '消费';
                chart_option_3.series[1].data = data.defray.items;
                chart_option_3.series[1].itemStyle = {normal: {areaStyle: {type: 'default'}}};
                var echart1 = echarts.init(document.getElementById('income-line'));
                echart1.setOption(chart_option_1);
                var echart2 = echarts.init(document.getElementById('income-pie'));
                echart2.setOption(chart_option_2);
                var echart3 = echarts.init(document.getElementById('income-defray-line'));
                echart3.setOption(chart_option_3);

                //汇总数据
                if( parseInt(type) === 1)
                    $('.summary-box .income-total').text(data.total.income).next().text('元');
                else
                    $('.summary-box .income-total').text(data.total.income).next().text('个');

                if( parseInt(type) === 1)
                    $('.summary-box .defray-total').text(data.total.defray).next().text('元');
                else
                    $('.summary-box .defray-total').text(data.total.defray).next().text('个');


            }else
            {
                tip.showTip('err','获取数据失败',1000);
            }
        },true,true);
    }

});