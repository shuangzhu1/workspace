define(function (require,exports) {
    var base = require('app/panel/panel.base');

    $('#query').on('click',function () {
        var _this = this;
        var start = $(_this).closest('div').find('.start').val().replace(/\//g,'');
        var end = $(_this).closest('div').find('.end').val().replace(/\//g,'');
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

        base.requestApi('/api/stat/loginCount',data,function (res) {
            if(res.result === 1)
            {
                //统计区间值
                $(_this).closest('div').find('.start').val(res.data.period.start);
                $(_this).closest('div').find('.end').val(res.data.period.end);

                //图标数据
                option_pie = {
                    title : {
                        text: '新老用户占比',
                        x:'center'
                    },
                    tooltip : {
                        trigger: 'item',
                        formatter: "{a} <br/>{b} : {c} ({d}%)"
                    },
                    legend: {
                        orient : 'horizontal',
                        y : 'bottom',
                        data:['新用户','老用户']
                    },
                    /*toolbox: {
                        show : true,
                        feature : {
                            saveAsImage : {show: true}
                        }
                    },*/
                    series : [
                        {
                            name:'访问来源',
                            type:'pie',
                            radius : '40%',
                            center: ['50%', '45%'],
                            data:[
                                {value:res.data.proportion_of_users.new, name:'新用户'},
                                {value:res.data.proportion_of_users.old, name:'老用户'},

                            ]
                        }
                    ]
                };

                option_map = {
                    title : {
                        text: '登录用户分布',
                        x:'center'
                    },
                    tooltip : {
                        trigger: 'item'
                    },
                    legend: {
                        orient: 'vertical',
                        x:'left',
                        data:['登录人数']
                    },
                    dataRange: {
                        min: 0,
                        max:100,
                        x: 'left',
                        y: 'bottom',
                        text:['高','低'],           // 文本，默认为数值文本
                        calculable : true
                    },
                    /*toolbox: {
                        show: true,
                        feature : {
                            saveAsImage : {show: true}
                        }
                    },*/
                    series : [
                        {
                            name: '登录人数',
                            type: 'map',
                            mapType: 'china',
                            roam: false,
                            itemStyle:{
                                normal:{label:{show:true}},
                                emphasis:{label:{show:true}}
                            },
                            data:[

                            ]
                        }
                    ]
                };
                //总用户数
                option_pie.title.text = "新老用户占比(共 " + res.data.total_users + " 人)";
                //地图数据
                for( var k in res.data.users_per_province )
                {
                    option_map.series[0].data.push({name:k,value:res.data.users_per_province[k]})
                }
                option_map.dataRange.max = res.data.max_users_count
                echarts.init(document.getElementById('myChart-pie'),'macarons').setOption(option_pie,true);
                echarts.init(document.getElementById('myChart-map'),'macarons').setOption(option_map,true);


            }

        },true,true)
    });
    $('#query').get(0).click();
});