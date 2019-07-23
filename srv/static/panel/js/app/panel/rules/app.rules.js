/**
 * Created by ykuang on 2016/12/19.
 */
define(function (require, exports) {
    var base = require('app/panel/panel.base');//公共函数
    var store = require('app/panel/panel.storage');//公共函数
    var color = require('app/app.color');

    base.selectNone();
    base.selectCheckbox();

    exports.setRule = function (vip_grade) {
        // edit
        $('.editRowBtn').on('click', function (e) {
            var behavior = $(this).attr('data-behavior');
            $('.item-show[data-behavior="' + behavior + '"]').hide();
            $('.item-edit[data-behavior="' + behavior + '"]').fadeIn();

            e.stopImmediatePropagation();
        });
        // save item
        $('.saveRowBtn').on('click', function (e) {
            var type = $("#ruleForm").attr('data-type');
            type = (type == 'normal' ? '0' : "1");
            var id = $(this).attr('data-id');
            var behavior = $(this).attr('data-behavior');
            var obj = $(this).parents('.item-edit[data-behavior="' + behavior + '"]');
            var action = obj.find('.pointType').val();
            var term = obj.find('.term').val();
            var limit = obj.find('.limit').val();
            var quantity = obj.find('.quantity').val();

            var txt_action = obj.find('.pointType option:selected').text();
            var txt_term = obj.find('.term option:selected').text();
            var txt_points = obj.find('.quantity').val();

            var data = {
                'id': id,
                'behavior': behavior,
                'action': action,
                'quantity': quantity,
                'term': term,
                'limit': limit,
            };
            // api request
            base.requestApi('/api/rules/setPoint', {data: [data]}, function (res) {
                if (res.result == 1) {
                    window.location.reload();
                    base.showTip('ok', '操作成功！', 1000);
                }
            });

            $('.item-edit[data-behavior="' + behavior + '"]').hide();
            $('.item-show[data-behavior="' + behavior + '"]').fadeIn();

            e.stopImmediatePropagation();
        });

        // edit all
        $('.editRuleBtn').on('click', function (e) {
            var flag = $(this).attr('data-flag');
            if (flag == 1) {
                $('.ruleForm p.item-edit').hide();
                $('.ruleForm p.item-show').fadeIn();
                $(this).attr('data-flag', 0).text('编辑');
                $('.saveRuleBtn').hide();
            } else {
                $('.ruleForm p.item-show').hide();
                $('.ruleForm p.item-edit').fadeIn();
                $(this).attr('data-flag', 1).text('取消');
                $('.saveRuleBtn').fadeIn();
            }
            e.stopImmediatePropagation();
        });

        // item show edit
        $('.item-show').on({
            mouseenter: function () {
                $(this).find('.editRowBtn').show();
            },
            mouseleave: function () {
                $(this).find('.editRowBtn').hide();
            }
        });

        // save all
        $('.saveRuleBtn').on('click', function (e) {
            var tmp = [];
            var is_firm = parseInt($('#is_firm').val());
            $('.ruleForm .item-edit:visible').each(function () {
                var id = $(this).attr('data-id');
                var behavior = $(this).attr('data-behavior');
                var action = $(this).find('.pointType').val();
                var term = $(this).find('.term').val();
                var quantity = $(this).find('.quantity').val();
                var limit = $(this).find('.limit').val();
                var data = {
                    'id': id,
                    'behavior': behavior,
                    'action': action,
                    'quantity': quantity,
                    'term': term,
                    'limit': limit,
                };
                tmp.push(data);
            });

            // api request
            base.requestApi('/api/rules/setPoint', {data: tmp}, function (res) {
                if (res.result == 1) {
                    base.showTip('ok', '操作成功！', 1000);
                }
                // window.location.reload();
            });
            e.stopImmediatePropagation();
        });
    };

    exports.setCoinRule = function (vip_grade) {
        // edit
        $('.editRowBtn').on('click', function (e) {
            var behavior = $(this).attr('data-behavior');
            $('.item-show[data-behavior="' + behavior + '"]').hide();
            $('.item-edit[data-behavior="' + behavior + '"]').fadeIn();

            e.stopImmediatePropagation();
        });
        // save item
        $('.saveRowBtn').on('click', function (e) {
            var type = $("#ruleForm").attr('data-type');
            type = (type == 'normal' ? '0' : "1");
            var id = $(this).attr('data-id');
            var behavior = $(this).attr('data-behavior');
            var obj = $(this).parents('.item-edit[data-behavior="' + behavior + '"]');
            var action = obj.find('.pointType').val();
            var term = obj.find('.term').val();
            var quantity = obj.find('.quantity').val();
            var limit = obj.find('.limit').val();

            var txt_action = obj.find('.pointType option:selected').text();
            var txt_term = obj.find('.term option:selected').text();
            var txt_points = obj.find('.quantity').val();

            var data = {
                'id': id,
                'behavior': behavior,
                'action': action,
                'quantity': quantity,
                'term': term,
                'limit': limit
            };
            // api request
            base.requestApi('/api/rules/setCoinPoint', {data: [data]}, function (res) {
                if (res.result == 1) {
                    //  window.location.reload();
                    base.showTip('ok', '操作成功！', 1000);
                }
            });

            $('.item-edit[data-behavior="' + behavior + '"]').hide();
            $('.item-show[data-behavior="' + behavior + '"]').fadeIn();

            e.stopImmediatePropagation();
        });

        // edit all
        $('.editRuleBtn').on('click', function (e) {
            var flag = $(this).attr('data-flag');
            if (flag == 1) {
                $('.ruleForm p.item-edit').hide();
                $('.ruleForm p.item-show').fadeIn();
                $(this).attr('data-flag', 0).text('编辑');
                $('.saveRuleBtn').hide();
            } else {
                $('.ruleForm p.item-show').hide();
                $('.ruleForm p.item-edit').fadeIn();
                $(this).attr('data-flag', 1).text('取消');
                $('.saveRuleBtn').fadeIn();
            }
            e.stopImmediatePropagation();
        });

        // item show edit
        $('.item-show').on({
            mouseenter: function () {
                $(this).find('.editRowBtn').show();
            },
            mouseleave: function () {
                $(this).find('.editRowBtn').hide();
            }
        });

        // save all
        $('.saveRuleBtn').on('click', function (e) {
            var tmp = [];
            var is_firm = parseInt($('#is_firm').val());
            $('.ruleForm .item-edit:visible').each(function () {
                var id = $(this).attr('data-id');
                var behavior = $(this).attr('data-behavior');
                var action = $(this).find('.pointType').val();
                var term = $(this).find('.term').val();
                var quantity = $(this).find('.quantity').val();
                var limit = $(this).find('.limit').val();
                var data = {
                    'id': id,
                    'behavior': behavior,
                    'action': action,
                    'vip_grade': vip_grade,
                    'quantity': quantity,
                    'term': term,
                    'is_firm': is_firm,
                    'limit': limit
                };
                tmp.push(data);
            });

            // api request
            base.requestApi('/api/rules/setCoinPoint', {data: tmp}, function (res) {
                if (res.result == 1) {
                    base.showTip('ok', '操作成功！', 1000);
                }
                //  window.location.reload();
            });
            e.stopImmediatePropagation();
        });
    }

});