define(function (require, exports) {
    var base = require('/srv/static/panel/js/app/panel/panel.base');
    require('/srv/static/panel/js/jquery/jquery.datetimepicker.js');
    require('/srv/static/panel/js/echarts/themes/pink.js');
    require('/srv/static/panel/js/echarts/themes/blue.js');
    require('/srv/static/panel/js/echarts/themes/green.js');
    require('/srv/static/panel/js/echarts/themes/purple.js');
    var user = require('/srv/static/panel/js/app/panel/stat/panel.user');
    var discuss = require('/srv/static/panel/js/app/panel/stat/panel.discuss');
    var group = require('/srv/static/panel/js/app/panel/stat/panel.group.js?v=1.0');

    exports.statistic = function () {
        $(function () {
            user.complexLoad('total', 'today');
            discuss.singleWelcome('discusss', 'today');
            group.singleWelcome('groups', 'today');

            $(".user_component .trackable").on('click', function () {
                if (!$(this).hasClass('cur')) {
                    $(this).addClass('cur').siblings('.trackable').removeClass('cur');
                    if ($(this).data('id') == 'custom') {
                        $(this).parent().siblings(".time_filter").show();
                    } else {
                        user.complexLoad($(this).data('type'), $(this).data('id'));
                        $(this).parent().siblings(".time_filter").hide();
                    }
                }
            });
            $(".discuss_component .trackable").on('click', function () {
                if (!$(this).hasClass('cur')) {
                    $(this).addClass('cur').siblings('.trackable').removeClass('cur');
                    var day = $(this).data('id');
                    if ($(this).data('id') == 'custom') {
                        $(".time_filter").show();
                    } else {
                        discuss.singleWelcome('discusss', day);
                        $(".time_filter").hide();
                    }
                }
            });
            $(".group_component .trackable").on('click', function () {
                if (!$(this).hasClass('cur')) {
                    $(this).addClass('cur').siblings('.trackable').removeClass('cur');
                    var day = $(this).data('id');
                    if ($(this).data('id') == 'custom') {
                        $(".time_filter").show();
                    } else {
                        group.singleWelcome('groups', day);
                        $(".time_filter").hide();
                    }
                }
            });
        })
    }
});