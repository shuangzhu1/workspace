define(function (require, exports) {
    var base = require('app/panel/panel.base');
    $('.loading').show();

    var option_pie = {
        title : {
            text: ''
        },
        tooltip : {
            trigger: 'item',
            formatter: "{a} <br/>{b} : {c} ({d}%)"
        },
        legend: {
            orient : 'horizontal',
            x : 'center',
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
        base.requestApi('/api/stat/appVersion',data,function(res){
            if( res.result === 1 )
            {
                $('.loading').hide();


                //ios各版本占比
                var option_pie1 = $.extend(true,{},option_pie);
                option_pie1.legend.data = res.data.ios.legend;
                option_pie1.series[0].name = 'ios各版本占比';
                $.each(res.data.ios.data,function(k,v){
                    option_pie1.series[0].data.push({
                        value:v,
                        name: k
                    });
                });
                echarts.init(document.getElementById('pie-ios'),'macarons').setOption(option_pie1,true);

                //android各版本占比
                var option_pie2 = $.extend(true,{},option_pie);
                option_pie2.legend.data = res.data.android.legend;
                option_pie2.series[0].name = 'android各版本占比';
                $.each(res.data.android.data,function(k,v){
                    option_pie2.series[0].data.push({
                        value:v,
                        name: k
                    });
                });
                echarts.init(document.getElementById('pie-android'),'infographic').setOption(option_pie2,true);
                //筛选时间段
                $('.start').val(res.data.start);
                $('.end').val(res.data.end);
            }
        },true,true);
    }



});