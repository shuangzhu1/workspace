define(function (require, exports) {
    var base = require('base');
    var load_more = require("app/more");
    var psinit = require('app/ps.init');

    exports.user_center = function (uid) {
        var load_discuss = false;
        var load_album = false;
        var load_show = false;

        //tab 切换
        $(".tab_list li").on('click', function () {
            var data_id = $(this).data('id');
            $(".tab_content").hide();
            $(".tab_content[data-id='" + data_id + "']").show();
            $(this).addClass('on').siblings().removeClass('on');
            //加载动态
            if (data_id == 2 && !load_discuss) {
                load_more.more('/api/user/discuss', {to: uid}, function () {
                    load_discuss = true;
                    psinit.init();
                }, "#discuss_wrap", ".item")
            }
            //加载相册
            if (data_id == 3 && !load_album) {
                load_more.more('/api/user/album', {to: uid}, function () {
                    load_album = true;
                    /*$('.lightGallery').lightGallery({
                        mode: "lg-slide",
                        speed: 300,
                        scale: 2,
                        keypress: true,
                        enableZoomAfter: 300
                    });*/
                    psinit.init();
                }, "#album_wrap", ".album_list_item", {amount: 5})
            }
            //加载秀场
            if( data_id == 4 && !load_show )
            {
                load_more.more('/api/user/show',{uid:uid},function () {
                    load_show = true;
                },'#show_wrap','.item');
            }
        });
        //初始化为动态
        $(".tab_list li[data-id='2']").click();

    }

});