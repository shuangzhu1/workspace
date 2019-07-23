define(function (require, exports) {
    var base = require('/srv/static/panel/js/app/panel/panel.base');
    require('/srv/static/panel/js/echarts/themes/pink.js');
    require('/srv/static/panel/js/echarts/themes/blue.js');
    require('/srv/static/panel/js/echarts/themes/green.js');
    require('/srv/static/panel/js/echarts/themes/purple.js');

    exports.shareChart = function () {
        $(function () {
            /*  var myChart_total = echarts.init(document.getElementById('total'), 'blue');
             var myChart_discuss = echarts.init(document.getElementById('discuss'), 'green');
             var myChart_user = echarts.init(document.getElementById('user'), 'purple');
             var myChart_invite = echarts.init(document.getElementById('invite'), 'pink');*/
            var option = {
                title: {
                    text: '',
                    subtext: ''
                },
                tooltip: {
                    trigger: 'axis'
                },
                legend: {
                    data: ['全部', 'QQ', '微博', "朋友圈", 'QQ空间', '微信好友']
                },
                toolbox: {
                    show: true,
                    feature: {
                        mark: {show: true},
                        dataView: {show: true, readOnly: false},
                        magicType: {show: true, type: ['line', 'bar', 'stack', 'tiled']},
                        restore: {show: true},
                        saveAsImage: {show: true}
                    }
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
                        name: '全部',
                        type: 'line',
                        data: ''
                    },
                    {
                        name: 'QQ',
                        type: 'line',
                        data: ''
                    },
                    {
                        name: '微博',
                        type: 'line',
                        data: ''
                    },
                    {
                        name: '朋友圈',
                        type: 'line',
                        data: ''
                    },
                    {
                        name: 'QQ空间',
                        type: 'line',
                        data: ''
                    },
                    {
                        name: '微信好友',
                        type: 'line',
                        data: ''
                    }
                ]
            };
            /*     myChart_total.setOption(total_option);
             myChart_discuss.setOption(total_option);
             myChart_user.setOption(total_option);
             myChart_invite.setOption(total_option);*/
            $("#total").html("<div class='loading'><img src='/srv/static/panel/images/admin/loading2.gif'/>&nbsp;&nbsp;数据加载中...</div>")
            $("#discuss").html("<div class='loading'><img src='/srv/static/panel/images/admin/loading2.gif'/>&nbsp;&nbsp;数据加载中...</div>")
            $("#user").html("<div class='loading'><img src='/srv/static/panel/images/admin/loading2.gif'/>&nbsp;&nbsp;数据加载中...</div>")
            $("#invite").html("<div class='loading'><img src='/srv/static/panel/images/admin/loading2.gif'/>&nbsp;&nbsp;数据加载中...</div>")

            base.requestApi('/api/stat/share', {'type': "all"}, function (res) {
                if (res.result == 1) {
                    res.data.values = eval(res.data.values);
                    option.xAxis[0].data = eval(res.data.labels);
                    option.series[0].data = eval(res.data.values[0]);
                    option.series[1].data = eval(res.data.values[1]);
                    option.series[2].data = eval(res.data.values[2]);
                    option.series[3].data = eval(res.data.values[3]);
                    option.series[4].data = eval(res.data.values[4]);
                    option.series[5].data = eval(res.data.values[5]);
                    echarts.init(document.getElementById('total'), 'blue').setOption(option);
                }
            }, true, true);
            base.requestApi('/api/stat/share', {'type': "invite"}, function (res) {
                if (res.result == 1) {
                    option.xAxis[0].data = eval(res.data.labels);
                    res.data.values = eval(res.data.values);
                    option.series[0].data = eval(res.data.values[0]);
                    option.series[1].data = eval(res.data.values[1]);
                    option.series[2].data = eval(res.data.values[2]);
                    option.series[3].data = eval(res.data.values[3]);
                    option.series[4].data = eval(res.data.values[4]);
                    option.series[5].data = eval(res.data.values[5]);
                    echarts.init(document.getElementById('invite'), 'pink').setOption(option);
                }
            }, true, true);
            base.requestApi('/api/stat/share', {'type': "user"}, function (res) {
                if (res.result == 1) {
                    option.xAxis[0].data = eval(res.data.labels);
                    res.data.values = eval(res.data.values);
                    option.series[0].data = eval(res.data.values[0]);
                    option.series[1].data = eval(res.data.values[1]);
                    option.series[2].data = eval(res.data.values[2]);
                    option.series[3].data = eval(res.data.values[3]);
                    option.series[4].data = eval(res.data.values[4]);
                    option.series[5].data = eval(res.data.values[5]);
                    echarts.init(document.getElementById('user'), 'green').setOption(option);
                }
            }, true, true);
            base.requestApi('/api/stat/share', {'type': "discuss"}, function (res) {
                if (res.result == 1) {
                    option.xAxis[0].data = eval(res.data.labels);
                    res.data.values = eval(res.data.values);
                    option.series[0].data = eval(res.data.values[0]);
                    option.series[1].data = eval(res.data.values[1]);
                    option.series[2].data = eval(res.data.values[2]);
                    option.series[3].data = eval(res.data.values[3]);
                    option.series[4].data = eval(res.data.values[4]);
                    option.series[5].data = eval(res.data.values[5]);
                    echarts.init(document.getElementById('discuss'), 'purple').setOption(option);
                }
            }, true, true);
        })
    }
});