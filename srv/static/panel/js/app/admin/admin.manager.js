// admin user,group,menus,permission settings
define(function (require, exports) {
    var base = require('app/panel/panel.base');//公共函数
    base.selectCheckbox();

    var site_url = '/';

    function checkNull(str, field) {
        if (str.length == 0) {
            $(field).focus().parent().find('#status').html('不能为空');
            return false;
        } else {
            $(field).parent().find('#status').html('');
        }
        return true;
    }

    /**
     * login access
     *
     * @param btn
     * @param referer
     */
    exports.adminLogin = function (btn, referer) {
        $(btn).on('click', function () {
            // params
            var user = $('#loginForm #user').val();
            var passwd = $('#loginForm #passwd').val();

            if (checkNull(user, $('#loginForm #user')) == true) {
            } else {
                return false;
            }

            if (checkNull(passwd, $('#loginForm #passwd')) == true) {
            } else {
                return false;
            }

            var data = {
                'user': user,
                'passwd': passwd
            }
            var url = referer ? referer : site_url + '/index';

            // disable the button
            $(btn).attr('disabled', true);
            // api request
            base.requestApi('/api/admin/login', data, function (res) {
//                console.log(res);
                if (res.result == 1) {
                    tip.showTip('ok', '登陆成功，即将跳转', 1000);
                    setTimeout(function () {
                        window.location.href = url;
                    }, 1500);
                }
            })
        });
    }

    /**
     * update Admin userInfo
     * @param btn
     */
    exports.updateAdminInfo = function (btn) {
        $(btn).on('click', function () {
            // params
            var user = $('#updateAdminForm #user').val();
            var true_name = $('#updateAdminForm #true_name').val();
            var email = $('#updateAdminForm #email').val();
            var data = {'user': user, 'true_name': true_name, 'email': email};

            // disable the button
            $(btn).attr('disabled', true);
            // api request
            base.requestApi('/api/admin/uinfo', data, function (res) {
                if (res.result == 1) {
                    tip.showTip('ok', '恭喜您，个人信息修改成功！', 1000);
                    $('#updateAdminForm #user').parent().find('#status').text('用于登陆');
                    $('#updateAdminForm #email').parent().find('#status').text('');
                } else if (res.error.code == 10005) {
                    $('#updateAdminForm #email').focus();
                    $('#updateAdminForm #email').parent().find('#status').text('邮箱已经存在~！');
                } else if (res.error.code == 10003) {
                    $('#updateAdminForm #user').focus();
                    $('#updateAdminForm #user').parent().find('#status').text('用户已经存在~！');
                }

                // unDisable the button
                $(btn).attr('disabled', false);
            });
        });
    };

    /**
     * update Admin userInfo
     *
     * @param btn
     */
    exports.changeAdminPasswd = function (btn) {
        $(btn).on('click', function () {
            // params
            var oldPasswd = $('#changePasswdForm #oldPasswd').val();
            var newPasswd = $('#changePasswdForm #newPasswd').val();
            var data = {'oldPasswd': oldPasswd, 'newPasswd': newPasswd};

            // disable the button
            $(btn).attr('disabled', true);
            // api request
            base.requestApi('/api/admin/upass', data, function (res) {

                if (res.result == 1) {
                    tip.showTip('ok', '密码更新成功，下次登录有效！', 1000);
                    $('#changePasswdForm #oldPasswd').parent().find('#status').text('');
                } else {
                    $('#changePasswdForm #oldPasswd').focus();
                    $('#changePasswdForm #oldPasswd').parent().find('#status').text('原始密码不正确~！');
                }

                // unDisable the button
                $(btn).attr('disabled', false);
            });
        });
    };


    /**
     * mv cat and their children to new parent
     * @param btn
     */
    exports.mvMenus = function (btn) {
        $(btn).on('click', function (e) {
            // params
            var toCid = $('select.mvMenusCat').val();

            if (isNaN(toCid) || toCid == '') {
                tip.showTip('err', '请选择二级栏目！', 3000);
                return false;
            }

            var data = [];
            $(".list tr input.chk").each(function () {
                if ($(this).prop('checked') == true || $(this).prop('checked') == 'checked') {
                    data.push($(this).attr('data-id'));
                }
            });

            //  has no selected
            if (data.length == 0) {
                tip.showTip('err', '请选择需要移动的项', 3000);
                return;
            }

            // confirm
            var cm = window.confirm('你确定需要进行此操作吗？');
            if (!cm) {
                return;
            }

            // disable the button
            $(btn).attr('disabled', true);
            // api request
            base.requestApi('/api/admin/mvMenu', {'data': data, 'cid': toCid}, function (res) {
                if (res.result == 1) {
                    for (var i = 0; i < data.length; i++) {
                        $('.list tr[data-id="' + data[i] + '"]').remove();
                    }
                    tip.showTip('ok', '操作成功！', 1000,function(){
                        window.location.reload();
                    });
                }
            },true,false);

            e.stopImmediatePropagation();
        });
    };

    /**
     * set menus
     */
    exports.setMenus = function () {
        // first cat click
        $("#menus .menuCat").on('click', function () {
            var catPid = $(this).attr('data-id');

            // change current
            $("#menus .menuCat").removeClass('current');
            $(this).addClass('current');

            // change second cat view
            $("#menus .menuCat2").removeClass('current').hide();
            $("#menus .menuCat2[data-pid=" + catPid + "]").show();
        });
        // second cat click
        $("#menus .menuCat2").on('click', function () {
            var catId = $(this).attr('data-id');
            $("#menus .menuCat2").removeClass('current');
            $(this).addClass('current');
            $("#menus .listData .row").removeClass('current').hide();
            $("#menus .listData .row[data-cid=" + catId + "]").show();
            // add cid to attr
            $('#menus .listData .addMenuRow').show().attr('data-cid', catId);
            $('#menus .listData .addMenuBtn').show().attr('data-cid', catId);
        });

        // add menu row
        $("#menus .addMenuBtn").on('click', function (e) {
            var catId = $(this).attr('data-cid');
            // append empty row
            var str = '<tr id="" class="row addMenuRow current" data-cid="' + catId + '" >';
            str += '<th class="name"></th>';
            str += '    <td></td>';
            str += '    <td><input class="txt sort" type="text" value="0" ></td>';
            str += '    <td><input class="txt isHide" type="text" value="0"/></td>';
            str += '    <td><input class="txt module" type="text" value="panel"/></td>';
            str += '    <td><input class="txt controller" type="text" value=""/></td>';
            str += '    <td><input class="txt action" type="text" value=""/></td>';
            str += '    <td><input class="txt menuTitle" type="text" value=""/></td>';
            str += '</tr>';
            // append
            $('#menus .listData').append(str);
            e.stopImmediatePropagation();
        });

        // submit
        var btn = '#setMenuBtn';
        $(btn).on('click', function (e) {
            var data = [];
            $('#menus .listData .row').each(function () {
                var id = $(this).attr('data-id');
                var cid = $(this).attr('data-cid');

                // change data
                var sort = $(this).find('.sort').val();
                var title = $(this).find('.menuTitle').val();
                var isHide = $(this).find('.isHide').val();
                var module = $(this).find('.module').val();
                var controller = $(this).find('.controller').val();
                var action = $(this).find('.action').val();

                //old data
                var old_order = $(this).find('.sort').attr('data-old');
                var old_title = $(this).find('.menuTitle').attr('data-old');
                var old_module = $(this).find('.module').attr('data-old');
                var old_controller = $(this).find('.controller').attr('data-old');
                var old_action = $(this).find('.action').attr('data-old');
                var old_isHide = $(this).find('.isHide').attr('data-old');

                if (!(sort == old_order && title == old_title && isHide == old_isHide && module == old_module && controller == old_controller && action == old_action)) {
                    if (controller && action && title && cid && isHide) {
                        var menu = {
                            id: id,
                            cid: cid,
                            title: title,
                            sort: sort,
                            is_hide: isHide,
                            module: module,
                            controller: controller,
                            action: action
                        };
                        data.push(menu);
                    }
                }
            });

            if (data.length == 0) {
                tip.showTip('err', '您未作任何的修改', 3000);
                return false;
            }

            // console.log(a);
            var data = {'menus': data}

            // disable the button
            $(btn).attr('disabled', true);
            // api request
            base.requestApi('/api/admin/setMenu', data, function (res) {
                if (res.result == 1) {
                    tip.showTip('ok', '恭喜您，导航更新成功！即将跳转', 1000);
                    setTimeout(function () {
                        window.location.reload();
                    }, 1000);
                }

                // cancel to disable the btn
                $(btn).attr('disabled', false);
            });
            e.stopImmediatePropagation();
        });


    };

    /**
     * menus cats
     */
    exports.setMenusCat = function () {

        $("table#menuCats .operate").on({
                mouseenter: function () {
                    $(this).find('.add').show();
                },
                mouseleave: function () {
                    $(this).find('.add').hide();
                }
            }
        );
        /**
         * expand the hide menus
         */
        $('table#menuCats .expand').on('click', function (e) {
            var status = $(this).attr('data-status');
            var cid = $(this).attr('data-cid');
            var txt = $(this).text();
            // current is hidden
            if (status == 'hide') {
                $('#menuCats tr[data-pid=' + cid + ']').show();
                $(this).attr('data-status', 'show');
                if (txt == '[ + ]') {
                    $(this).text('[ - ]');
                } else {
                    $(this).text('[-]');
                }
            } else {
                $('tr[data-pid=' + cid + ']').hide();
                $(this).attr('data-status', 'hide');
                if (txt == '[ - ]') {
                    $(this).text('[ + ]');
                } else {
                    $(this).text('[+]');
                }
            }
            e.stopImmediatePropagation();
        });

        /**
         * add action
         */
        $('table#menuCats .add').on('click', function (e) {
            var parent_id = $(this).attr('data-pid');
            $('.expand[data-cid=' + parent_id + ']').attr('data-status', 'hide').text('[+]').trigger('click');
            // append empty row
            var str = '<tr class="row level2" data-id="" data-pid="' + parent_id + '"  style="display:table-row;">';
            str += '    <th class="name"></th>';
            str += '    <td class="operate"></td>';
            str += '    <td><input class="txt sort" type="text" value="0" /></td>';
            str += '    <td><input class="txt isHide" type="text" value="0" /></td>';
            str += '    <td>|一一 <input class="txt catTitle" type="text" value="" /></td>';
            str += '    <td><input class="txt catUrl" type="text" value="" /></td>';
            str += '    <td><input class="txt catIcon" type="text" value="" /></td>';
            str += '</tr>';
            // append
            $('#menuCats tr[data-id=' + parent_id + ']').after(str);
            e.stopImmediatePropagation();
        });
        var btn = '#catDoBtn';
        $(btn).on('click', function (e) {
            var data = [];
            $('#menuCats .row').each(function () {
                var cid = $(this).attr('data-id');
                var parent_id = $(this).attr('data-pid');

                // change data
                var sort = $(this).find('.sort').val();
                var title = $(this).find('.catTitle').val();
                var url = $(this).find('.catUrl').val();
                var icon = $(this).find('.catIcon').val();
                var isHide = $(this).find('.isHide').val();

                //old data
                var old_order = $(this).find('.sort').attr('data-old-order');
                var old_title = $(this).find('.catTitle').attr('data-old-title');
                var old_url = $(this).find('.catUrl').attr('data-old-url');
                var old_icon = $(this).find('.catIcon').attr('data-old-icon');
                var old_isHide = $(this).find('.isHide').attr('data-old-isHide');

                if (!(sort == old_order && title == old_title && url == old_url && icon == old_icon && isHide == old_isHide)) {
                    var menu = {
                        id: cid,
                        parent_id: parent_id,
                        title: title,
                        sort: sort,
                        url: url,
                        icon: icon,
                        is_hide: isHide
                    };
                    data.push(menu);
                }
            });
            if (data.length == 0) {
                tip.showTip('err', '您未作任何的修改', 3000);
                return false;
            }
            //var a = JSON.stringify(data);
            // console.log(a);
            var data = {'menuCats': data}
            //console.log(data);
            // disable the button
            $(btn).attr('disabled', true);
            // api request
            base.requestApi('/api/admin/setMenuCat', data, function (res) {

                tip.showTip('ok', '恭喜您，导航更新成功！即将跳转', 1000);
                setTimeout(function () {
                    window.location.reload();
                }, 1000);

                // cancel to disable the btn
                $(btn).attr('disabled', false);
            });
            e.stopImmediatePropagation();
        });
    }

    /**
     * add admin user
     *
     * @param btn
     */
    exports.addAdminUser = function (btn) {
        $(btn).on('click', function () {
            // params
            var passwd = $('#addAdminForm #newPasswd').val();
            var user = $('#addAdminForm #user').val();
            var uid = $('#addAdminForm #infoBtn').attr('data-uid');
            var true_name = $('#addAdminForm #true_name').val();
            var email = $('#addAdminForm #email').val();
            var group_id = $('#addAdminForm #group_id').val()
            var data = {'user': user, 'true_name': true_name, 'email': email, 'group_id': group_id, 'passwd': passwd};

            // disable the button
            $(btn).attr('disabled', true);
            // api request
            base.requestApi('/api/admin/add', data, function (res) {
                if (res.result == 1) {
                    tip.showTip('ok', '管理员添加成功！即将跳转', 1000);
                    setTimeout(function () {
                        window.location.href = app.site_url + '/admin';
                    }, 1000);
                } else if (res.error.code == 10005) {
                    $('#addAdminForm #email').focus();
                    $('#addAdminForm #email').parent().find('#status').text('邮箱已经存在~！');
                } else if (res.error.code == 10003) {
                    $('#addAdminForm #user').focus();
                    $('#addAdminForm #user').parent().find('#status').text('用户已经存在~！');
                }

                // cancel to disable the btn
                $(btn).attr('disabled', false);
            });
        })
    };

    /**
     * update another admin info
     *
     * @param btn
     */
    exports.updateAdmin = function (btn) {
        $(btn).on('click', function () {
            var user = $('#updateAdminForm #user').val();
            var uid = $('#updateAdminForm #infoBtn').attr('data-uid');
            var true_name = $('#updateAdminForm #true_name').val();
            var email = $('#updateAdminForm #email').val();
            var group_id = $('#updateAdminForm #group_id').val()
            var data = {'user': user, 'true_name': true_name, 'email': email, 'group_id': group_id, 'uid': uid};

            // disable the button
            $(btn).attr('disabled', true);
            // api request
            base.requestApi('/api/admin/update', data, function (res) {
                tip.showTip('ok', '恭喜您，个人信息修改成功！', 1000);
                if (res.result == 1) {
                    $('#updateAdminForm #user').parent().find('#status').text('用于登陆');
                    $('#updateAdminForm #email').parent().find('#status').text('');
                } else if (res.error.code == 10005) {
                    $('#updateAdminForm #email').focus();
                    $('#updateAdminForm #email').parent().find('#status').text('邮箱已经存在~！');
                } else if (res.error.code == 10003) {
                    $('#updateAdminForm #user').focus();
                    $('#updateAdminForm #user').parent().find('#status').text('用户已经存在~！');
                }

                // unDisable button
                $(btn).attr('disabled', true);
            })
        });
    };

    /**
     * update another admin passwd
     *
     * @param btn
     */
    exports.resetPasswd = function upInfo(btn) {
        $(btn).on('click', function () {
            // params
            var newPasswd = $('#changePasswdForm #newPasswd').val();
            var uid = $('#changePasswdForm #passwdBtn').attr('data-uid');
            var data = {'uid': uid, 'newPasswd': newPasswd};

            // disable the button
            $(btn).attr('disabled', true);
            // api request
            base.requestApi('/api/admin/repass', data, function (res) {
                if (res.result == 1) {
                    tip.showTip('ok', '密码更新成功，下次登录有效！', 1000);
                    $('#changePasswdForm #oldPasswd').parent().find('#status').text('');
                }

                // unDisable button
                $(btn).attr('disabled', true);
            })

        });
    }


    /**
     * set group
     */
    exports.setGroup = function () {
        // first cat click
        $("#groupForm #addOption").on('click', function (e) {
            var str = '<tr class="row" >';
            str += '     <th class="name"></th>';
            str += '     <td><input class="txt group_name" type="text" value="" /></td>';
            str += '     <td><input class="txt group_desc" type="text" value="" /></td>';
            str += '     <td></td>';
            str += '</tr>';
            // append
            $("#groupForm .listData").append(str);
            e.stopImmediatePropagation();
        });

        var btn = '#groupForm #setGroupBtn';
        $(btn).on('click', function (e) {
            var data = [];
            $('#groupForm .list tr').each(function () {
                var id = $(this).attr('data-id');

                // change data
                var group_name = $(this).find('.group_name').val();
                var group_desc = $(this).find('.group_desc').val();

                //old data
                var old_name = $(this).find('.group_name').attr('data-old-name');
                var old_desc = $(this).find('.group_name').attr('data-old-desc');

                if (!(group_name == old_name && group_desc == old_desc)) {
                    if (group_name) {
                        var group = {
                            id: id,
                            name: group_name,
                            desc: group_desc
                        };
                        data.push(group);
                    }
                }
            });

            if (data.length == 0) {
                tip.showTip('err', '您未作任何的修改', 3000);
                return false;
            }
            //var a = JSON.stringify(data);
            // console.log(a);
            var data = {'groups': data};

            // disable the button
            $(btn).attr('disabled', true);
            // api request
            base.requestApi('/api/admin/upGroup', data, function (res) {

                if (res.result == 1) {
                    tip.showTip('ok', '恭喜您，管理员组设置成功！即将跳转', 1000);
                    setTimeout(function () {
                        window.location.reload();
                    }, 1000);
                }

                // cancel to disable the btn
                $(btn).attr('disabled', false);
            });
            e.stopImmediatePropagation();
        });
    }

    /**
     * set user permission
     */
    exports.setUserPermission = function () {
        $('#permissionForm .chk').on('click', function (e) {
            var perm = $(this).val();
            if (perm == 1) {
                $(this).val(0);
            } else {
                $(this).val(1);
            }
            e.stopImmediatePropagation();
        });

        // select all
        $('#permissionForm .selcAll').on('click', function () {
            var cid = $(this).attr('data-cid');
            var selc = cid ? '[data-cid=' + cid + ']' : '';
            $('#permissionForm .chk' + selc + ':enabled').each(function () {
                $(this).val(1);
                $(this).attr('checked', 'checked');
            });
        });

        // select none
        $('#permissionForm .selcNone').on('click', function () {
            var cid = $(this).attr('data-cid');
            var selc = cid ? '[data-cid=' + cid + ']' : '';
            $('#permissionForm .chk' + selc + ':enabled').each(function () {
                $(this).val(0);
                $(this).attr('checked', false);
            });
        });

        //
        var btn = '#permissionForm .setPermissionBtn';
        // set permission
        $(btn).on('click', function (e) {

            var data = [];
            var uid = $(this).attr('data-uid');

            $('#permissionForm .chk').each(function () {
                var perm_val = $(this).val();
                var old_perm = $(this).attr('data-old-perm');
                var perm_id = $(this).attr('data-perm-id');
                var menu_id = $(this).attr('data-menu-id');

                if (!(old_perm == perm_val)) {
                    var info = {'perm_id': perm_id, 'menu_id': menu_id, 'right_type': perm_val};
                    data.push(info);
                }
            });
            if (data.length == 0) {
                tip.showTip('err', '您未作任何的修改', 3000);
                return false;
            }
            var data = {'permissions': JSON.stringify(data), 'uid': uid};

            // disable the button
            $(btn).attr('disabled', true);
            // api request
            base.requestApi('/api/admin/setUserPerm', data, function (res) {

                if (res.result == 1) {
                    tip.showTip('ok', '恭喜您，管理员权限设置成功！即将跳转', 1000);
                    setTimeout(function () {
                        window.location.reload();
                    }, 1000);
                }

                // cancel to disable the btn
                $(btn).attr('disabled', false);
            });
            e.stopImmediatePropagation();
        })
    }

    /**
     * set user permission
     */
    exports.setGroupPermission = function () {
        $('#permissionForm .chk').on('click', function (e) {
            var perm = $(this).val();
            if (perm == 1) {
                $(this).val(0);
            } else {
                $(this).val(1);
            }
            e.stopImmediatePropagation();
        });

        // select all
        $('#permissionForm .selcAll').on('click', function () {
            var cid = $(this).attr('data-cid');
            var selc = cid ? '[data-cid=' + cid + ']' : '';
            $('#permissionForm .chk' + selc).each(function () {
                $(this).val(1);
                $(this).attr('checked', 'checked');
            });
        });

        // select none
        $('#permissionForm .selcNone').on('click', function () {
            var cid = $(this).attr('data-cid');
            var selc = cid ? '[data-cid=' + cid + ']' : '';
            $('#permissionForm .chk' + selc).each(function () {
                $(this).val(0);
                $(this).attr('checked', false);
            });
        });

        //
        var btn = '#permissionForm .setPermissionBtn';
        // set permission
        $(btn).on('click', function (e) {

            var data = [];
            var gid = $(this).attr('data-gid');

            $('#permissionForm .chk').each(function () {
                var perm_val = $(this).val();
                var old_perm = $(this).attr('data-old-perm');
                var perm_id = $(this).attr('data-perm-id');
                var menu_id = $(this).attr('data-menu-id');

                if (!(old_perm == perm_val)) {
                    var info = {'perm_id': perm_id, 'menu_id': menu_id, 'right_type': perm_val};
                    data.push(info);
                }
            });
            if (data.length == 0) {
                tip.showTip('err', '您未作任何的修改', 3000);
                return false;
            }
            var data = {'permissions': JSON.stringify(data), 'gid': gid}


            // disable the button
            $(btn).attr('disabled', true);
            // api request
            base.requestApi('/api/admin/setGroupPerm', data, function (res) {

                if (res.result == 1) {
                    tip.showTip('ok', '恭喜您，管理员权限设置成功！即将跳转', 1000);
                    setTimeout(function () {
                        window.location.reload();
                    }, 1000);
                }

                // cancel to disable the btn
                $(btn).attr('disabled', false);
            });
            e.stopImmediatePropagation();
        })
    }

});