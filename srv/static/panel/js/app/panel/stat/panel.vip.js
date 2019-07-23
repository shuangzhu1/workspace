define(function (require, exports) {
    var base = require('app/panel/panel.base');
    $('.loading').show();

    var option_line = {
        title : {
            text: '',
        },
        tooltip : {
            trigger: 'axis',
            formatter:'{a}<br>{c}%'
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
        calculable : true,
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
                    formatter: '{value}'
                }
            }
        ],
        series : [
            {
                name:'',
                type:'line',
                data:[],
                markPoint : {
                    data : [
                        {type : 'max', name: '最大值'},
                        {type : 'min', name: '最小值'}
                    ]
                },
                markLine : {
                    data : [
                        {type : 'average', name: '平均值'}
                    ]
                }
            }
        ]
    };
    var option_bar = {
        title : {
            text: '',
        },
        tooltip : {
            trigger: 'axis',
            
        },
        legend: {
            data:['']
        },
        toolbox: {
            show : true,
            feature : {
                saveAsImage : {show: true}
            }
        },
        calculable : true,
        xAxis : [
            {
                type : 'category',
                data : []
            }
        ],
        yAxis : [
            {
                type : 'value',
                formatter:'{value}个'
            }
        ],
        series : [
            {
                name:'',
                type:'bar',
                data:[],
                stack:'总量'
            }
        ]
    };

    var option_pie = {
        title : {
            text: ''
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
                name:'',
                type:'pie',
                radius : '55%',
                center: ['50%', '60%'],
                data:[

                ]
            }
        ]
    };

    $('#query').on('click',function(){
        exports.reload();
    });
    exports.reload = function(){
        var start = $('.start').val().replace(/\//g,'');
        var end = $('.end').val().replace(/\//g,'');
        var data = {};
        if( start !== '')
            data.start = start;
        if( end !== '')
            data.end = end;
        if( start > end)
        {
            tip.showTip('err','统计开始时间不应大于结束时间',2000);
            return;
        }
        base.requestApi('/api/stat/vip',data,function(res){
            if( res.result === 1 )
            {
                $('.loading').hide();
                //付费率
                var option_line1 = $.extend(true,{},option_line);
                option_line1.legend.data = ['付费率'];
                option_line1.xAxis[0].data = res.data.purchase.line.day;
                option_line1.yAxis[0].axisLabel.formatter = '{value} %';
                option_line1.series[0].name ='付费率';
                option_line1.series[0].data =res.data.purchase.line.data;
                echarts.init(document.getElementById('purchase-rate'),'macarons').setOption(option_line1,true);
                //点击转化率
                var option_line2 = $.extend(true,{},option_line);
                option_line2.legend.data = ['转化率'];
                option_line2.xAxis[0].data = res.data.click.line.day;
                option_line2.yAxis[0].axisLabel.formatter = '{value} %';
                option_line2.series[0].name ='转化率';
                option_line2.series[0].data =res.data.click.line.data;
                echarts.init(document.getElementById('click-rate'),'macarons').setOption(option_line2,true);
                //购买时长详细
                var option_bar1 = $.extend(true,{},option_bar);
                option_bar1.legend.data = ['1个月','3个月','6个月'];
                option_bar1.xAxis[0].data = res.data.vip_len.line.day;
                option_bar1.yAxis[0].formatter = '{value} 个';
                option_bar1.series[0].name ='1个月';
                option_bar1.series[0].data = res.data.vip_len.line.data[0];
                option_bar1.series[1] = $.extend(true,{},option_bar1.series[0]);
                option_bar1.series[1].name ='3个月';
                option_bar1.series[1].data = res.data.vip_len.line.data[1];
                option_bar1.series[2] = $.extend(true,{},option_bar1.series[0]);
                option_bar1.series[2].name ='6个月';
                option_bar1.series[2].data = res.data.vip_len.line.data[2];
                echarts.init(document.getElementById('purchase-length-detail'),'macarons').setOption(option_bar1,true);



                //购买时长总结
                var option_pie1 = $.extend(true,{},option_pie);
                option_pie1.legend.data = ['1个月','3个月','6个月'];
                option_pie1.series[0].name = 'vip时长占比';
                $.each(res.data.vip_len.pie.sum,function(k,v){
                    option_pie1.series[0].data.push({
                        value:v,
                        name:k + '个月'
                    });
                });
                echarts.init(document.getElementById('purchase-length-sum'),'macarons').setOption(option_pie1,true);
                //筛选时间段
                $('.start').val(res.data.start);
                $('.end').val(res.data.end);
            }
        },true,true);
    }



});