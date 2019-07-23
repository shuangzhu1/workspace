define(function (require, exports) {
    var base = require('base');
    require("jquery/jquery.more.js");
    var load_more = require("app/more.js?v=1.0.1");
    exports.like_collect = function () {
        //视频播放
        $("#live_bg").click(function () {
            document.getElementById('my-video').play();
            $(this).hide();
        });

        //是否允许收藏、取消收藏
        var enable_collect = true;
        $("#collect").on('click', function () {
            if (!enable_collect) {
                return false;
            }
            enable_collect = false;
            var _thisCol = $(this).find('em.collect');
            var _this = $(this);
            var _thisLabel = $(this).find('label');
            checkLogin(function () {
                if (_thisCol.hasClass('enshrine')) {
                    requestApi('/api/social/unCollect', {
                        type: _this.data('type'),
                        item_id: _this.data('item-id')
                    }, function (res) {
                        if (res.result == 1) {
                            _thisCol.removeClass('enshrine');
                            Popup('取消成功');
                            _thisLabel.html(parseInt(parseInt(_thisLabel.html()) - 1));
                            enable_collect = true;
                        }
                    }, false, true);
                } else {
                    requestApi('/api/social/collect', {
                        type: _this.data('type'),
                        item_id: _this.data('item-id')
                    }, function (res) {
                        if (res.result == 1) {
                            _thisCol.addClass('enshrine');
                            Popup('收藏成功');
                            _thisLabel.html(parseInt(parseInt(_thisLabel.html()) + 1));
                            enable_collect = true;

                        }
                    }, false, true);

                }
            });
        });
        //是否允许点赞、取消赞
        var enable_like = true;

        $(".comment_like").on('click', function (e) {
            if (!enable_like) {
                return false;
            }
            enable_like = false;
            var _this = $(this);
            var _thisEm = $(this).find('em');
            var _thisLabel = $(this).find('label');
            checkLogin(function () {
                if (_thisEm.hasClass('vLike')) {
                    requestApi('/api/social/dislike', {
                        type: _this.data('type'),
                        item_id: _this.data('item-id')
                    }, function (res) {
                        if (res.result == 1) {
                            _thisEm.removeClass('vLike');
                            Popup('取消成功');
                            if (res.data == '1') {
                                _thisLabel.html(parseInt(parseInt(_thisLabel.html()) - 1));
                                enable_like = true;
                            }

                        }
                    }, false, true);
                } else {
                    requestApi('/api/social/like', {
                        type: _this.data('type'),
                        item_id: _this.data('item-id')
                    }, function (res) {
                        if (res.result == 1) {
                            _thisEm.addClass('vLike');
                            Popup('点赞成功');
                            if (res.data == '1') {
                                _thisLabel.html(parseInt(parseInt(_thisLabel.html()) + 1));
                                enable_like = true;
                            }
                        }
                    }, false, true);

                }
            });
            e.stopPropagation();
        })
    };
    exports.more = function (discuss_id, url) {
        exports.like_collect();
        load_more.more(url, {'item_id': discuss_id}, function () {
            $(".comment_list_about > p").each(function (i, v) {
                var len = $(this).text().length;
                if ($(v).text().length > 60) {
                    var alltext = $(this).text();
                    var substr = $(this).text().substr(0, 38);
                    $(this).html(substr + '...');
                    $(v).next('a').show();
                } else {
                    $(v).next('a').hide();
                }
            });
            $(".watch").click(function () {
                var alltext = $(this).attr('data-con');
                $(this).prev().html(alltext);
                $(this).hide();
                $(this).removeAttr('data-con');
                $(this).remove();
            });
            exports.like_collect();
        })
    };

});