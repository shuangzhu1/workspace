/**
 * Created by ykuang on 2017/11/24.
 */
define(function (require, exports) {
    require("/static/wap/js/tools/Chart.min.js");
    var base = require('base');

    //1-群成员 2-发言人数 3-发言条数
    exports.stat = function (ele, type, data, days, no_data, color_option) {
        var ctx = document.getElementById(ele).getContext('2d');
        var default_color = {
            backgroundColor: 'rgba(252, 107, 56, 0.16)',
            borderColor: 'rgb(252, 107, 56)',
            data: data,//eval("[<?php echo $data['member_cnt']?>]"),
            lineTension: 0,
            pointBorderColor: "rgb(252, 107, 56)",
            pointBackgroundColor: "rgb(252, 107, 56)"
        };
        if (no_data) {
            color_option = {
                backgroundColor: '#fff',
                borderColor: '#fff',
                data: data,//eval("[<?php echo $data['speakers']?>]"),
                lineTension: 0,
                pointBorderColor: '#fff',
                pointBackgroundColor: '#fff',
            };
        }
        if (color_option) {
            $.extend(default_color, color_option);
        }

        var default_option =
        {
            // The type of chart we want to create
            type: 'line',

            // The data for our dataset
            data: {
                labels: days,//eval("[<?php echo $days?>]"),
                datasets: [{
                    /*  label: "",*/
                    fill: true,
                    backgroundColor: default_color.backgroundColor,
                    borderColor: default_color.borderColor,
                    data: data,//eval("[<?php echo $data['member_cnt']?>]"),
                    lineTension: 0,
                    pointBorderColor: default_color.pointBorderColor,
                    pointBackgroundColor: default_color.pointBackgroundColor,
                    pointRadius: 5
                }]
            },

            // Configuration options go here
            options: {
                layout: {
                    padding: {
                        top: 20,
                        bottom: 0,
                        left: 30,
                        right: 30
                    }
                },
                legend: {
                    display: false
                },
                scales: {
                    yAxes: [{
                        gridLines: {
                            drawTicks: false,
                            color: "#e0e0e0"
                        },

                        ticks: {
                            /*   autoSkipPadding: 5,
                             padding: 20,
                             maxRotation: 0,
                             labelOffset: 15,*/
                            display: false,
                            beginAtZero: true
                        }
                    }]
                },
                animation: {
                    onComplete: function () {
                        if (no_data) {
                            $(".wrap_" + type + " .no_data").show();
                        } else {
                            var chartInstance = this.chart,
                                ctx = chartInstance.ctx;
                            ctx.font = Chart.helpers.fontString(Chart.defaults.global.defaultFontSize, Chart.defaults.global.defaultFontStyle, Chart.defaults.global.defaultFontFamily);
                            ctx.textAlign = 'center';
                            ctx.textBaseline = 'bottom';

                            this.data.datasets.forEach(function (dataset, i) {
                                var meta = chartInstance.controller.getDatasetMeta(i);
                                meta.data.forEach(function (bar, index) {
                                    var data = dataset.data[index];
                                    ctx.fillText(data, bar._model.x, bar._model.y - 5);
                                });
                            });
                        }
                    }


                }
            }
        };
        new Chart(ctx, default_option);
    };
    exports.init = function (days, data) {
        exports.stat("myChart", 1, data.chart1, days, false);
        if (data.chart2_no_data == '0') {
            exports.stat("myChart2", 2, data.chart2, days, false, {
                backgroundColor: 'rgba(0, 189, 204, 0.16)',
                borderColor: 'rgb(0, 189, 204)',
                data: data,//eval("[<?php echo $data['member_cnt']?>]"),
                lineTension: 0,
                pointBorderColor: "rgb(0, 189, 204)",
                pointBackgroundColor: "rgb(0, 189, 204)"
            });
        } else {
            exports.stat("myChart2", 2, data.chart2, days, true);
        }
        console.log(data.chart3);
        exports.stat("myChart3", 3, data.chart3, days,false,{
            backgroundColor: 'rgba(80, 199, 56, 0.16)',
                borderColor: 'rgb(80, 199, 56)',
                data: data,//eval("[<?php echo $data['member_cnt']?>]"),
                lineTension: 0,
                pointBorderColor: "rgb(80, 199, 56)",
                pointBackgroundColor: "rgb(80, 199, 56)"
        });

    }

});