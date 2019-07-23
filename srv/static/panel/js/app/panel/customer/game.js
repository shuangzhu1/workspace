/**
 * Created by ykuang on 2016/12/19.
 */
define(function (require, exports) {
    var base = require('app/panel/panel.base');//公共函数
    var storage = require('app/panel/panel.storage.js?v=1.0');//storage

    base.selectNone();
    base.selectCheckbox();
    exports.GameList = function () {
        storage.getImg('#upTagThumb', function (res) {
            $('#thumb').val(res.url);
            $('.preview-thumb').attr('src', res.url);
        }, false);
        var modal = $("#tagModal");
        //游戏编辑
        $(".listData").on('click', '.editBtn', function () {
            modal.find("#name").val($(this).data('name'));
            /* modal.find("#sort_num").val($(this).data('sort_num'));*/
            modal.find("#game_id").val($(this).data('id'));
            modal.find("#thumb").val($(this).data('thumb'));
            modal.find("#customer").val($(this).data('customer'));
            modal.find("#apk_sign").val($(this).data('apk_sign'));
            modal.find("#package_id").val($(this).data('package_id'));
            modal.find("#bundle_id").val($(this).data('bundle_id'));
            modal.find("#dev_bundle_id").val($(this).data('dev_bundle_id'));
            /*  modal.find("#url").val($(this).data('url'));*/
            modal.find(".preview-thumb").attr('src', $(this).data('thumb'));
            if (($(this).data('status') == '1' && !modal.find("#status").prop('checked')) || ($(this).data('status') == '0' && modal.find("#status").attr('checked'))) {
                modal.find("#status").click();
            }
            /*  if (($(this).data('support_login') == '1' && !modal.find("#support_login").prop('checked')) || ($(this).data('support_login') == '0' && modal.find("#support_login").attr('checked'))) {
             modal.find("support_login").click();
             }*/
            modal.find(".error-widget").hide();
            modal.find(".success-widget").hide();
            modal.find('.modal-title').html("游戏编辑");
            modal.modal('show');
        });
        //添加游戏
        $(".btnAdd").on('click', function () {
            modal.find("#name").val('');
            /*  modal.find("#sort_num").val(50);*/
            modal.find("#game_id").val(0);
            modal.find("#thumb").val('');
            modal.find("#apk_sign").val('');
            modal.find("#package_id").val('');
            modal.find("#bundle_id").val('');
            modal.find("#dev_bundle_id").val('');
            /*      modal.find("#url").val('');*/
            modal.find(".preview-thumb").attr('src', '');
            modal.find(".error-widget").hide();
            modal.find(".success-widget").hide();
            modal.find('.modal-title').html("添加游戏");
            if (!(modal.find("#status").is(":checked"))) {
                modal.find("#status").click();
            }
            /*   if (!(modal.find("#support_login").is(":checked"))) {
             modal.find("#support_login").click();
             }*/
            modal.modal('show');
        });
        //禁用
        $(".listData").on('click', '.lockBtn', function () {
            var game_id = $(this).attr('data-id');
            $(this).confirm("确定要禁用吗?", {
                ok: function () {
                    base.requestApi('/api/game/lock', {
                        game_id: game_id,
                    }, function (res) {
                        if (res.result == 1) {
                            base.showTip('ok', res.data, 1000, function () {
                                window.location.reload()
                            });
                        } else {
                        }
                    });
                },
                cancel: function () {
                    return false;
                }
            });
        });
        //解除禁用
        $(".listData").on('click', ".unLockBtn", function () {
            var game_id = $(this).attr('data-id');
            $(this).confirm("确定要解除禁用吗?", {
                ok: function () {
                    base.requestApi('/api/game/unLock', {
                        game_id: game_id,
                    }, function (res) {
                        if (res.result == 1) {
                            base.showTip('ok', res.data, 1000, function () {
                                window.location.reload()
                            });
                        } else {
                        }
                    });
                },
                cancel: function () {
                    return false;
                }
            });
        });
        //删除
        $(".listData").on('click', ".delBtn", function () {
            var game_id = $(this).attr('data-id');
            $(this).confirm("确定要删除吗?", {
                ok: function () {
                    base.requestApi('/api/game/remove', {
                        game_id: game_id,
                    }, function (res) {
                        if (res.result == 1) {
                            base.showTip('ok', res.data, 1000, function () {
                                window.location.reload()
                            });
                        } else {
                        }
                    });
                },
                cancel: function () {
                    return false;
                }
            });
        });
        //确定
        modal.find("#sureBtn").on('click', function () {
            var game_id = modal.find("#game_id").val();
            var name = modal.find("#name").val().trim();
            var thumb = modal.find("#thumb").val().trim();
            var customer = modal.find("#customer").val();
            var apk_sign = modal.find("#apk_sign").val();
            var package_id = modal.find("#package_id").val();
            var bundle_id = modal.find("#bundle_id").val();
            var dev_bundle_id = modal.find("#dev_bundle_id").val();

            /*  var url = modal.find("#url").val();*/
            /* var sort_num = parseInt(modal.find("#sort_num").val().trim());*/
            var status = 1;
            //var support_login = 1;
            if (!(modal.find("#status").is(":checked"))) {
                status = 2;
            }
            /*   if (!(modal.find("#support_login").is(":checked"))) {
             support_login = 0;
             }*/
            if (name == '') {
                modal.find(".error-widget .error_msg").html("请输入标签名称");
                modal.find(".error-widget").show();
                return false;
            }
            if (!customer) {
                modal.find(".error-widget .error_msg").html("请选择游戏供应商");
                modal.find(".error-widget").show();
                return false;
            }
            /*   if (!apk_sign) {
             modal.find(".error-widget .error_msg").html("请填写apk签名");
             modal.find(".error-widget").show();
             return false;
             }*/
            /* if (!url) {
             modal.find(".error-widget .error_msg").html("请填写链接地址");
             modal.find(".error-widget").show();
             return false;
             }*/
            //编辑游戏
            if (game_id > 0) {
                /*   $(this).confirm("确定要修改吗?", {
                 ok: function () {*/
                base.requestApi('/api/game/edit', {
                    game_id: game_id,
                    name: name,
                    /*   sort_num: sort_num,*/
                    status: status,
                    apk_sign: apk_sign,
                    package_id:package_id,
                    bundle_id: bundle_id,
                    dev_bundle_id: dev_bundle_id,
                    thumb: thumb,
                    /* url: url,*/
                    /*    support_login: support_login,*/
                    customer: customer
                }, function (res) {
                    if (res.result == 1) {
                        base.showTip('ok', res.data, 1000);
                        modal.find(".success-widget").show();
                        modal.find(".success-widget .success_msg").html(res.data);
                        modal.find(".error-widget").hide();

                        setTimeout(function () {
                            window.location.reload();
                        }, 2000);
                        $(".close").on('click', function () {
                            window.location.reload();
                        })
                    } else {
                        modal.find(".success-widget").hide();
                        modal.find(".error-widget .error_msg").html(res.error.msg);
                        modal.find(".error-widget").show();
                    }
                });
                /*   },
                 cancel: function () {
                 return false;
                 }
                 });*/
            }
            //添加游戏
            else {
                /*   $(this).confirm("确定要添加吗?", {
                 ok: function () {*/
                base.requestApi('/api/game/edit', {
                    game_id: game_id,
                    name: name,
                    apk_sign: apk_sign,
                    package_id:package_id,
                    bundle_id: bundle_id,
                    dev_bundle_id: dev_bundle_id,
                    /*      sort_num: sort_num,*/
                    status: status,
                    thumb: thumb,
                    /*  url: url,*/
                    customer: customer
                    /*  support_login: support_login*/
                }, function (res) {
                    if (res.result == 1) {
                        base.showTip('ok', res.data, 1000);
                        modal.find(".success-widget").show();
                        modal.find(".success-widget .success_msg").html(res.data);
                        modal.find(".error-widget").hide();
                        setTimeout(function () {
                            window.location.reload();
                        }, 2000);
                        $(".close").on('click', function () {
                            window.location.reload();
                        })

                    } else {
                        modal.find(".success-widget").hide();
                        modal.find(".error-widget .error_msg").html(res.error.msg);
                        modal.find(".error-widget").show();
                    }
                });
                /*  },
                 cancel: function () {
                 return false;
                 }
                 });*/
            }
        })

    };
    exports.CustomerList = function () {
        storage.getImg('.upTagThumb', function (res,btnObj) {
            $(btnObj).prev().val(res.url);
            $(btnObj).closest('div').parent().next().find('.preview-thumb').attr('src', res.url);
        }, false);
        var modal = $("#tagModal");
        //商家编辑
        $(".listData").on('click', '.editBtn', function () {
            modal.find("#name").val($(this).data('name'));
            modal.find("#customer_id").val($(this).data('id'));
            modal.find("#thumb").val($(this).data('thumb'));
            modal.find("#ncbl").val($(this).data('ncbl'));
            modal.find("#icp").val($(this).data('icp'));
            modal.find("#bp").val($(this).data('bp'));

            modal.find(".preview-thumb").each(function () {
                $(this).attr('src',$(this).parent().prev().find('input.img').val());
            });//attr('src', $(this).data('thumb'));
            if (($(this).data('status') == '1' && !modal.find("#status").prop('checked')) || ($(this).data('status') == '0' && modal.find("#status").attr('checked'))) {
                modal.find("#status").click();
            }
            modal.find(".error-widget").hide();
            modal.find(".success-widget").hide();
            modal.find('.modal-title').html("游戏编辑");
            modal.modal('show');
        });
        //添加商家
        $(".btnAdd").on('click', function () {
            modal.find("#name").val('');
            modal.find("#customer_id").val(0);
            modal.find("#thumb").val('');
            modal.find("#ncbl").val('');
            modal.find("#icp").val('');
            modal.find("#bp").val('');
            modal.find(".preview-thumb").attr('src', '');
            modal.find(".error-widget").hide();
            modal.find(".success-widget").hide();
            modal.find('.modal-title').html("添加游戏");
            if (!(modal.find("#status").is(":checked"))) {
                modal.find("#status").click();
            }
            modal.modal('show');
        });
        //禁用
        $(".listData").on('click', '.lockBtn', function () {
            var customer_id = $(this).attr('data-id');
            $(this).confirm("确定要禁用吗?", {
                ok: function () {
                    base.requestApi('/api/game/lockCustomer', {
                        customer_id: customer_id,
                    }, function (res) {
                        if (res.result == 1) {
                            base.showTip('ok', res.data, 1000, function () {
                                window.location.reload()
                            });
                        } else {
                        }
                    });
                },
                cancel: function () {
                    return false;
                }
            });
        });
        //解除禁用
        $(".listData").on('click', ".unLockBtn", function () {
            var customer_id = $(this).attr('data-id');
            $(this).confirm("确定要解除禁用吗?", {
                ok: function () {
                    base.requestApi('/api/game/unLockCustomer', {
                        customer_id: customer_id,
                    }, function (res) {
                        if (res.result == 1) {
                            base.showTip('ok', res.data, 1000, function () {
                                window.location.reload()
                            });
                        } else {
                        }
                    });
                },
                cancel: function () {
                    return false;
                }
            });
        });
        //确定
        modal.find("#sureBtn").on('click', function () {
            var customer_id = modal.find("#customer_id").val();
            var name = modal.find("#name").val().trim();
            var thumb = modal.find("#thumb").val().trim();
            var ncbl = modal.find("#ncbl").val().trim();
            var icp = modal.find("#icp").val().trim();
            var bp = modal.find("#bp").val().trim();
            var status = 1;
            if (!(modal.find("#status").is(":checked"))) {
                status = 2;
            }
            if (name == '') {
                modal.find(".error-widget .error_msg").html("请输入商家名称");
                modal.find(".error-widget").show();
                return false;
            }
            //编辑商家
            if (customer_id > 0) {
                /*   $(this).confirm("确定要修改吗?", {
                 ok: function () {*/
                base.requestApi('/api/game/editCustomer', {
                    customer_id: customer_id,
                    name: name,
                    status: status,
                    thumb: thumb,
                    ncbl: ncbl,
                    icp: icp,
                    bp: bp,
                }, function (res) {
                    if (res.result == 1) {
                        base.showTip('ok', res.data, 1000);
                        modal.find(".success-widget").show();
                        modal.find(".success-widget .success_msg").html(res.data);
                        modal.find(".error-widget").hide();

                        setTimeout(function () {
                            window.location.reload();
                        }, 2000);
                        $(".close").on('click', function () {
                            window.location.reload();
                        })
                    } else {
                        modal.find(".success-widget").hide();
                        modal.find(".error-widget .error_msg").html(res.error.msg);
                        modal.find(".error-widget").show();
                    }
                });
                /*   },
                 cancel: function () {
                 return false;
                 }
                 });*/
            }
            //添加商家
            else {
                /*   $(this).confirm("确定要添加吗?", {
                 ok: function () {*/
                base.requestApi('/api/game/editCustomer', {
                    customer_id: customer_id,
                    name: name,
                    status: status,
                    thumb: thumb,
                    ncbl: ncbl,
                    icp: icp,
                    bp: bp,
                }, function (res) {
                    if (res.result == 1) {
                        base.showTip('ok', res.data, 1000);
                        modal.find(".success-widget").show();
                        modal.find(".success-widget .success_msg").html(res.data);
                        modal.find(".error-widget").hide();
                        setTimeout(function () {
                            window.location.reload();
                        }, 2000);
                        $(".close").on('click', function () {
                            window.location.reload();
                        })

                    } else {
                        modal.find(".success-widget").hide();
                        modal.find(".error-widget .error_msg").html(res.error.msg);
                        modal.find(".error-widget").show();
                    }
                });
                /*  },
                 cancel: function () {
                 return false;
                 }
                 });*/
            }
        })

    }
});