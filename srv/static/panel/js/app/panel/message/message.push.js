define(function (require, exports) {
        var base = require('app/panel/panel.base');
        var store = require('app/panel/panel.storage');
        var uploader = require('app/panel/message/upload.js?v=1.0.3');

        exports.tip = function (type, msg) {
            if (type == 'err') {
                tip.showTip("err", msg, 1000);
                $(".error_msg").html(msg);
                $(".error-widget").show();
                $(".success-widget").hide();
            } else if (type == 'ok') {
                $(".success_msg").html(msg);
                $(".success-widget").show();
                $(".error-widget").hide();
            } else {
                $(".success-widget").hide();
                $(".error-widget").hide();
            }
        };
        exports.getUser = function (filter) {
            base.requestApi('/api/message/getUser', filter, function (res) {
                var html = "";
                var result = res.data.res;
                for (var i in result) {
                    html += "<li style='cursor:pointer;user-select:none'><input type='checkbox'  data-phone='" + result[i]['phone'] +
                        "'  data-username='" + result[i]['username'] +
                        "'  data-avatar='" + result[i]['avatar'] +
                        "' data-id='" + result[i]['id'] +
                        "'  class='user' value='" + result[i]['id'] +
                        "'/><img src='" + result[i]['avatar'] +
                        "?x-oss-process=image/resize,m_fill,h_100,w_100'/>" + result[i]['username'] +
                        " 【" + result[i]['id'] + "】<i class='fa " + (result[i]['sex'] == 1 ? 'fa-mars pink' : 'fa-venus blue') + "'></i><span class='right'>" + result[i]['created'] + "</span></li>" +
                        "";
                }
                html += "<div class='pageBar'>" + res.data.pageBar + "</div>";
                $(".userList").html(html);
            }, false, true);

        };
        exports.commit = function (action) {
            var url_pattern = /((http[s]{0,1}|ftp):\/\/[a-zA-Z0-9\.\-]+\.([a-zA-Z]{2,4})(:\d+)?(\/[a-zA-Z0-9\.\-~!@\#$%^&*+?:_\/=]*)?)|(www.[a-zA-Z0-9\.\-]+\.([a-zA-Z]{2,4})(:\d+)?(\/[a-zA-Z0-9\.\-~!@\#$%^&*+?:_\/=]*)?)/i;
            var msg_type = $(".message_type:checked").val(); //消息类型
            var user_type = $(".user_type:checked").val(); //用户类型
            var message = $("#message").val(); //推送消息

            var data = {
                msg_type: msg_type,
                user_type: user_type,
                message: message
            };
            //文字链接
            if (msg_type == '1') {
                var content = $.trim($(".trumbowyg-editor").html());
                if (content == '') {
                    exports.tip('err', '内容不能为空');
                    return;
                }
                data.content = content;
            }
            //单图加标题加链接
            else if (msg_type == '2') {
                var component = $(".tpl2Component");
                var title = $.trim(component.find('.title').val());
                var link = $.trim(component.find('.link').val());
                var thumb = $.trim(component.find('.thumb').attr('src'));


                if (title == '') {
                    exports.tip('err', '标题不能为空');
                    component.find('.title').focus();
                    return;
                }
                if (link == '') {
                    exports.tip('err', '请填写链接地址');
                    component.find('.link').focus();
                    return;
                }
                if (!url_pattern.test(link)) {
                    exports.tip('err', '请填写正确的链接地址');
                    component.find('.link').focus();
                    return;
                }
                if (thumb == '') {
                    exports.tip('err', '请选择图片');
                    return;
                }
                data.title = title;
                data.link = link;
                data.thumb = component.find('.thumb').attr('src') + '?' + component.find('.thumb').attr('data-width') + 'x' + component.find('.thumb').attr('data-height');

            }
            //多图加标题加链接
            else if (msg_type == '3') {
                var component = $(".tpl3Component");
                var media_data = [];
                var flag = true;
                component.find(".tpl3_model").each(function () {
                    var title = $.trim($(this).find('.title').val());
                    var link = $.trim($(this).find('.link').val());
                    var thumb = $.trim($(this).find('.thumb').attr('src'));
                    if (title == '') {
                        exports.tip('err', '标题不能为空');
                        $(this).find('.title').focus();
                        flag = false;

                        $(this).css({'border': '1px solid #b94a48'})
                        return false;
                    }
                    if (link == '') {
                        exports.tip('err', '请填写链接地址');
                        $(this).find('.link').focus();
                        $(this).css({'border': '1px solid #b94a48'})

                        flag = false;
                        return false;
                    }
                    if (!url_pattern.test(link)) {
                        exports.tip('err', '请填写正确的链接地址');
                        $(this).find('.link').focus();
                        $(this).css({'border': '1px solid #b94a48'});

                        flag = false;
                        return false;
                    }
                    if (thumb == '') {
                        exports.tip('err', '请选择图片');
                        $(this).css({'border': '1px solid #b94a48'});

                        flag = false;
                        return false;
                    }
                    $(this).css({'border': 'none'});
                    media_data.push({
                        title: title,
                        'link': link,
                        'thumb': $(this).find('.thumb').attr('src') + '?' + $(this).find('.thumb').attr('data-width') + 'x' + $(this).find('.thumb').attr('data-height')
                    })
                });
                if (!flag) {
                    return false;
                }
                data.media_data = media_data;
            }
            // 部分用户发送
            if (user_type == '2') {
                if ($(".selectedUser li").length == 0) {
                    exports.tip('err', '请选择相关用户');
                    exports.getUser({page: page, key: key});
                    return;
                }
                var uids = '';
                $(".selectedUser li").each(function () {
                    uids += "," + $(this).attr('data-id');
                });
                data.uids = uids.substr(1);
            }
            exports.tip('hide');
            //action = 1 立即推送； action =2 定时推送
            if( action === 1)
            {
                base.requestApi('/api/message/push', {data: data}, function (res) {
                    if (res.result == 1) {
                        tip.showTip("ok", '发送成功', 1000);
                    }
                })
            }else if( action === 2)
            {
                var timing = $('#timing').val();
                data.timing = timing;
                base.requestApi('/api/message/store', {data: data}, function (res) {
                    if (res.result === 1) {
                        tip.showTip("ok", '操作成功', 1000);
                    }
                })
            }


        };
        exports.push = function () {
            $(function () {
                uploader.uploadImg('.upload-widget[data-unique="0"]', {'type': 'img'}, function (res) {
                });
                uploader.uploadImg('.upload-widget[data-unique="1"]', {'type': 'img'}, function (res) {
                });
            });

            //选择定时消息推送时间
            $('#timing').datetimepicker({
                lang: "ch",
                step: 30,
                minDate:0,
                format: "Y-m-d H:i",
                onChangeDateTime: function () {
                }
            });
            //选择时间完成
            $('#complete-select').on('click',function () {
                var timing = $.trim($('#timing').val());
                if( timing === '')
                {
                    tip.showTip('err','请选择定时时间',2000);
                    return false;
                }
                if( Date.parse(timing) <= new Date().getTime()  )
                {
                    tip.showTip('err','定时时间不能小于当前时间',2000);
                    return false;
                }
                $('#select-date').modal('hide');
                exports.commit(2);//action =2 定时推送
            });
            //消息类型 -封面 选择
            $(".cover").on('click', function () {
                var parent = $(this).closest("div");
                parent.addClass('current').siblings().removeClass('current');
                $(".message_type[value='" + parent.data('id') + "']").click();
                $(".tplComponent").hide();
                $(".tpl" + parent.data('id') + "Component").show();

            });

            //消息类型选择
            $(".message_type").on('change', function () {
                $(".tpl_block").removeClass("current");
                $(".tpl_block[data-id='" + $(this).val() + "']").addClass("current");
                $(".tplComponent").hide();
                $(".tpl" + $(this).val() + "Component").show();
                if ($(this).val() != 1) {
                    $(".pushContent").show()
                } else {
                    $(".pushContent").hide()
                }
            });

            //用户类型选择
            var page = 1;
            var key = "";
            var has_load = false;
            $(".user_type").on('change', function () {
                if ($(this).val() == '2') {
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
                } else {
                    $(".avatar_list").html("");
                    $("#sendModal").modal("hide");
                }

            });
            //全选
            $(".checkAll").on('click', function () {
                if ($(this).is(':checked')) {
                    $(".user").each(function () {
                        $(this).attr('checked', true);
                    });
                } else {
                    $(".user").each(function () {
                        $(this).attr('checked', false);
                    });
                }
            });
            //双击移动用户
            $('.userList').on('click','li',function () {
                var id = $(this).find('.user').attr('data-id');
                var username = $(this).find('.user').attr('data-username');
                var avatar = $(this).find('.user').attr('data-avatar');
                if ($(".selectedUser li[data-id='" + id + "']").length === 0) {
                    html ="<li  data-id='" + id + "' data-avatar='" + avatar + "'><img src='" + avatar + "?x-oss-process=image/resize,m_fill,h_100,w_100'/>" + username + "<icon class='remove fa fa-remove'></icon></li>";
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
            $('.search').on('keydown',function (e) {
                if(e.keyCode === 13)
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
            $('#sendModal').on('click','.pageBar a', function () {
                page = $(this).attr('data-page');
                if (page >= 1) {
                    exports.getUser({page: page, key: key});
                }
            });
            //确定选择
            $("#updateSureBtn").on('click', function () {
                $(".avatar_list").html("");

                if ($(".selectedUser li").length > 0) {
                    $(".avatar_list").html("<span class='btn btn-sm btn-purple btnReSelect'>重新选择</span>")
                    $(".selectedUser li").each(function () {
                        $(".avatar_list").append("<img src='" + $(this).attr('data-avatar') + "?x-oss-process=image/resize,m_fill,h_100,w_100'/>");
                    });

                }
                $("#sendModal").modal("hide");
            });
            //重新选择
            $(".avatar_list").on("click", ".btnReSelect", function () {
                $("#sendModal").modal("show");
            });


            var editor = $('.message_box').trumbowyg({
                btns: ['link'],
                lang: 'zh_cn'
            });


            //添加 消息体
            var tpl3_model_index = 1;
            $(".plus").on('click', function () {
                var content = $(".tpl3_model[data-id='1']").clone();
                tpl3_model_index++;
                content.css({'border': 'none'});
                content.attr('data-id', tpl3_model_index);

                content.find(".fa-plus").removeClass('fa-plus').addClass('fa-minus');
                content.find(".btn_wrap").find('label').html("移除该组");
                content.find(".plus").removeClass('plus').addClass('minus');
                content.find(".upload-widget").attr('data-unique', tpl3_model_index);
                content.find(".chose-existing").attr('id', 'selection-' + tpl3_model_index).val('');
                content.find(".thumb").attr('id', 'thumb_' + tpl3_model_index).attr('src', '');
                content.find(".title").val('');
                content.find(".link").val('http://');

                $(".tpl3Component").append(content);
                uploader.uploadImg('.upload-widget[data-unique="' + tpl3_model_index + '"]', {'type': 'img'}, function (res) {
                });
                //删除消息体
                $(".minus").on('click', function () {
                    $(this).parent().parent().remove();
                })
            });
            //发送消息
            /*$(".commit").on('click', function () {
                exports.commit();
            });*/
            $(".saveBtn").on('click', function () {
                exports.commit(1);
            });
            $(".timingBtn").on('click', function () {
                //exports.commit(2);
                $('#select-date').modal('show');
            });

            //选择现有图文
            $('.tpl2Component,.tpl3Component').on('change','.chose-existing',function(){
                var id = $(this).val();
                var _this = this;
                if(id !== '')
                {
                    base.requestApi('/api/message/getMaterialDetail',{id:id},function (res) {
                        if(res.result === 1)
                        {
                            var imgSize = res.data.thumb.split('_s_')[1].split('.')[0].split('x');
                            var $wrapper = $(_this).closest('article');
                            $wrapper.find('.title').val(res.data.title);
                            $wrapper.find('.link').val(res.data.link);
                            $wrapper.find('.thumb').attr('src',res.data.thumb).attr('data-width',imgSize[0]).attr('data-height',imgSize[1]).show();
                        }
                    },true,true)
                }

            });


        }
    }
)