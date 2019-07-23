define(function (require, exports) {
    var base = require('/srv/static/panel/js/app/panel/panel.base');
    require('/srv/static/panel/js/jquery/jquery.datetimepicker.js');
    require('/srv/static/panel/js/echarts/themes/pink.js');
    require('/srv/static/panel/js/echarts/themes/blue.js');
    require('/srv/static/panel/js/echarts/themes/green.js');
    require('/srv/static/panel/js/echarts/themes/purple.js');

    var day = 'today'; //日期
    var start = ''; //开始
    var end = '';   //结束
    var type = 'all';//类型

    var chart_total, chart_image, chart_video, chart_audio, chart_text, chart_package = null;
    var complex_total, complex_tag, complex_mediaType = null;
    exports.load = function () {
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

        $("#total").siblings(".loading").show();
        $("#image").siblings(".loading").show();
        $("#video").siblings(".loading").show();
        $("#audio").siblings(".loading").show();
        $("#text").siblings(".loading").show();
        $("#package").siblings(".loading").show();

        $("#total").hide();
        $("#image").hide();
        $("#video").hide();
        $("#audio").hide();
        $("#text").hide();
        $("#package").hide();
        //html("<div class='loading'><img src='/static/panel/images/admin/loading2.gif'/>&nbsp;&nbsp;数据加载中...</div>")
        //   $("#discuss").html("<div class='loading'><img src='/static/panel/images/admin/loading2.gif'/>&nbsp;&nbsp;数据加载中...</div>")
        //  $("#user").html("<div class='loading'><img src='/static/panel/images/admin/loading2.gif'/>&nbsp;&nbsp;数据加载中...</div>")
        //  $("#invite").html("<div class='loading'><img src='/static/panel/images/admin/loading2.gif'/>&nbsp;&nbsp;数据加载中...</div>")

        base.requestApi('/api/stat/discuss', {'type': "all", day: day, start: start, end: end}, function (res) {
            if (res.result == 1) {
                option.xAxis[0].data = eval(res.data.labels);
                option.series[0].data = eval(res.data.values);
                $(".total").html('【<a href="javascript::">' + res.data.count + '</a>】');
                $("#total").siblings(".loading").hide();
                $("#total").show();
                if (chart_total == null) {
                    chart_total = echarts.init(document.getElementById('total'), 'blue');
                }
                chart_total.setOption(option);
                //  echarts.init(document.getElementById('total'), 'blue').setOption(option);

            }
        }, true, true);
        base.requestApi('/api/stat/discuss', {'type': 3, day: day, start: start, end: end}, function (res) {
            if (res.result == 1) {
                option.xAxis[0].data = eval(res.data.labels);
                option.series[0].data = eval(res.data.values);
                $(".image").html('【<a href="javascript::">' + res.data.count + '</a>】');
                $("#image").siblings(".loading").hide();
                $("#image").show();

                if (chart_image == null) {
                    chart_image = echarts.init(document.getElementById('image'), 'pink');
                }
                chart_image.setOption(option);
            }
        }, true, true);
        base.requestApi('/api/stat/discuss', {'type': 2, day: day, start: start, end: end}, function (res) {
            if (res.result == 1) {
                option.xAxis[0].data = eval(res.data.labels);
                option.series[0].data = eval(res.data.values);
                $(".video").html('【<a href="javascript::">' + res.data.count + '</a>】');
                $("#video").siblings(".loading").hide();
                $("#video").show();
                if (chart_video == null) {
                    chart_video = echarts.init(document.getElementById('video'), 'green');
                }
                chart_video.setOption(option);
            }
        }, true, true);
        base.requestApi('/api/stat/discuss', {'type': 4, day: day, start: start, end: end}, function (res) {
            if (res.result == 1) {
                option.xAxis[0].data = eval(res.data.labels);
                option.series[0].data = eval(res.data.values);
                $(".audio").html('【<a href="javascript::">' + res.data.count + '</a>】');
                $("#audio").siblings(".loading").hide();
                $("#audio").show();
                if (chart_audio == null) {
                    chart_audio = echarts.init(document.getElementById('audio'), 'purple');
                }
                chart_audio.setOption(option);
            }
        }, true, true);
        base.requestApi('/api/stat/discuss', {'type': 5, day: day, start: start, end: end}, function (res) {
            if (res.result == 1) {
                option.xAxis[0].data = eval(res.data.labels);
                option.series[0].data = eval(res.data.values);
                $(".package").html('【<a href="javascript::">' + res.data.count + '</a>】');
                $("#package").siblings(".loading").hide();
                $("#package").show();
                if (chart_package == null) {
                    chart_package = echarts.init(document.getElementById('package'), 'pink');
                }
                chart_package.setOption(option);
            }
        }, true, true);
        base.requestApi('/api/stat/discuss', {'type': 1, day: day, start: start, end: end}, function (res) {
            if (res.result == 1) {
                option.xAxis[0].data = eval(res.data.labels);
                option.series[0].data = eval(res.data.values);
                $(".text").html('【<a href="javascript::">' + res.data.count + '</a>】');
                $("#text").siblings(".loading").hide();
                $("#text").show();
                if (chart_text == null) {
                    chart_text = echarts.init(document.getElementById('text'), 'blue');
                }
                chart_text.setOption(option);
            }
        }, true, true);
    };
    exports.singleWelcome = function (type, day) {
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
        $(type).html("<div class='loading'><img src='/srv/static/panel/images/admin/loading2.gif'/>&nbsp;&nbsp;数据加载中...</div>");
        base.requestApi('/srv/api/stat/discussComplex', {
            type: 'all',
            day: day,
            start: start,
            end: end,
            chart_type: 'line'
        }, function (res) {
            if (res.result == 1) {
                option.xAxis[0].data = eval(res.data.labels);
                option.series[0].data = eval(res.data.values);
                $("." + type).html('【<a href="javascript::">' + res.data.count + '</a>】');
                echarts.init(document.getElementById(type), 'purple').setOption(option);
            }
        }, true, true);

    };
    exports.complexLoad = function () {
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

      //  $("#total").html("<div class='loading'><img src='/static/panel/images/admin/loading2.gif'/>&nbsp;&nbsp;数据加载中...</div>");
      //  $("#tags").html("<div class='loading'><img src='/static/panel/images/admin/loading2.gif'/>&nbsp;&nbsp;数据加载中...</div>");
      //  $("#mediaType").html("<div class='loading'><img src='/static/panel/images/admin/loading2.gif'/>&nbsp;&nbsp;数据加载中...</div>");

        $("#total").siblings(".loading").show();
        $("#tags").siblings(".loading").show();
        $("#mediaType").siblings(".loading").show();
        $("#total").hide();
        $("#tags").hide();
        $("#mediaType").hide();

        $("#summary").html("<div class='loading' style='margin-top: 0;'><img src='/srv/static/panel/images/admin/loading2.gif'/>&nbsp;&nbsp;数据加载中...</div>");

        base.requestApi('/srv/api/stat/discussComplex', {
            type: type,
            day: day,
            start: start,
            end: end,
            chart_type: 'line'
        }, function (res) {
            if (res.result == 1) {
                option.xAxis[0].data = eval(res.data.labels);
                option.series[0].data = eval(res.data.values);
                $(".total").html('【<a href="javascript::">' + res.data.count + '</a>】');
                $("#total").siblings(".loading").hide();
                $("#total").show();
                if (complex_total == null) {
                    complex_total = echarts.init(document.getElementById('total'), 'blue');
                }
                complex_total.setOption(option);

            }
        }, true, true);
        base.requestApi('/srv/api/stat/discussComplex', {
            type: type,
            day: day,
            start: start,
            end: end,
            chart_type: 'circle'
        }, function (res) {
            if (res.result == 1) {
                circle_option.legend.data = eval(res.data.labels);
                circle_option.series[0].data = eval(res.data.values);

                $("#tags").siblings(".loading").hide();
                $("#tags").show();
                if (complex_tag == null) {
                    complex_tag = echarts.init(document.getElementById('tags'), 'blue');
                }
                complex_tag.setOption(circle_option);
            }
        }, true, true);
        base.requestApi('/srv/api/stat/discussComplex', {
            type: type,
            day: day,
            start: start,
            end: end,
            chart_type: 'type_circle'
        }, function (res) {
            if (res.result == 1) {
                circle_option.legend.data = eval(res.data.labels);
                circle_option.series[0].data = eval(res.data.values);
                $("#mediaType").siblings(".loading").hide();
                $("#mediaType").show();
                if (complex_mediaType == null) {
                    complex_mediaType = echarts.init(document.getElementById('mediaType'), 'purple');
                }
                complex_mediaType.setOption(circle_option);
            }
        }, true, true);
        base.requestApi('/srv/api/stat/discussComplex', {
            type: type,
            day: day,
            start: start,
            end: end,
            chart_type: 'sum'
        }, function (res) {
            if (res.result == 1) {
                var html = '<table class="summary"><tbody><tr>';
                html += '</tr></tbody></tabel>';
                var i = 0;
                $.each(res.data, function (key, val) {
                    if (key == 'labels') {
                        return;
                    }
                    html += ' <td><span class="text">' + res.data.labels[i] + '<a href="javascript:void(0);" data="visitor_count" class="help">' +
                        '&nbsp;</a></span>' +
                        '<div class="value summary-ellipsis" title="' + val + '">' + val + '</div></td>';
                    i++;
                });
                $("#summary").html(html);
            }
        }, true, true);
    };
    exports.single = function () {
        $(function () {
            exports.load();
            $(".trackable").on('click', function () {
                if (!$(this).hasClass('current')) {
                    $(this).addClass('current').siblings('.trackable').removeClass('current')
                    day = $(this).data('id');
                    exports.load();
                }
            })
        })
    };
    exports.complex = function (media_type) {
        type = media_type == '0' ? 'all' : media_type;
        $(function () {
            exports.complexLoad();
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
                    start = $('#start').val();
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
                    if (end !== $('#end').val()) {
                        end = $('#end').val();
                        day = 'custom';
                        exports.complexLoad();
                    }
                }
            });
            $(".trackable").on('click', function () {
                if (!$(this).hasClass('cur')) {
                    $(this).addClass('cur').siblings('.trackable').removeClass('cur');
                    day = $(this).data('id');
                    if ($(this).data('id') == 'custom') {
                        $(".time_filter").show();
                    } else {
                        exports.complexLoad();
                        $(".time_filter").hide();
                    }
                }
            });

            $(".type").on('click', function () {
                if (!$(this).hasClass('cur')) {
                    $(this).addClass('cur').siblings('.type').removeClass('cur');
                    type = $(this).data('id');
                    exports.complexLoad();
                }
            })
        })
    }

});