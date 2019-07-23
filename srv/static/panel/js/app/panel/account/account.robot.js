define(function (require, exports) {
    var base = require('app/panel/panel.base');
    $('#avator').change(function () {
        var idFile = $(this).attr("id");
        var file = document.getElementById(idFile);
        var reader = new FileReader();
        reader.onload =function (e) {
            $('#preview').attr('src',e.target.result);
            $('#preview').closest('a').attr('href',e.target.result);
            $('#preview').removeClass('hide');
        }
        reader.readAsDataURL(file.files[0]);
    });
    exports.saveRobot = function () {
        // add click
        $('.addBtn').click(function (e) {
            base.showPop('#optionPopup');

            e.stopImmediatePropagation();
        });

        // confirm
        $('#optionWidget').on('click', '.res-btn', function () {
            var data = {};
            data.name = $('#optionPopup #name').val();
            data.signature = $('#optionPopup #signature').val();
            data.avator = $('#optionPopup #preview').attr('src');
            data.sex = $("#optionPopup input[name='sex']:checked").val();
            if( data.name == '')
            {
                tip.showTip('err','请填写昵称',2000);return;
            }
            /*if( data.avator == '')
            {
                tip.showTip('err','请选择头像',2000);return;
            }*/
            base.requestApi('/panel/robot/register', {data: data}, function (res) {
                if (res.result == 1) {
                    window.location.reload();
                    base.hidePop('#optionPopup');
                }
            });

        });


    };
});