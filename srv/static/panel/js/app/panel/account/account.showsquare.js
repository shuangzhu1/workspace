define(function (require, exports) {
    var base = require('app/panel/panel.base');

    exports.addTask = function () {
        // add click
        $('.addBtn').click(function (e) {
            base.showPop('#optionPopup');

            e.stopImmediatePropagation();
        });
        // confirm
        $('#optionWidget').on('click', '.res-btn', function () {
            var data = {};
            data.uids = $('#uids').val();
            data.start = $('#start').val();
            data.end = $('#end').val();
            data.score = $("#score").val();
            //console.log(Date.parse(new Date(data.start)),Date.parse(new Date(data.end)));return;
            if(data.start == '' || data.end == '')
            {
                tip.showTip('err','请选择任务时间',3000);
                return;
            }
            if( Date.parse(new Date(data.start)) >= Date.parse(new Date(data.end)))
            {
                tip.showTip('err','结束时间不能小于开始时间',3000);
                return;

            }
            if( data.uids == '')
            {
                tip.showTip('err','目标用户不能为空',3000);
                return;
            }
            if( data.score == '')
            {
                tip.showTip('err','期望得分不能为空',3000);
                return;
            }
            base.requestApi('/panel/showsquare/addTask', {data: data}, function (res) {
                if (res.result == 1) {
                    base.hidePop('#optionPopup');
                    tip.showTip('ok','添加成功',1500);
                    setTimeout(function(){window.location.reload();},1500);
                }
            });

        });


    };
});