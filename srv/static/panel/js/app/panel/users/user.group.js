"use strict";
window.alert = BSDialog.alert;
window.confirm = BSDialog.confirm;
$(document).ready(function() {
    $('#saveGrade').bind('click', function () {
        var gid = $('#gid').val();
        var name = $('#gradeName').val();
        var desc = $('#gradeDesc').val();
        var ruleElements = $(':checkbox[name=ruleIds]');
        var rules = '';
        $(ruleElements).each(function(index, node) {
            var ruleId = $(node).val();
            var checked = node.checked;
            if(checked) {
                rules += ',' + ruleId;
            }
        });
        if(rules.length > 0) {
            rules = rules.substring(1);
        }
        if(!name || name.length == 0) {
            alert('组名称不能为空', function() {
                $('#gradeName').focus();
            });
            return false;
        }
        if(!desc || desc.length == 0) {
            alert('组描述不能为空', function() {
                $('#gradeDesc').focus();
            });
            return false;
        }

        var data = {};
        if(gid && gid > 0) {
            data.gid = gid;
        }
        data.name = name;
        data.desc = desc;
        data.rules = rules;

        $.ajax({
            url: '/panel/users/groupAdd',
            dataType: 'json',
            type: 'post',
            data: data
        }).done(function(data) {
            if(data.code > 0) {
                alert("组添加失败！" + data.message);
            }
            else {
                window.location.href = window.location.href;
            }
        });
    });
    $('i[data-toggle="tooltip"]').tooltip();

    $('.item-edit').bind('click', function() {
        $('body').data('currentGrade', JSON.parse($(this).attr('data-data')));
    });
    $('#gradeInfoModal').bind('show.bs.modal', function() {
        initModalFormData();
    }).bind("hide.bs.modal", function() {
        $('body').data('currentGrade', false);
    });

    $('.item-remove').bind('click', function(e) {
        if(e.preventDefault) {
            e.preventDefault();
        }
        else {
            e.returnValue = false;
        }
        var gid = $(this).attr('data-id');
        if(!gid) {
            alert("请指定要删除的分组");
            return false;
        }
        BSDialog.confirm("确定要删除吗？", [{
            label: "放弃",
            cssClass: "btn btn-warning btn-sm"
        }, {
            label: "确定",
            cssClass: "btn btn-danger btn-sm",
            onclick: function() {
                $.ajax({
                    url: '/panel/users/groupRemove',
                    dataType: 'json',
                    type: 'post',
                    data: {
                        gid: gid
                    }
                }).done(function(data) {
                    if(data.code && data.code == 1) {
                        alert(data.message);
                    }
                    else {
                        window.location.href = window.location.href;
                    }
                });
            }
        }]);
    });
});


function initModalFormData() {
    var data = $('body').data('currentGrade');
    var ruleElements = $(':checkbox[name=ruleIds]');
    if(!data) {
        $('#gid').val('');
        $('#gradeName').val('');
        $('#gradeDesc').val('');
        $(ruleElements).each(function(index, node) {
            $(node).attr('checked', false);
        });
    }
    else {
        $('#gid').val(data.id);
        $('#gradeName').val(data.name);
        $('#gradeDesc').val(data.desc);
        var rules = JSON.parse(data.rules);
        $(ruleElements).each(function(index, node) {
            var ruleId = $(node).val();
            console.log($.inArray(ruleId, rules));
            if(ruleId && $.inArray(ruleId, rules) >= 0) {
                $(node).attr('checked', true);
            }
            else {
                $(node).attr('checked', false);
            }
        });
    }
}
