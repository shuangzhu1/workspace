define(function (require, exports) {
    var search = require('app/app.search');
    var base = require('app/panel/panel.base');

    exports.sensitive = function (type) {
        var height = $(window).height();
        $(".float_bar").css({
            'top': (height + 65 - $(".float_bar").height()) / 2 + 'px'
        });

        $(".words li").each(function () {
            $(this).attr('data-width', $(this).outerWidth());
            $(this).css({'width': $(this).outerWidth()});
            $(this).find("i").show();
        });

        $(".words li").hover(function () {
            $(this).animate({
                'width': ($(this).data('width') + 20) + 'px'
            }, 300, '', function () {
            })
        }, function () {
            $(this).animate({'width': ($(this).data('width')) + 'px'}, 300, '', function () {
            })
        });
        $(".btnAdd").on('click', function () {
            $("#wordModal").modal("show");
        });

        $("#sureBtn").on('click', function () {
            var word = $.trim($("#word").val());
            if (word == '') {
                return;
            }
            base.requestApi('/api/site/addWord', {words: word, type: type}, function (res) {
                if (res.result == 1) {
                    tip.showTip('ok', '添加成功', 1000, function () {
                        window.location.reload();
                    });
                }
            })
        });

        $(".float_bar li").on('click', function () {
            $(this).addClass('active').siblings().removeClass('active');
            var key = $(this).data('key');
            $(".header a").removeClass('current');
            var location = $(".header[data-key='" + key + "']");
            location.find('a').addClass('current');
            $("html,body").animate({scrollTop: location.offset().top - 200}, 500);
        });


        $(".search").on('blur', function () {
            $(".search_result").slideUp();
        });
        $(".search").on('focus', function () {
            $(".search_result").slideDown();
        });
        $(".search_result").on("click", 'li', function () {
            $(".float_bar li[data-key='" + $(this).data('first-abbr') + "']").click();
            $(".search").val($(this).data('word'));
            $(".words li").removeClass("current");
            $(".words li[data-key='" + $(this).data('key') + "']").addClass("current");
        });

        //删除
        $(".delBtn").on('click', function () {
            var parent = $(this).parent();
            var item = parent.data('item');
            if (item != '') {
                base.requestApi('/api/site/removeWord', {word: item, type: type}, function (res) {
                    if (res.result == 1) {
                        parent.remove();
                    }
                }, false, true);
            }
        });

        search.Search(".search", function (value) {
            if (value == '') {
                $(".search_result").slideUp();
                return;
            }
            base.requestApi('/api/site/searchWord', {word: value, type: type}, function (res) {
                if (res.result == 1) {
                    var html = "";
                    if (res.data.count > 0) {
                        $.each(res.data.list, function () {
                            html += "<li data-key='" + this.key + "' data-first-abbr='" + this.first_abbr + "' data-word='" + this.word + "'>" + this.content + "</li>";
                        })
                    } else {
                    }
                    $(".search_result").html(html).slideDown();
                }
            }, false, true);
        })
    }


});