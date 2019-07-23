define(function (require, exports) {
    var base = require('app/panel/panel.base');
    exports.edit = function () {
        $(".list").on('click', '.editBtn', function (e) {
            var content = base64.decode($(this).attr('data-content'));

            $("#msgModal .error-widget").hide();
            $("#msgModal .success-widget").hide();
            $("#msgModal .tpl_id_group").hide();
            $("#msgModal .submit_yunpian").show();

            $("#msgModal #content").val(content);
            $("#msgModal #sub_key").val($(this).attr('data-sub_key'));
            $("#msgModal #name").val($(this).attr('data-name'));
            $("#msgModal #tpl_id").val($(this).attr('data-tpl_id'));

            $("#msgModal #sureBtn").attr('data-original', $(this).attr('data-content'));
            $("#msgModal #message_id").val($(this).attr('data-id'));
            $("#msgModal .modal-title").html('短信模板编辑');
            $("#msgModal").modal('show');

            e.stopImmediatePropagation();
        });
        //添加模板
        $(".addTpl").on('click', function (e) {
            $("#msgModal .error-widget").hide();
            $("#msgModal .success-widget").hide();
            $("#msgModal .tpl_id_group").show();
            $("#msgModal .submit_yunpian").hide();

            $("#msgModal #content").val("");
            $("#msgModal #sub_key").val('');
            $("#msgModal #name").val('');
            $("#msgModal #tpl_id").val('');

            $("#msgModal #sureBtn").attr('data-original', '');
            $("#msgModal #message_id").val(0);
            $("#msgModal .modal-title").html('添加短信模板');
            $("#msgModal").modal('show');
            e.stopImmediatePropagation();
        })
        //更新
        $("#msgModal #sureBtn").on('click', function () {
            //  var original_content = base64.decode($(this).attr('data-original'));
            var content = $("#content").val().trim();
            var sub_key = $("#sub_key").val().trim();
            var name = $("#name").val().trim();
            var tpl_id = $("#tpl_id").val().trim();
            var id = $("#message_id").val();

            var unsubmit = 0;
            if (!($('#id-pills-stacked').attr('checked') == 'checked')) {
                if (tpl_id == '' || !/^[0-9]+$/.test(tpl_id)) {
                    $(".error-widget .error_msg").html("请填写云片模板id");
                    $(".error-widget").show();
                }
                unsubmit = 1;
            }
            /*  if (original_content == content) {
             base.showTip('err', '模板内容没有任何改变', 1000);
             return false;
             }*/
            if (content == '') {
                $("#msgModal .error-widget .error_msg").html("模板内容不能为空");
                $("#msgModal .error-widget").show();
                return false;
            }
            //修改模板
            if (id > 0) {
                $(this).confirm("确定要修改吗?", {
                    ok: function () {
                        base.requestApi('/api/message/smsUpdate', {
                            id: id,
                            content: content,
                            'sub_key': sub_key,
                            'name': name,
                            'unsubmit': unsubmit,
                            'tpl_id': tpl_id
                        }, function (res) {
                            if (res.result == 1) {
                                base.showTip('ok', res.data, 1000);
                                $("#msgModal .success-widget").show();
                                $("#msgModal .success-widget .success_msg").html(res.data);
                                $("#msgModal .error-widget").hide();
                                $(".modal-footer").hide();
                                $(".close").on('click', function () {
                                    window.location.reload();
                                })
                            } else {
                                $("#msgModal .success-widget").hide();
                                $("#msgModal .error-widget .error_msg").html(res.error.msg);
                                $("#msgModal .error-widget").show();
                            }
                        });
                    },
                    cancel: function () {
                        return false;
                    }
                });
            }
            //添加模板
            else {
                $(this).confirm("确定要添加吗?", {
                    ok: function () {
                        base.requestApi('/api/message/smsAdd', {
                            content: content,
                            'sub_key': sub_key,
                            'name': name,
                            'tpl_id': tpl_id
                        }, function (res) {
                            if (res.result == 1) {
                                base.showTip('ok', res.data, 1000);
                                $("#msgModal .success-widget").show();
                                $("#msgModal .success-widget .success_msg").html(res.data);
                                $("#msgModal .error-widget").hide();
                                $(".modal-footer").hide();
                                $(".close").on('click', function () {
                                    window.location.reload();
                                })
                            } else {
                                $("#msgModal .success-widget").hide();
                                $("#msgModal .error-widget .error_msg").html(res.error.msg);
                                $("#msgModal .error-widget").show();
                            }
                        });
                    },
                    cancel: function () {
                        return false;
                    }
                });
            }

            //$("#msgModal #content").val(content);
        });
        //删除
        $(".list .delBtn").on('click', function () {
            var id = $(this).attr('data-id');
            $(this).confirm("确定要删除吗?删除不可逆", {
                ok: function () {
                    base.requestApi('/api/message/smsRemove', {id: id}, function (res) {
                        if (res.result == 1) {
                            base.showTip('ok', res.data, 1000);
                            $(".success-widget").show();
                            $(".success-widget .success_msg").html(res.data);
                            $(".error-widget").hide();
                            $(".modal-footer").hide();
                            setTimeout(function () {
                                window.location.reload();
                            }, 1000)
                        } else {
                            $(".success-widget").hide();
                            $(".error-widget .error_msg").html(res.error.msg);
                            $(".error-widget").show();
                        }
                    });
                },
                cancel: function () {
                    return false;
                }
            });
            //$("#msgModal #content").val(content);
        });
        //查看云片信息
        $(".list .queryBtn").on('click', function () {
            var id = $(this).attr('data-tpl_id');

            base.requestApi('/api/message/query', {tpl_id: id}, function (res) {
                if (res.result == 1) {
                    $("#queryModal").modal('show');
                    if (res.data.tpl_id) {
                        $("#yunpian_content").val(res.data.tpl_content);
                        $("#yunpian_tpl_id").val(res.data.tpl_id);
                        var status = '';
                        if (res.data.check_status == 'SUCCESS') {
                            status = '<span class="badge badge-success">审核通过</span>';
                        } else if (res.data.check_status == 'FAIL') {
                            status = '<span class="badge badge-danger">审核失败</span>【' + res.data.reason + '】';
                        } else if (res.data.check_status == 'CHECKING') {
                            status = '<span class="badge badge-pink">正在审核</span>';
                        }
                        $(".checkStatus").html(status);
                    }
                }
            });
            //$("#msgModal #content").val(content);
        });

        $('#id-pills-stacked').on('click', function () {
            if ($(this).attr('checked') == 'checked') {
                $(".tpl_id_group").hide();
            } else {
                $(".tpl_id_group").show();
            }
        });

    };
    //系统消息编辑
    exports.sysEdit = function () {
        $(".list").on('click', '.editBtn', function (e) {
            var content = base64.decode($(this).attr('data-content'));

            $("#msgModal .error-widget").hide();
            $("#msgModal .success-widget").hide();

            $("#msgModal #content").val(content);
            $("#msgModal #sub_key").val($(this).attr('data-sub_key'));
            $("#msgModal #name").val($(this).attr('data-name'));

            $("#msgModal #sureBtn").attr('data-original', $(this).attr('data-content'));
            $("#msgModal #message_id").val($(this).attr('data-id'));
            $("#msgModal .modal-title").html('编辑系统消息模板');
            $("#msgModal").modal('show');
            e.stopImmediatePropagation();
        });
        //添加模板
        $(".addTpl").on('click', function (e) {
            $("#msgModal .error-widget").hide();
            $("#msgModal .success-widget").hide();
            $("#msgModal .tpl_id_group").show();
            $("#msgModal .submit_yunpian").hide();

            $("#msgModal #content").val("");
            $("#msgModal #sub_key").val('');
            $("#msgModal #name").val('');

            $("#msgModal #sureBtn").attr('data-original', '');
            $("#msgModal #message_id").val(0);
            $("#msgModal .modal-title").html('添加系统消息模板');
            $("#msgModal").modal('show');
            e.stopImmediatePropagation();
        })
        $("#msgModal #sureBtn").on('click', function () {
            //  var original_content = base64.decode($(this).attr('data-original'));
            var content = $("#content").val().trim();
            var sub_key = $("#sub_key").val().trim();
            var name = $("#name").val().trim();
            var id = $("#message_id").val();

            /*  if (original_content == content) {
             base.showTip('err', '模板内容没有任何改变', 1000);
             return false;
             }*/
            if (content == '') {
                $("#msgModal .error-widget .error_msg").html("模板内容不能为空");
                $("#msgModal .error-widget").show();
                return false;
            }
            //修改系统消息模板
            if (id > 0) {
                $(this).confirm("确定要修改吗?", {
                    ok: function () {
                        base.requestApi('/api/message/sysUpdate', {
                            id: id,
                            content: content,
                            'sub_key': sub_key,
                            'name': name
                        }, function (res) {
                            if (res.result == 1) {
                                base.showTip('ok', res.data, 1000);
                                $("#msgModal .success-widget").show();
                                $("#msgModal .success-widget .success_msg").html(res.data);
                                $("#msgModal .error-widget").hide();
                                $(".modal-footer").hide();
                                $(".close").on('click', function () {
                                    window.location.reload();
                                })
                            } else {
                                $("#msgModal .success-widget").hide();
                                $("#msgModal .error-widget .error_msg").html(res.error.msg);
                                $("#msgModal .error-widget").show();
                            }
                        });
                    },
                    cancel: function () {
                        return false;
                    }
                });
            }
            //添加系统消息模板
            else {
                $(this).confirm("确定要添加吗?", {
                    ok: function () {
                        base.requestApi('/api/message/sysAdd', {
                            id: id,
                            content: content,
                            'sub_key': sub_key,
                            'name': name
                        }, function (res) {
                            if (res.result == 1) {
                                base.showTip('ok', res.data, 1000);
                                $("#msgModal .success-widget").show();
                                $("#msgModal .success-widget .success_msg").html(res.data);
                                $("#msgModal .error-widget").hide();
                                $(".modal-footer").hide();
                                $(".close").on('click', function () {
                                    window.location.reload();
                                })
                            } else {
                                $("#msgModal .success-widget").hide();
                                $("#msgModal .error-widget .error_msg").html(res.error.msg);
                                $("#msgModal .error-widget").show();
                            }
                        });
                    },
                    cancel: function () {
                        return false;
                    }
                });
            }

        });
        //删除
        $(".list .delBtn").on('click', function () {
            var id = $(this).attr('data-id');
            $(this).confirm("确定要删除吗?删除不可逆", {
                ok: function () {
                    base.requestApi('/api/message/sysRemove', {id: id}, function (res) {
                        if (res.result == 1) {
                            base.showTip('ok', res.data, 1000);
                            $(".success-widget").show();
                            $(".success-widget .success_msg").html(res.data);
                            $(".error-widget").hide();
                            $(".modal-footer").hide();
                            setTimeout(function () {
                                window.location.reload();
                            }, 1000)
                        } else {
                            $(".success-widget").hide();
                            $(".error-widget .error_msg").html(res.error.msg);
                            $(".error-widget").show();
                        }
                    });
                },
                cancel: function () {
                    return false;
                }
            });
            //$("#msgModal #content").val(content);
        });
    };
    exports.addHotQuestion = function () {

        //添加模态框显示时处理事件
        $('#msgModal').on('show.bs.modal', function () {
            $('#content').on('keydown input',function(){
                var len = $(this).val().length;
                $('#cur_num').html(len);
                if( len >= 50 && "8,37,38,39,40".indexOf(event.keyCode) === -1 )
                {
                    $('#error-tips').removeClass('hide');
                    $('#error-tips').addClass('animated flash');
                    $('#error-tips').one('webkitAnimationEnd mozAnimationEnd MSAnimationEnd oanimationend animationend', function () {
                        var _this = this;
                        setTimeout(function () {
                            $(_this).removeClass('animated flash')
                            $(_this).addClass('hide')
                        },3000);


                    });
                    if(event.keyCode == 32)
                    {
                        $(this).val($(this).val().substring(0,50));
                        $('#cur_num').html($(this).val().length);
                    }
                    return false;
                }
            });

        });
        //添加问题
        $('.addTpl').on('click',function () {
            if( $('tr.item').length >= 50 )
            {
                alert('最多维护50条热门问题');
                return;
            }
            $('#sureBtn').on('click',function(){
                if( $('#content').val().length > 50 )
                {
                    $('#error-tips').removeClass('hide');
                    $('#error-tips').addClass('animated flash');
                    $('#error-tips').one('webkitAnimationEnd mozAnimationEnd MSAnimationEnd oanimationend animationend', function () {
                        var _this = this;
                        setTimeout(function () {
                            $(_this).removeClass('animated flash');
                            $(_this).addClass('hide')
                        },3000);


                    });
                    return false;
                }

                var content = $('#content').val().trim();
                var weight = $('#weight').val();
                var data_id = $('#data-id').val();
                base.requestApi('/api/message/updateHotQuestion',{data_id:data_id,type:'add',content:content,weight:weight},function (res) {
                    if( res.result == 1)
                    {
                        $('#msgModal').modal('hide');
                        base.showTip('ok','添加成功',1000,function () {
                            window.location.reload();
                        });
                    }
                })
            });
            $('#content').val('');
            $('#msgModal').modal('show');
        });

        //编辑问题
        $('.editBtn').on('click',function(){
            var _this = this,
                item_key = $(_this).closest('tr').attr('data-key'),
                weight = $(_this).closest('tr').find('td:eq(2)').text().trim(),
                content = $(_this).closest('tr').find('td:eq(1)').text().trim();

            $('#cur_num').html(content.length);
            $('#item-key').val(item_key);
            $('#weight').val(weight);
            $('#content').val(content);
            $('#sureBtn').on('click',function(){
                if( $('#content').val().length > 50 )
                {
                    $('#error-tips').removeClass('hide');
                    $('#error-tips').addClass('animated flash');
                    $('#error-tips').one('webkitAnimationEnd mozAnimationEnd MSAnimationEnd oanimationend animationend', function () {
                        var _this = this;
                        setTimeout(function () {
                            $(_this).removeClass('animated flash');
                            $(_this).addClass('hide')
                        },3000);


                    });
                    return false;
                }
                var new_content = $('#content').val().trim();
                $('#msgModal').modal('hide');
                var item_key = $('#item-key').val(),
                    weight = $('#weight').val(),
                    data_id = $('#data-id').val();
                base.requestApi('/api/message/updateHotQuestion',{data_id:data_id,type:'edit',item_key:item_key,content:new_content,weight:weight},function (res) {
                    if( res.result == 1)
                    {
                        base.showTip('ok','修改成功',1000,function () {
                           window.location.reload()
                        });
                    }
                })
            });
            $('#msgModal').modal('show');
        });
       //删除问题
        $('.delBtn').on('click',function(){
            var _this = this,
                item_key = $(_this).closest('tr').attr('data-key'),
                data_id = $('#data-id').val();
            $(_this).confirm("确定删除该条问题？", {
                ok: function() {
                    $(_this).closest('tr').remove();
                    var data = $('form').serialize();
                    base.requestApi('/api/message/updateHotQuestion',{data_id:data_id,type:'del',item_key:item_key},function (res) {
                        if( res.result == 1)
                        {
                            base.showTip('ok','删除成功',1000,function () {
                                window.location.reload()
                            });
                        }
                    })
                },
                cancel: function() {
                    $('#msgModal').modal('hide')
                }
            });

        });
    }
})
;
