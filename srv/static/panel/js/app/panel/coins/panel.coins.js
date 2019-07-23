/**
 * Created by ykuang on 2018/3/16.
 */
define(function (require, exports) {
    var base = require('app/panel/panel.base');//公共函数
    //龙豆规则编辑
    exports.setCoinSetting = function () {
        $(".btnSetting").on('click', function () {
            var coins = $(".coins").val();
            var diamond_rate = $(".diamond_rate").val();

            var change_type = [];

            if (isNaN(coins) || coins < 0) {
                base.showTip('err', '请输入正确的龙豆数！', 1000);
                return false;
            }
            if (isNaN(diamond_rate) || diamond_rate < 0) {
                base.showTip('err', '请输入正确的龙币龙钻比例！', 1000);
                return false;
            }
            $("input[name='change_type']").each(function () {
                if ($(this).prop('checked') === true) {
                    change_type.push($(this).val());
                }
            });
            base.requestApi('/api/coins/setting', {
                rate: coins,
                change_type: change_type,
                diamond_rate: diamond_rate
            }, function (res) {
                if (res.result == 1) {
                    //  window.location.reload();
                    base.showTip('ok', '操作成功！', 1000);
                }
            });

        })
    };
});