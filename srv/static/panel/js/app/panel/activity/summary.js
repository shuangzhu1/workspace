define(function (require,exports) {
    var base = require('app/panel/panel.base');
    var ecConfig = require('echarts/config');
    var option = {
        title : {
            text: '活动发起数',
            subtext: '',
            x:'center'
        },
        tooltip : {
            trigger: 'item',
            formatter: "{a} <br/>{b} : {c} ({d}%)"
        },
        legend: {
            orient : 'horizontal',//vertical
            //x : 'left',
            y:'bottom',
            data:['官方发起','用户发起']
        },
        series : [
            {
                //selectedMode: 'single',
                name:'总数',
                type:'pie',
                radius : '55%',
                center: ['50%', '50%'],
                itemStyle : {
                    normal : {
                        label : {
                            position : 'inner'
                        },
                        labelLine : {
                            show : false
                        }
                    }
                },
                data:[
                    {value:335, name:'官方发起'},
                    {value:310, name:'用户发起'}

                ]
            }
        ]
    };


    exports.getData = function (ac_type ) { //获取图表所需数据
        var begin = $('#begin').val().replace(/\//g,'');
        var end = $('#end').val().replace(/\//g,'');
        var isOk = true;
        if(begin-end > 0)
        {
            tip.showTip('err','开始日期不能大于结束日期',2000);
            isOk = false;
        }
        if( isOk )
        {
            $('.floors .loading').show();
            $('.floors .content').hide();
            $('.hr-double').hide();
            base.requestApi('/api/activity/summary',{begin:begin,end:end},function (res) {
                ac_type = ac_type || 0;
                var ac_count = {};
                ac_count.total = 0;
                ac_count.official = 0;
                ac_count.user = 0;
                ac_count.list = res.data.form.activitys[ac_type];
                for( var i in res.data.form.activitys[ac_type].total_launch)//全部
                {
                    ac_count.total +=  res.data.form.activitys[ac_type].total_launch[i];
                }
                for( var i in res.data.form.activitys[ac_type].official_launch)//官方
                {
                    ac_count.official +=  res.data.form.activitys[ac_type].official_launch[i];
                }
                for( var i in res.data.form.activitys[ac_type].user_launch)//用户
                {
                    ac_count.user +=  res.data.form.activitys[ac_type].user_launch[i];
                }
                option.title.text = "活动发起数";
                option.legend.data = ['官方发起','用户发起'];
                option.series[0].data = [
                    {value:ac_count.official,name:'官方发起'},
                    {value:ac_count.user,name:'用户发起'}
                ];
                $('#ac_count_official').html(ac_count.official);
                $('#ac_count_user').html(ac_count.user);
                $('#official_money').html(res.data.form.activitys[ac_type]['money_detail']['official_money'] / 100);
                $('#user_money').html(res.data.form.activitys[ac_type]['money_detail']['user_money'] / 100);
                for(var i in res.data.form.activitys[ac_type])
                {
                    if( i == 'official_launch')//官方发起列表
                    {
                        $('.floors').eq(0).find('.content table:eq(0) tbody').html('');//发起数量表格
                        for( var j in res.data.form.activitys[ac_type]['official_launch'] )
                        {
                            var $tr = $('<tr><td><span style="font-weight:bold" class="green">0</span><span class="right">元</span></td><td><span style="font-weight:bold" class="red"></span><span class="right">个</span></td></tr>');
                            $tr.find('td:eq(0) span:eq(0)').html(j/100);
                            $tr.find('td:eq(1) span:eq(0)').html(res.data.form.activitys[ac_type]['official_launch'][j]);
                            $('.floors').eq(0).find('.content table:eq(0) tbody').append($tr.get(0));
                        }

                        $('.floors').eq(1).find('.content table:eq(0) tbody').html('');//活动金额表格
                        var $tr = $('<tr><td><span style="font-weight:bold" class="green"></span><span class="right">元</span></td><td><span style="font-weight:bold" class="green"></span><span class="right">元</span></td><td><span style="font-weight:bold" class="green"></span><span class="right">元</span></td><td><span style="font-weight:bold" class="green"></span><span class="right">元</span></td></tr>');
                        $tr.find('td:eq(0) span:eq(0)').html(res.data.form.activitys[ac_type]['money_detail']['official_money'] / 100);
                        $tr.find('td:eq(1) span:eq(0)').html(res.data.form.activitys[ac_type]['money_detail']['official_refused'] / 100);
                        $tr.find('td:eq(2) span:eq(0)').html(res.data.form.activitys[ac_type]['money_detail']['user_take_official'] / 100);
                        $tr.find('td:eq(3) span:eq(0)').html((res.data.form.activitys[ac_type]['money_detail']['official_take'] - res.data.form.activitys[ac_type]['money_detail']['official_take_user']) / 100);
                        $('.floors').eq(1).find('.content table:eq(0) tbody').append($tr.get(0));

                    }
                    if( i == 'user_launch')//用户发起列表
                    {
                        $('.floors:eq(0) .content').find('table:eq(1) tbody').html('');//发起数量表格
                        for( var j in res.data.form.activitys[ac_type]['user_launch'] )
                        {
                            var $tr = $('<tr><td><span style="font-weight:bold" class="green">0</span><span class="right">元</span></td><td><span style="font-weight:bold" class="red"></span><span class="right">个</span></td></tr>');
                            $tr.find('td:eq(0) span:eq(0)').html(j/100);
                            $tr.find('td:eq(1) span:eq(0)').html(res.data.form.activitys[ac_type]['user_launch'][j]);
                            $('.floors').eq(0).find('.content table:eq(1) tbody').append($tr.get(0));
                        }

                        $('.floors').eq(1).find('.content table:eq(1) tbody').html('');//活动金额表格
                        var $tr = $('<tr><td><span style="font-weight:bold" class="green"></span><span class="right">元</span></td><td><span style="font-weight:bold" class="green"></span><span class="right">元</span></td><td><span style="font-weight:bold" class="green"></span><span class="right">元</span></td><td><span style="font-weight:bold" class="green"></span><span class="right">元</span></td></tr>');
                        $tr.find('td:eq(0) span:eq(0)').html(res.data.form.activitys[ac_type]['money_detail']['user_money'] / 100);
                        $tr.find('td:eq(1) span:eq(0)').html(res.data.form.activitys[ac_type]['money_detail']['user_refused'] / 100);
                        $tr.find('td:eq(2) span:eq(0)').html((res.data.form.activitys[ac_type]['money_detail']['user_take'] - res.data.form.activitys[ac_type]['money_detail']['user_take_official']) / 100);
                        $tr.find('td:eq(3) span:eq(0)').html(res.data.form.activitys[ac_type]['money_detail']['official_take_user'] / 100);
                        $('.floors').eq(1).find('.content table:eq(1) tbody').append($tr.get(0));
                    }
                }
                var echart1 = echarts.init(document.getElementById('chart1'));
                echart1.setOption(option);//活动发起数量
                option.title.text = '资金去向';
                option.legend.data = ['退回金额','用户领取','官方回收'];
                option.series[0].data = [
                    {value:res.data.form.activitys[ac_type]['money_detail']['official_refused'] / 100,'name':'退回金额'},
                    {value:res.data.form.activitys[ac_type]['money_detail']['user_take_official'] / 100,name:'用户领取'},
                    {value:(res.data.form.activitys[ac_type]['money_detail']['official_take'] - res.data.form.activitys[ac_type]['money_detail']['official_take_user']) / 100,name:'官方回收'}
                ];
                var echart2 = echarts.init(document.getElementById('chart2'));
                echart2.setOption(option);//活动发起数量

                option.title.text = '资金去向';
                option.legend.data = ['退回金额','用户领取','官方回收'];
                option.series[0].data = [
                    {value:res.data.form.activitys[ac_type]['money_detail']['user_refused'] / 100,'name':'退回金额'},
                    {value:(res.data.form.activitys[ac_type]['money_detail']['user_take'] - res.data.form.activitys[ac_type]['money_detail']['official_take_user']) / 100,name:'用户领取'},
                    {value:(res.data.form.activitys[ac_type]['money_detail']['official_take_user']) / 100,name:'官方回收'}
                ];
                var echart3 = echarts.init(document.getElementById('chart3'));
                echart3.setOption(option);//活动发起数量

                //用户参与活动详情
                $('#user_detail').find('div:eq(0) span').html((res.data.form.activitys[ac_type]['money_detail']['official_take_user'] / 100 - res.data.form.activitys[ac_type]['money_detail']['user_take_official'] / 100).toFixed(2));
                $('#user_detail').find('div:eq(1) span').html(res.data.form.activitys[ac_type]['user_detail']['user_count']);
                $('#user_detail').find('div:eq(2) span').html(res.data.form.activitys[ac_type]['user_detail']['reward_count']);
                $('#user_detail').find('div:eq(3) span').html(res.data.form.activitys[ac_type]['user_detail']['join_count']);
                $('.floors .loading').hide();
                $('.floors .content').fadeIn();
                $('.hr-double').fadeIn();
            },true,true);
        }


    }

    //选择活动类型
    $('#ac_type').on('change',function () {
        exports.getData($('#ac_type').val())
    });
    //选择日期
    $('#query').on('click',function(){
        exports.getData($('#ac_type').val());
    });
    /*echart1.on(ecConfig.EVENT.PIE_SELECTED, function (param){ //图标点击事件
        var selected = param.selected;
        var serie;
        var str = '当前选择： ';
        for (var idx in selected) {
            serie = option.series[idx];
            for (var i = 0, l = serie.data.length; i < l; i++) {
                if (selected[idx][i]) {
                    str += '【系列' + idx + '】' + serie.name + ' : ' +
                        '【数据' + i + '】' + serie.data[i].name + ' ';
                }
            }
        }
        console.log(str);
    })*/

});