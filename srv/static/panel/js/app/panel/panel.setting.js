/**
 * Created by ykuang on 2018/4/25.
 */
define(function (require, exports) {
    var base = require('app/panel/panel.base');
    var storage = require('app/panel/panel.storage');
    exports.getUser = function (filter) {
        base.requestApi('/api/admin/getUser', filter, function (res) {
            var html = res.data.data;
            //var result = res.data.res;
            //for (var i in result) {
            //    html += "<li style='cursor:pointer;user-select:none'>" +
            //        "<b><input type='checkbox'   data-phone='" + result[i]['phone'] +
            //        "'  data-username='" + result[i]['username'] +
            //        "'  data-avatar='" + result[i]['avatar'] +
            //        "' data-id='" + result[i]['id'] +
            //        "'  class='user chk ace' value='" + result[i]['id'] +
            //        "'/><span class='lbl'></span></b><img src='" + result[i]['avatar'] +
            //        "?x-oss-process=image/resize,m_fill,h_100,w_100'/>" + result[i]['username'] +
            //        " 【" + result[i]['id'] + "】<i class='fa " + (result[i]['sex'] == 1 ? 'fa-mars pink' : 'fa-venus blue') + "'></i><span class='right'>" + result[i]['created'] + "</span></li>" +
            //        "";
            //}
            html += "<div class='pageBar'>" + res.data.pageBar + "</div>";
            $(".userList").html(html);
        }, false, true);

    };
    exports.bindUser = function () {
        $(".btnAdd").on('click', function () {
            var app_uid = $.trim($("#app_uid").val());
            if (app_uid == '') {
                tip.showTip("err", '请填写app用户id号', 1000);
                $("#app_uid").focus();
                return false;
            }

            if (isNaN(app_uid) || app_uid.length < 5 || app_uid.length > 10) {
                tip.showTip("err", '无效的app用户id号', 1000);
                $("#app_uid").focus();
                return false;
            }
            base.requestApi('/api/admin/addAppUid', {app_uid: app_uid}, function (res) {
                if (res.result == 1) {
                    tip.showTip("ok", '关联成功', 1000, function () {
                        window.location.reload();
                    });
                }
            })
        });
        $(".removeBtn").on('click', function () {
            var app_uid = $(this).attr('data-id');
            if (app_uid) {
                base.requestApi('/api/admin/removeAppUid', {app_uid: app_uid}, function (res) {
                    if (res.result == 1) {
                        tip.showTip("ok", '取消关联成功', 1000, function () {
                            window.location.reload();
                        });
                    }
                })
            }
        });

        //编辑绑定账号信息
        $(".editBtn").on('click', function () {
            var $li = $('.editBtn').closest('li');
            var avatar = $li.find('img').attr('src').split('?')[0];
            var username = $.trim($li.find('p.username').text());
            var uid = $.trim($li.find('p.user_id').text());
            var $modal = $('#editUser');
            $modal.find('#pick-avatar').attr('src',avatar);
            $modal.find('input[name="avatar"]').val(avatar);
            $modal.find('input[name="username"]').val(username);
            $modal.find('input[name="uid"]').val(uid);
            $modal.find('input[name="birth"]').val($(this).data('birth'));
            $modal.find('input[name="sex"][value=' + $(this).data('sex') + ']').attr('checked',true);
            $modal.modal('show');
        });
        //选择头像
        storage.getImg('#selectAvator',function(res){
            $('#pick-avatar').attr('src',res.url);
            $('#editUser').find('input[name="avatar"]').val(res.url);
        });
        //保存用户信息
        $('#editUser').find('#saveBtn').on('click',function(){
            var data = $('#editUser').find('form').serialize();

            base.requestApi('/api/setting/editBoundUser',data,function (res) {
                if( res.result === 1 )
                {
                    tip.showTip('ok','编辑成功',800,function () {
                        $('#editUser').modal('hide');
                        window.location.reload();
                    });

                }
            });
        });


        //用户类型选择
        var page = 1;
        var key = "";
        var has_load = false;
        $(".selectUser").on('click', function () {
            if (!has_load) {
                /*初始化*/
                $(".checkAll").attr('checked', false);
                $(".selectedUser").html("");
                $(".avatar_list").html("");
                page = 1;
                exports.getUser({page: page, key: key});
                has_load = true;
            }
            $("#sendModal").modal("show");

        });
        //全选
        $(".checkAll").on('click', function (e) {

            if (!$(this).prop('checked')) {
                $(".user").each(function () {
                    //$(this).attr('checked', true);
                    if ($(this).prop('checked')) {
                        $(this).click()
                    }
                });
            } else {
                $(".user").each(function (e) {
                    if (!$(this).prop('checked')) {
                        $(this).click()
                    }
                    //$(this).attr('checked', false);
                });
            }
        });
        //双击移动用户
        $('.userList').on('dbclick', 'li', function () {
            var id = $(this).find('.user').attr('data-id');
            var username = $(this).find('.user').attr('data-username');
            var avatar = $(this).find('.user').attr('data-avatar');
            if ($(".selectedUser li[data-id='" + id + "']").length === 0) {
                html = "<li  data-id='" + id + "' data-avatar='" + avatar + "'><img src='" + avatar + "?x-oss-process=image/resize,m_fill,h_100,w_100'/>" + username + "<icon class='remove fa fa-remove'></icon></li>";
                $('.selectedUser').append(html);
            }

        });
        //批量移动用户
        $(".moveBtn").on('click', function () {
            var data = [];
            $(".modal-body .user").each(function () {
                if ($(this).is(":checked")) {
                    var id = $(this).attr('data-id');
                    var username = $(this).attr('data-username');
                    var phone = $(this).attr('data-phone');
                    var avatar = $(this).attr('data-avatar');
                    data.push({id: id, username: username, phone: phone, avatar: avatar});
                }
            });
            if (data.length == 0) {
                tip.showTip('err', '请先勾选用户再操作', 1000);
                return false;
            }
            var html = "";
            for (var i in data) {
                if ($(".selectedUser li[data-id='" + data[i]['id'] + "']").length === 0) {
                    html += "<li  data-id='" + data[i]['id'] + "' data-avatar='" + data[i]['avatar'] + "'><img src='" + data[i]['avatar'] + "?x-oss-process=image/resize,m_fill,h_100,w_100'/>" + data[i]['username'] + "<icon class='remove fa fa-remove'></icon></li>";
                }
            }
            $(".selectedUser").append(html);
        });
        //搜索框按下enter执行搜索
        $('.search').on('keydown', function (e) {
            if (e.keyCode === 13)
                $('.btnSearch').get(0).click();
        });
        //搜索按钮
        $(".btnSearch").on('click', function () {
            var value = $.trim($(".search").val());
            if (value != '') {
                page = 1;
                key = value;
                exports.getUser({page: page, key: key});
                $("#sendModal").modal("show");
            }
            else if (key != '') {
                page = 1;
                key = '';
                exports.getUser({page: page, key: key});
                $("#sendModal").modal("show");
            }
        });
        //移除用户
        $(".selectedUser").on('click', ".remove", function () {
            $(this).parent().remove();
        });
        //分页
        $('#sendModal').on('click', '.pageBar a', function () {
            page = $(this).attr('data-page');
            if (page >= 1) {
                exports.getUser({page: page, key: key});
            }
        });
        //确定选择
        $("#updateSureBtn").on('click', function () {
            uids = [];
            $(".selectedUser li").each(function () {
                uids.push($(this).data('id'))
            });
            if (uids.length == 0) {
                tip.showTip('err', '请先选择用户', 1000)
                return false
            }
            base.requestApi('/api/admin/addAppUids', {app_uid: uids}, function (res) {
                if (res.result == 1) {
                    tip.showTip("ok", '关联成功', 1000, function () {
                        window.location.reload();
                    });
                }
            })

        });
        //重新选择
        $(".avatar_list").on("click", ".btnReSelect", function () {
            $("#sendModal").modal("show");
        });

    }
})