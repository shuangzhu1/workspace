/**
 * Created by ykuang on 4/25/17.
 */
define(function (require, exports) {
    var base = require('app/panel/panel.base');//公共函数
    var store = require('app/panel/panel.storage');//公共函数
    var color = require('app/app.color');

    base.selectNone();
    base.selectCheckbox();

    /**
     * 初始化
     */
    exports.init = function () {
        //上传图标
        store.getImg('.upload-badge', function (res, obj) {
            $(obj).parent().find(".badgeField").val(res.url);
            $(obj).parent().find(".badge-img-previewer").attr('src', res.url);
        }, false);

        exports.addGrade();
        exports.setGrade();
        exports.delGroup();
    };

    /**
     * 添加新等级
     */
    /**
     * update grade
     *
     * @param btn
     */
    exports.addGrade = function () {
        // submit to update
        $('#addGradeBtn').on('click', function (e) {
            if (e.preventDefault) {
                e.preventDefault();
            }
            else {
                e.returnValue = false;
            }
            var name = $('.addGradeForm .txtName').val().trim();
            //   var discount = $('.addGradeForm .txtdiscount').val().trim();
            var exp_start = parseFloat($('.addGradeForm .addIntPointStart').val().trim());
            var exp_end = parseFloat($('.addGradeForm .addIntPointEnd').val().trim());
            //  var badgeImg = $('.addGradeForm .badgeField').val().trim();
            var amountOldStart = $(this).attr('data-amount-start');
            var amountOldEnd = $(this).attr('data-amount-end');
            //var groupMember = $(".addGradeForm .groupMember").val().trim();
            var topDiscuss = $(".addGradeForm .topDiscuss").val().trim();
            //    var share_commission = $('.addGradeForm .share-commission').val().trim();
            //   var diy_commission = $('.addGradeForm .diy-coomission').val().trim();

            /*   if (!name || name.length <= 0) {
             base.showTip('err', '头衔名称必填！', 3000);
             return false;
             }*/

            if (isNaN(exp_start) && isNaN(exp_end)) {
                base.showTip('err', '填写的经验值范围数据有误，请检查～！', 3000);
                return false;
            }

            if (!exp_start && exp_end && !isNaN(amountOldEnd) && exp_end > amountOldEnd) {
                base.showTip('err', '填写的经验值结束值不得高于' + amountOldEnd + '，请检查～！', 3000);
                return false;
            }

            if (exp_start && !exp_end && !isNaN(amountOldStart) && exp_start < amountOldStart) {
                base.showTip('err', '填写的经验值起始值不得低' + amountOldStart + '，请检查～！', 3000);
                return false;
            }

            /*  if (!badgeImg || badgeImg.length <= 0) {
             base.showTip('err', '头衔图标必填！', 3000);
             return false;
             }*/

            // console.log(a);
            var data = {
                'name': name,
                'exp_start': isNaN(exp_start) ? "" : exp_start,
                'exp_end': isNaN(exp_end) ? "" : exp_end,
                //'member_limit': groupMember,
                'top_limit': topDiscuss,
                //  'badge': badgeImg,
                // 'discount': discount,
                //   'share_commission': share_commission,
                //  'diy_commission': diy_commission
            };

            var btn = this;
            // disable the button
            $(btn).attr('disabled', true);
            // api request
            base.requestApi('/api/user/addGroup', data, function (res) {
                if (res.result == 1) {
                    base.showTip('ok', '等级设置成功！即将跳转', 1000);
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

    exports.delGroup = function () {
        $('.del-grade').click(function (e) {
            if (e.preventDefault) {
                e.preventDefault();
            }
            else {
                e.returnValue = false;
            }

            if (!confirm("确定要删除此等级吗？")) {
                return false;
            }

            var grade = $(this).attr('data-grade');
            if (!grade || isNaN(grade) || grade <= 0) {
                base.showTip('err', "没有选择要删除的等级");
            }

            base.requestApi('/api/user/delGroup', {grade: grade}, function (res) {
                if (res.result == 1) {
                    base.showTip('ok', "分组删除成功，即将刷新页面数据，请稍候！", 3000);
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
    exports.setGrade = function () {
        // check if has changed
        /*  $('.intPointEnd').on('change', function () {
         // get data
         var id = $(this).attr('data-id');
         var exp_old_start = parseInt($(this).attr('data-amount-start'));
         var exp_old_end = parseInt($(this).attr('data-amount-end'));
         var exp_start = $(this).val();
         var amount_end = $(this).val();
         var grade = $(this).attr('data-grade');

         // check itself
         if (!(!isNaN(amount_end) && ( parseInt(amount_end) > amount_start ))) {
         $(this).addClass('err');
         base.showTip('err', '请设置当前结束额度并且大于开始额度！', 3000);
         return false;
         } else {
         // set own data
         $(this).removeClass('err');
         }

         // check next
         // set next start
         var next_grade = 1 + parseInt(grade);
         next_grade = next_grade < 10 ? '0' + next_grade : next_grade;
         var next_amount_start = 1 + parseInt(amount_end);
         // next obj . fuck here.
         var next_obj = $('.list').find('.item[data-grade="' + next_grade + '"]');
         // next grade is not exists
         if (next_obj.length == 0) {
         // just do add
         if (grade) {
         // for new add
         $('.addRow .add-amount-start').text(next_amount_start);
         $('.addRow .addIntPointEnd').attr('data-amount-start', next_amount_start);
         }
         return false;
         }
         // change count
         next_obj.find('.amount-start').text(next_amount_start);
         next_obj.find('.intPointEnd').attr('data-amount-start', next_amount_start);

         var next_amount_end = next_obj.find('.intPointEnd').val();

         // check next
         if (!(!isNaN(next_amount_end) && (next_amount_end > next_amount_start ))) {
         next_obj.find('.intPointEnd').focus().addClass('err');
         base.showTip('err', '请设置结束额度并且大于开始额度！', 3000);
         return false;
         } else {
         next_obj.find('.intPointEnd').removeClass('err');
         $(this).removeClass('err');
         }
         });
         */
        // submit to update
        $('#setGradeBtn').on('click', function (e) {
            var data = [];
            var err = false;
            $('.list .listData .item').each(function () {
                if ($(this).find('.intPointEnd').hasClass('err')) {
                    err = true;
                } else {
                    err = false;
                }
                // change data
                var id = $(this).attr('data-id');
                var name = $(this).find('.txtName').val().trim();
                var exp_end = $(this).find('.intPointEnd').val().trim();
                var exp_start = $(this).find('.intPointStart').val();
                //var member_limit = $(this).find('.memberLimit').val().trim();
                var top_limit = $(this).find('.topLimit').val().trim();
                //  var badge = $(this).find('.badgeField').val().trim();

                //old data
                var old_name = $(this).find('.txtName').attr('data-old');
                var old_start = $(this).find('.intPointEnd').attr('data-old-start');
                var old_end = $(this).find('.intPointEnd').attr('data-old-end');
                //      var old_badge = $(this).find('.badgeField').attr('data-old');
                //var old_member_limit = $(this).find('.memberLimit').attr('data-old');
                var old_top_limit = $(this).find('.topLimit').attr('data-old');

                // change or not
                if (!(name == old_name
                        && exp_start == old_start
                        && exp_end == old_end
                            //   && badge == old_badge
                        //&& member_limit == old_member_limit
                        && top_limit == old_top_limit
                    ) && (name)) {
                    var tmp = {
                        id: id,
                        name: name,
                        exp_start: exp_start,
                        exp_end: exp_end,
                        //  badge: badge,
                        //member_limit: member_limit,
                        top_limit: top_limit
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

            // console.log(a);
            var data = {'data': data}
            // disable the button
            // api request
            base.requestApi('/api/user/saveGroups', data, function (res) {
                if (res.result == 1) {
                    base.showTip('ok', '等级设置成功！即将跳转', 1000, function () {
                        window.location.reload()
                    });
                }

            });

            e.stopImmediatePropagation();

        })
    };
});
