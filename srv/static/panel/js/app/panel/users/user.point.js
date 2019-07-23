define(function (require, exports) {
    var base = require('app/panel/panel.base');
    base.selectNone();
    base.selectCheckbox();
    exports.del = function () {
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
            base.requestApi('/api/user/delPointLog', {data: data}, function (res) {
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