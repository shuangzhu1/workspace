define(function (require,exports) {
    var base = require('app/panel/panel.base');
    var storage = require('app/panel/panel.storage');
    storage.getImg('#thumbPreview', function (res) {
        $('input[name="thumb"]').val(res.url);
        $('#thumbPreview').attr('src', res.url);
    });

    $('#save').on('click',function(){
        var title = $('#title').val();
        var thumb = $('#thumb').val();
        var flag = true;
        if( $.trim(title) === ''  )
        {
            tip.showTip('err','请填写标题',2000);
            flag = false;
            return ;
        }

        /*if( $.trim(thumb) === '/static/panel/images/default-pic.png' )
        {
            tip.showTip('err','请选择缩略图',2000);
            flag = false;
            return;
        }*/
        if( flag )
        {
            var formData = $('#article').serialize();
            base.requestApi('/api/package/noticeAdd',formData,function (res) {
                if( res.result === 1 )
                    tip.showTip('ok','操作成功',1000);
            });
        }

    })

});