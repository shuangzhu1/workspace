define(function (require, exports) {
    var base = require('app/panel/panel.base');

    /**
     */
    exports.del = function () {
        $(".list .listData").on('click', ".delBtn", function (e) {
            // params
            var id = $(this).attr('data-id');
            var data = id;
            // confirm
            var cm = window.confirm('你确定需要该条数据吗？');
            if (!cm) {
                return;
            }

            // api request
            base.requestApi('/api/admin/del', {data: data}, function (res) {
                if (res.result == 1) {
                    $('.list .listData .item[data-id="' + id + '"]').fadeOut();
                    setTimeout(function () {
                        $('.list .listData .item[data-id="' + id + '"]').remove();
                    }, 1000);
                    base.showTip('ok', '删除成功！', 3000);
                }
            });

            e.stopImmediatePropagation();
        });
    };

    exports.saveAdmin = function () {
        // add click
        $('.addOptionBtn').click(function (e) {
            base.showPop('#optionPopup');

            e.stopImmediatePropagation();
        });

        // confirm
        $('#optionWidget').on('click', '.res-btn', function () {
            var id = $(this).attr('data-id');
            var obj = $('#optionWidget');
            var name = obj.find('.name').val();
            var password = obj.find('.password').val();
            var account = obj.find('.account').val();
            var active = $('#optionWidget .active:checked').val();
            var group = $('#optionWidget .group').val();
            var level = $('#optionWidget .level').val();

            if (!checkField(obj.find('.account'), '登陆账号未5-16位字母数字下线组成', /^[a-zA-Z][a-zA-Z0-9_]{4,15}$/)) {
                obj.find('.account').focus();
                base.showTip('err', '登陆账号为5-16位字母数字下线组成', 3000);
                return false;
            }


            if (!id) {
                if (password.length < 6 || password.length > 16) {
                    obj.find('.password').focus();
                    base.showTip('err', '新建账号时,密码必须填写,长度为6-16位~!', 3000);
                    return false;
                }
                if (group == '') {
                    obj.find('.group').focus();
                    base.showTip('err', '请选择分组~!', 3000);
                    return false;
                }
            } else {
                if (password) {
                    if (!(password.length >= 6 && password.length <= 16)) {
                        obj.find('.password').focus();
                        base.showTip('err', '密码长度为6-16位,为空则不修改~!', 3000);
                        return false;
                    }
                }
            }

            if (!level) {
                obj.find('.role').focus();
                base.showTip('err', '请选择用户角色~!', 3000);
                return false;
            }

            var data = {
                account: account,
                name: name,
                password: password,
                level: level,
                group: group,
                active: active
            };
            base.requestApi('/api/admin/save', {data: data, id: id}, function (res) {
                if (res.result == 1) {
                    window.location.reload();
                    base.hidePop('#optionPopup');
                }
            });

        });

        // update
        $('.list .listData .upBtn').click(function (e) {
            var id = $(this).attr('data-id');
            var level = $(this).attr('data-level');
            var name = $(this).attr('data-name');
            var account = $(this).attr('data-account');
            var active = $(this).attr('data-active');
            var group = $(this).attr('data-group');

            var current_admin = $(this).attr('data-current-admin');

            if (id) {
                base.showPop('#optionPopup');

                $('#optionWidget .res-btn').attr('data-id', id);
                var obj = $('#optionWidget');
                obj.find('.account').val(account);
                obj.find('.name').val(name);
                obj.find('.level option[value="' + level + '"]').prop('selected', 'selected');
                obj.find('.group option[value="' + group + '"]').prop('selected', 'selected');
                obj.find('.active[value="' + active + '"]').prop('checked', 'checked');

                obj.find('.account_field').hide();
                if (current_admin == id) {
                    obj.find('.group_field').hide();
                    obj.find('.level_field').hide();
                    obj.find('.active_field').hide();
                } else {
                    obj.find('.group_field').show();
                    obj.find('.level_field').show();
                    obj.find('.active_field').show();


                }

            }

            e.stopImmediatePropagation();
        });

        // update
        $('.addBtn').click(function (e) {
            var id = $(this).attr('data-id');
            var level = $(this).attr('data-level');
            var group = $(this).attr('data-group');
            var name = $(this).attr('data-name');
            var account = $(this).attr('data-account');
            var active = $(this).attr('data-active');

            base.showPop('#optionPopup');

            $('#optionWidget .res-btn').attr('data-id', id);
            var obj = $('#optionWidget');
            obj.find('.account').val("");
            obj.find('.name').val("");
            obj.find('.level option[value=""]').prop('selected', 'selected');
            obj.find('.group option[value=""]').prop('selected', 'selected');
            obj.find('.active[value="1"]').prop('checked', 'checked');

            e.stopImmediatePropagation();
        });

    };
});