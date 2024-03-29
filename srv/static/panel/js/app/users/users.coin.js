/**
 * Created by ykuang on 4/25/17.
 */
define(function (require, exports) {
    var base = require('app/panel/panel.base');//公共函数
    base.selectNone();
    base.selectCheckbox();
    /**
     * 初始化
     */
    exports.init = function () {
        exports.addRule();
        exports.setRule();
        exports.delRule();

    };

    /**
     * 添加新规则
     */
    /**
     * update grade
     *
     * @param btn
     */
    exports.addRule = function () {
        // submit to update
        $('#addRuleBtn').on('click', function (e) {
            if (e.preventDefault) {
                e.preventDefault();
            }
            else {
                e.returnValue = false;
            }
            var coin = parseInt($.trim($('.addRuleForm .addCoin').val()));
            var money = parseFloat($.trim($('.addRuleForm .addMoney').val()));
            var donate = parseInt($.trim($('.addRuleForm .addDonate').val()));
            var key = parseInt($.trim($('.addRuleForm .addKey').val()))
            if (key == '') {
                base.showTip('err', '填写正确appstroe标识！', 3000);
                return false;
            }
            if (isNaN(coin)) {
                base.showTip('err', '填写正确的龙豆值！', 3000);
                return false;
            }

            if (isNaN(money)) {
                base.showTip('err', '填写正确的金额', 3000);
                return false;
            }

            if (isNaN(donate)) {
                base.showTip('err', '填写赠送龙豆数', 3000);
                return false;
            }

            // console.log(a);
            var data = {
                'coin': coin,
                'money': money,
                'donate': donate,
                'key': key
            };

            var btn = this;
            // disable the button
            $(btn).attr('disabled', true);
            // api request
            base.requestApi('/api/rules/addCoinRule', data, function (res) {
                if (res.result == 1) {
                    base.showTip('ok', '规则添加成功！即将跳转', 1000);
                    setTimeout(function () {
                        window.location.reload();
                    }, 1000)
                }
                // cancel to disable the btn
                $(btn).attr('disabled', false);
            });

            e.stopImmediatePropagation();
        })
    };

    exports.delRule = function () {
        $('.del-rule').click(function (e) {
            if (e.preventDefault) {
                e.preventDefault();
            }
            else {
                e.returnValue = false;
            }

            if (!confirm("确定要删除此规则吗？")) {
                return false;
            }

            var id = $(this).attr('data-id');
            if (!id || isNaN(id)) {
                base.showTip('err', "没有选择要删除的规则");
            }

            base.requestApi('/api/rules/delCoinRule', {id: id}, function (res) {
                if (res.result == 1) {
                    base.showTip('ok', "规则删除成功，即将刷新页面数据，请稍候！", 3000);
                    setTimeout(function () {
                        window.location.reload();
                    }, 3000);
                }
                else {
                    base.showTip("err", res.error.msg + res.error.more);
                }
            });

            e.stopImmediatePropagation();
        });
    };

    /**
     * 更新等级数据
     */
    exports.setRule = function () {

        // submit to update
        $('#setRuleBtn').on('click', function (e) {
            var data = [];
            var err = false;
            $('.list .listData .item').each(function () {
                if ($(this).find('.donate').hasClass('err')) {
                    err = true;
                } else {
                    err = false;
                }
                // change data
                var id = $(this).attr('data-id');
                var key = $(this).find('.key').val().trim();
                var coin = parseInt($(this).find('.coin').val().trim());
                var money = parseFloat($(this).find('.money').val().trim());
                var donate = parseInt($(this).find('.donate').val());
                //  var badge = $(this).find('.badgeField').val().trim();

                //old data
                var old_key = $(this).find('.key').attr('data-old');
                var old_coin = $(this).find('.coin').attr('data-old');
                var old_money = $(this).find('.money').attr('data-old');
                var old_donate = $(this).find('.donate').attr('data-old');
                //      var old_badge = $(this).find('.badgeField').attr('data-old');

                // change or not
                if (!(coin == old_coin
                        && money == old_money
                        && donate == old_donate
                        && key == old_key
                    ) && (coin)) {
                    var tmp = {
                        id: id,
                        key: key,
                        coin: coin,
                        money: money,
                        donate: donate
                    };
                    data.push(tmp);
                }
            });
            if (err) {
                base.showTip('err', '填写的数据有误，请检查～！', 3000);
                return false;
            }

            if (data.length == 0) {
                base.showTip('err', '您未作任何的修改', 3000);
                return false;
            }
            base.requestApi('/api/rules/saveCoinRule', {'data': data}, function (res) {
                if (res.result == 1) {
                    base.showTip('ok', '等级设置成功！即将跳转', 1000, function () {
                        window.location.reload()
                    });
                }

            });

            e.stopImmediatePropagation();

        })
    };
    exports.delRecord = function () {
        $(".list .listData").on('click', '.delBtn', function (e) {
            // params
            var id = $(this).attr('data-id');
            var data = [id];
            // confirm
            var cm = window.confirm('你确定需要该条数据吗？,删除后数据不可恢复');
            if (!cm) {
                return;
            }

            del(data);
            e.stopImmediatePropagation();
        });

        $(".list").on('click', '.delAllSelected', function (e) {
            var data = [];
            $(".list .listData input.chk").each(function () {
                if ($(this).attr('checked') == true || $(this).attr('checked') == 'checked') {
                    data.push($(this).attr('data-id'));
                }
            });

            //  has no selected
            if (data.length == 0) {
                base.showTip('err', '请选择需要删除的项,删除后数据不可恢复', 3000);
                return;
            }
            // confirm
            var cm = window.confirm('你确定需要删除选中的数据吗？,删除后数据不可恢复');
            if (!cm) {
                return;
            }

            del(data);

            e.stopImmediatePropagation();
        });

        function del(data) {
            // api request
            base.requestApi('/api/user/delCoinLog', {data: data}, function (res) {
                if (res.result == 1) {
                    for (var i = 0; i < data.length; i++) {
                        $('.list .listData .item[data-id="' + data[i] + '"]').remove();
                    }
                    base.showTip('ok', '删除成功！', 3000);
                }
            });
        }
    };
});
