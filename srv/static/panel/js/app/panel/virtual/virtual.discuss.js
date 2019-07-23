define(function (require, exports) {
    var base = require('app/panel/panel.base');
    /*   var store = require('app/panel/panel.storage');*/

    require('jquery/jquery.dragsort');

    /* require('jquery/jquery.stickyNavbar.min');
     require('jquery/jquery.easing.min');*/
    require('jquery/jquery-ajaxfileupload');
    var uploader = require('app/panel/virtual/upload.js?v=1.0');
    //视频提交数据
    function submit(url) {
        var media = [];//媒体数据
        var media_type = $(".media_type:checked").val();//媒体类型
        var content = $("#content").val().trim();//文本内容
        var open_location = $("#open_position").attr('checked') == 'checked' ? 1 : 0;//是否开启位置
        var lng = 0;//经度
        var lat = 0;//纬度
        var address = '';//位置
        var tag = '';//标签
        var app_uid = $(".user_item.checked").length == 1 ? $(".user_item.checked").attr('data-id') : 0;//发布用户
        $(".tag").each(function () {
            if ($(this).prop('checked')) {
                tag += ',' + $(this).val();
            }
        });
        tag = tag ? tag.substr(1) : '';
        //   var videoThumb = '';//视频截图
        var video = '';//视频

        if (open_location) {
            lng = $("#lng").val();//经度
            lat = $("#lat").val();//纬度
            address = $("#txtAddress").val();//位置
        }
        // videoThumb = ($("#videoThumb").attr('src') + '?' + $("#videoThumb").data('width') + 'x' + $("#videoThumb").data('height'));
        var data = {
            media: media,
            media_type: media_type,
            content: content,
            videoThumb: url.thumb,
            video: url.video,
            open_location: open_location,
            lng: lng,
            lat: lat,
            tags: tag,
            app_uid: app_uid
        };
        $("#msgModal").modal('hide');

        base.requestApi('/api/discuss/add', data, function (res) {
            if (res.result == 1) {
                tip.showTip('ok', '发布成功<a href="/discuss/detail/' + res.data + '">点击跳转至详情</a>', 3000);
            }
        });
    }

    exports.emoj = function () {
        $(function () {
            $.fn.extend({
                insertContent: function (myValue, t) {
                    var $t = $(this)[0];
                    if (document.selection) { // ie
                        this.focus();
                        var sel = document.selection.createRange();
                        sel.text = myValue;
                        this.focus();
                        sel.moveStart('character', -l);
                        var wee = sel.text.length;
                        if (arguments.length == 2) {
                            var l = $t.value.length;
                            sel.moveEnd("character", wee + t);
                            t <= 0 ? sel.moveStart("character", wee - 2 * t - myValue.length) : sel.moveStart("character", wee - t - myValue.length);
                            sel.select();
                        }
                    } else if ($t.selectionStart
                        || $t.selectionStart == '0') {
                        var startPos = $t.selectionStart;
                        var endPos = $t.selectionEnd;
                        var scrollTop = $t.scrollTop;
                        $t.value = $t.value.substring(0, startPos)
                            + myValue
                            + $t.value.substring(endPos, $t.value.length);
                        this.focus();
                        $t.selectionStart = startPos + myValue.length;
                        $t.selectionEnd = startPos + myValue.length;
                        $t.scrollTop = scrollTop;
                        if (arguments.length == 2) {
                            $t.setSelectionRange(startPos - t,
                                $t.selectionEnd + t);
                            this.focus();
                        }
                    } else {
                        this.value += myValue;
                        this.focus();
                    }
                }
            });
        });
        //表情
        var baseurl = '/static/panel/images/original_emotion/';
        var Face = {
            emoticon: {
                "[可爱]": baseurl + "emoji_01@2x.png",
                "[大笑]": baseurl + "emoji_00@2x.png",
                "[色]": baseurl + "emoji_02@2x.png",
                "[嘘]": baseurl + "emoji_03@2x.png",
                "[亲]": baseurl + "emoji_04@2x.png",
                "[呆]": baseurl + "emoji_05@2x.png",
                "[口水]": baseurl + "emoji_06@2x.png",
                "[汗]": baseurl + "emoji_145@2x.png",
                "[呲牙]": baseurl + "emoji_07@2x.png",
                "[鬼脸]": baseurl + "emoji_08@2x.png",
                "[害羞]": baseurl + "emoji_09@2x.png",
                "[偷笑]": baseurl + "emoji_10@2x.png",
                "[调皮]": baseurl + "emoji_11@2x.png",
                "[可怜]": baseurl + "emoji_12@2x.png",
                "[敲]": baseurl + "emoji_13@2x.png",
                "[惊讶]": baseurl + "emoji_14@2x.png",
                "[流感]": baseurl + "emoji_15@2x.png",
                "[委屈]": baseurl + "emoji_16@2x.png",
                "[流泪]": baseurl + "emoji_17@2x.png",
                "[嚎哭]": baseurl + "emoji_18@2x.png",
                "[惊恐]": baseurl + "emoji_19@2x.png",
                "[怒]": baseurl + "emoji_20@2x.png",
                "[酷]": baseurl + "emoji_21@2x.png",
                "[不说]": baseurl + "emoji_22@2x.png",
                "[鄙视]": baseurl + "emoji_23@2x.png",
                "[阿弥陀佛]": baseurl + "emoji_24@2x.png",
                "[奸笑]": baseurl + "emoji_25@2x.png",
                "[睡着]": baseurl + "emoji_26@2x.png",
                "[口罩]": baseurl + "emoji_27@2x.png",
                "[努力]": baseurl + "emoji_28@2x.png",
                "[抠鼻孔]": baseurl + "emoji_29@2x.png",
                "[疑问]": baseurl + "emoji_30@2x.png",
                "[怒骂]": baseurl + "emoji_31@2x.png",
                "[晕]": baseurl + "emoji_32@2x.png",
                "[呕吐]": baseurl + "emoji_33@2x.png",
                "[拜一拜]": baseurl + "emoji_160@2x.png",
                "[惊喜]": baseurl + "emoji_161@2x.png",
                "[流汗]": baseurl + "emoji_162@2x.png",
                "[卖萌]": baseurl + "emoji_163@2x.png",
                "[默契眨眼]": baseurl + "emoji_164@2x.png",
                "[烧香拜佛]": baseurl + "emoji_165@2x.png",
                "[晚安]": baseurl + "emoji_166@2x.png",
                "[强]": baseurl + "emoji_34@2x.png",
                "[弱]": baseurl + "emoji_35@2x.png",
                "[OK]": baseurl + "emoji_36@2x.png",
                "[拳头]": baseurl + "emoji_37@2x.png",
                "[胜利]": baseurl + "emoji_38@2x.png",
                "[鼓掌]": baseurl + "emoji_39@2x.png",
                "[握手]": baseurl + "emoji_200@2x.png",
                "[发怒]": baseurl + "emoji_40@2x.png",
                "[骷髅]": baseurl + "emoji_41@2x.png",
                "[便便]": baseurl + "emoji_42@2x.png",
                "[火]": baseurl + "emoji_43@2x.png",
                "[溜]": baseurl + "emoji_44@2x.png",
                "[爱心]": baseurl + "emoji_45@2x.png",
                "[心碎]": baseurl + "emoji_46@2x.png",
                "[钟情]": baseurl + "emoji_47@2x.png",
                "[唇]": baseurl + "emoji_48@2x.png",
                "[戒指]": baseurl + "emoji_49@2x.png",
                "[钻石]": baseurl + "emoji_50@2x.png",
                "[太阳]": baseurl + "emoji_51@2x.png",
                "[有时晴]": baseurl + "emoji_52@2x.png",
                "[多云]": baseurl + "emoji_53@2x.png",
                "[雷]": baseurl + "emoji_54@2x.png",
                "[雨]": baseurl + "emoji_55@2x.png",
                "[雪花]": baseurl + "emoji_56@2x.png",
                "[爱人]": baseurl + "emoji_57@2x.png",
                "[帽子]": baseurl + "emoji_58@2x.png",
                "[皇冠]": baseurl + "emoji_59@2x.png",
                "[篮球]": baseurl + "emoji_60@2x.png",
                "[足球]": baseurl + "emoji_61@2x.png",
                "[垒球]": baseurl + "emoji_62@2x.png",
                "[网球]": baseurl + "emoji_63@2x.png",
                "[台球]": baseurl + "emoji_64@2x.png",
                "[咖啡]": baseurl + "emoji_65@2x.png",
                "[啤酒]": baseurl + "emoji_66@2x.png",
                "[干杯]": baseurl + "emoji_67@2x.png",
                "[柠檬汁]": baseurl + "emoji_68@2x.png",
                "[餐具]": baseurl + "emoji_69@2x.png",
                "[汉堡]": baseurl + "emoji_70@2x.png",
                "[鸡腿]": baseurl + "emoji_71@2x.png",
                "[面条]": baseurl + "emoji_72@2x.png",
                "[冰淇淋]": baseurl + "emoji_73@2x.png",
                "[沙冰]": baseurl + "emoji_74@2x.png",
                "[生日蛋糕]": baseurl + "emoji_75@2x.png",
                "[蛋糕]": baseurl + "emoji_76@2x.png",
                "[糖果]": baseurl + "emoji_77@2x.png",
                "[葡萄]": baseurl + "emoji_78@2x.png",
                "[西瓜]": baseurl + "emoji_79@2x.png",
                "[光碟]": baseurl + "emoji_80@2x.png",
                "[手机]": baseurl + "emoji_81@2x.png",
                "[电话]": baseurl + "emoji_82@2x.png",
                "[电视]": baseurl + "emoji_83@2x.png",
                "[声音开启]": baseurl + "emoji_84@2x.png",
                "[声音关闭]": baseurl + "emoji_85@2x.png",
                "[铃铛]": baseurl + "emoji_86@2x.png",
                "[锁头]": baseurl + "emoji_87@2x.png",
                "[放大镜]": baseurl + "emoji_88@2x.png",
                "[灯泡]": baseurl + "emoji_89@2x.png",
                "[锤头]": baseurl + "emoji_90@2x.png",
                "[烟]": baseurl + "emoji_91@2x.png",
                "[炸弹]": baseurl + "emoji_92@2x.png",
                "[枪]": baseurl + "emoji_93@2x.png",
                "[刀]": baseurl + "emoji_94@2x.png",
                "[药]": baseurl + "emoji_95@2x.png",
                "[打针]": baseurl + "emoji_96@2x.png",
                "[钱袋]": baseurl + "emoji_97@2x.png",
                "[钞票]": baseurl + "emoji_98@2x.png",
                "[银行卡]": baseurl + "emoji_99@2x.png",
                "[手柄]": baseurl + "emoji_100@2x.png",
                "[麻将]": baseurl + "emoji_101@2x.png",
                "[调色板]": baseurl + "emoji_102@2x.png",
                "[电影]": baseurl + "emoji_103@2x.png",
                "[麦克风]": baseurl + "emoji_104@2x.png",
                "[耳机]": baseurl + "emoji_105@2x.png",
                "[音乐]": baseurl + "emoji_106@2x.png",
                "[吉他]": baseurl + "emoji_107@2x.png",
                "[火箭]": baseurl + "emoji_108@2x.png",
                "[飞机]": baseurl + "emoji_109@2x.png",
                "[火车]": baseurl + "emoji_110@2x.png",
                "[公交]": baseurl + "emoji_111@2x.png",
                "[轿车]": baseurl + "emoji_112@2x.png",
                "[出租车]": baseurl + "emoji_113@2x.png",
                "[警车]": baseurl + "emoji_114@2x.png",
                "[自行车]": baseurl + "emoji_115@2x.png",
            }
        };
        //选择表情
        $(document).on('click', '.send-select .icon-xiaolian', function () {
            if ($(this).hasClass('showimg')) {
                $(this).removeClass('showimg');
                $('.face-box').hide();
            } else {
                $(this).addClass('showimg');
                $('.face-box').show();
                for (var s in Face.emoticon) {
                    $(".face-img").append('<span class="items" data-faceID="' + s + '"><img src="' + Face.emoticon[s] + '"/></span>');
                }
            }
        });
        $(document).on('click', '.send-left .face-img>span', function () {
            var _faceID = $(this).attr('data-faceID');
            var obj = $('.topic-send').children('textarea');
            var _val = obj.val();
            obj.insertContent(_faceID);
            // obj.val(_val + _faceID);
            obj.focus();
            $('.send-select .icon-xiaolian').removeClass('showimg');
            $('.face-box').hide();
        });
    };
    exports.addDiscuss = function () {
        var load_upload_2 = false;//加载视频控件
        var load_upload_3 = false;//加载图片控件

        var load_baidu_map = false;//加载百度地图
        //
        $(function () {
            var video_upload = '';
            var img_upload = '';
            uploader.uploadVideoImg('.upload-widget[data-unique="1"]', {'type': 'img'}, function (res) {
            });
            uploader.uploadVideoImg('.upload-widget[data-unique="2"]', {
                'type': 'video'
            }, function (res) {
                video_upload = res;
            }, submit);

            /*  uploader.uploadVideoImg('.upload-widget[data-unique="1"]', {'type': 'img'}, function (res) {
             });
             uploader.uploadVideoImg('.upload-widget[data-unique="2"]', {
             'type': 'video',
             }, function (res) {
             video_upload = res;
             }, submit);*/

            $('#picturesPreview').dragsort({
                dragSelector: "img",
                dragBetween: true,
                dragEnd: function () {
                }
            });
            // 移除图片
            $("#picturesPreview").on('click', '.removeBtn', function (e) {
                $(this).parent().parent().remove();
                $("#browse_files_button_undefined").show();
                e.stopImmediatePropagation();
            });
            //类型开关
            $(".media_type").on('click', function () {
                //纯文本
                if ($(this).val() == 1) {
                    $(".picComponent").hide();
                    $(".videoComponent").hide();
                }
                //视频
                if ($(this).val() == 2) {
                    $(".picComponent").hide();
                    $(".videoComponent").show();
                    /*  if (!load_upload_2) {
                     uploader.uploadVideoImg('.upload-widget[data-unique="1"]', {'type': 'img'}, function (res) {
                     });
                     uploader.uploadVideoImg('.upload-widget[data-unique="2"]', {
                     'type': 'video'
                     }, function (res) {
                     video_upload = res;
                     }, submit);
                     load_upload_2 = true;
                     }*/
                }
                //图片
                if ($(this).val() == 3) {
                    if (!load_upload_3) {
                        uploader.upload('.pub-all-pic', {'type': 'img', multi_selection: true}, function (res) {
                        });
                        load_upload_3 = true;
                    }
                    $(".videoComponent").hide();
                    $(".picComponent").show();
                }
            });
            //
            $(".user_item").on('click', function () {
                if ($(this).hasClass('checked')) {
                    $(this).removeClass("checked");
                } else {
                    $(this).addClass("checked");
                    $(this).siblings().removeClass("checked");
                }
            });

            $("#open_position").on('change', function () {
                if ($(this).prop('checked')) {
                    $(".address_component").show();
                    if (!load_baidu_map) {
                        exports.toLatLng();
                        load_baidu_map = true;
                    }
                } else {
                    $(".address_component").hide();
                }
            });
            //保存
            $(".saveBtn").on('click', function () {
                var media = [];//媒体数据
                var media_type = $(".media_type:checked").val();//媒体类型
                var content = $("#content").val().trim();//文本内容
                var open_location = $("#open_position").attr('checked') == 'checked' ? 1 : 0;//是否开启位置
                var lng = 0;//经度
                var lat = 0;//纬度
                var address = '';//位置
                var tag = '';//标签
                var app_uid = $(".user_item.checked").attr('data-id');//发布用户
                $(".tag").each(function () {
                    if ($(this).prop('checked')) {
                        tag += ',' + $(this).val();
                    }
                });
                tag = tag ? tag.substr(1) : '';
                //   var videoThumb = '';//视频截图
                var video = '';//视频

                if (tag == '') {
                    tip.showTip('err', '请选择标签', 1000);
                    return false
                }
                if (open_location) {
                    lng = $("#lng").val();//经度
                    lat = $("#lat").val();//纬度
                    address = $("#txtAddress").val();//位置
                    if (lng == '' || lat == '') {
                        tip.showTip('err', '请选择发送时所在的位置', 1000);
                        return false
                    }
                    if (address == '') {
                        tip.showTip('err', '请填写详细地址', 1000);
                        return false
                    }

                }
                /*  if (!app_uid || app_uid == null) {
                 tip.showTip('err', '请选择发布人', 1000);
                 return false
                 }*/
                if (media_type == '1' || media_type == '3') {
                    //纯文本
                    if (media_type == '1') {
                        if (content == '') {
                            tip.showTip('err', '请输入文本内容', 1000);
                            return false
                        }
                    }
                    //图片
                    else if (media_type == '3') {
                        $(".img_list img").each(function () {
                            media.push($(this).attr('src') + '?' + $(this).data('width') + 'x' + $(this).data('height'));
                        });
                        if (media.length == 0) {
                            tip.showTip('err', '请选择图片', 1000);
                            return false
                        }
                    }
                    var data = {
                        media: media,
                        media_type: media_type,
                        content: content,
                        videoThumb: '',
                        video: video,
                        open_location: open_location,
                        lng: lng,
                        lat: lat,
                        tags: tag,
                        app_uid: app_uid
                    };
                    base.requestApi('/api/discuss/add', data, function (res) {
                        if (res.result == 1) {
                            tip.showTip('ok', '发布成功<a href="/discuss/detail/' + res.data + '">点击跳转至详情</a>', 3000);
                        }
                    });

                }
                //视频
                else if (media_type == '2') {

                    /*  if ($("#thumbPreview").attr('src') == '') {
                     tip.showTip('err', '请选择视频截图', 1000);
                     return false
                     }*/
                    if ($("#videoUrl").html() == '') {
                        tip.showTip('err', '请选择视频', 1000);
                        return false
                    }
                    video_upload.start();
                }
            });


        })
    };


    exports.toLatLng = function () {
        // 百度地图API功能
        var map = new BMap.Map("mapWrap");
        map.enableScrollWheelZoom();
        window.map = map;
        point = new BMap.Point(113.961974, 22.547832);
        map.centerAndZoom(point, 15);

        var marker = new BMap.Marker(point);
        marker.enableDragging();

        marker.addEventListener("dragend", function showInfo(e) {
            var cp = this.getPosition();
            $('#txtLat').val(cp.lat);
            $('#txtLng').val(cp.lng);
            map.centerAndZoom(cp, 16);
            //详细地址信息
            var geoc = new BMap.Geocoder();
            geoc.getLocation(e.point, function (rs) {
                //console.log(rs);
                // var addComp = rs.addressComponents;
                // console.log(addComp);
                //  alert(addComp.province + ", " + addComp.city + ", " + addComp.district + ", " + addComp.street + ", " + addComp.streetNumber);
            });
            //console.log(e);
        });

        map.addOverlay(marker);

        var myGeo = new BMap.Geocoder();

        var lat = parseFloat($('#txtLat').val());
        var lng = parseFloat($('#txtLng').val());
        if (!isNaN(lat) && !isNaN(lng) && lat > 0 && lng > 0) {
            point = new BMap.Point(lng, lat);
            map.centerAndZoom(point, 15);
            marker.setPosition(point);
        } else {
            geoPoint();
        }

        $('.form').on('change', '#txtAddress', function () {
            geoPoint();
        });

        // 三级联动
        $(".form").on("change", ".province_id", function (e) {
            var id = $(this).val();
            base.requestApi('/api/area/getCities', {"province_id": id}, function (res) {
                if (res.result == 1) {
                    $("#county_id").find("option[value='']").prop("selected", "selected");
                    var str = '<option value="">==请选择市区==</option>';
                    for (var i in res.data) {
                        var area = res.data[i];
                        str += '<option value="' + area.id + '">' + area.name + '</option>';
                    }
                    $("#city_id").html(str);
                }
            });
            $('#province_name').val($(this).find('option:selected').text());
            e.stopImmediatePropagation();
        }).on("change", ".city_id", function (e) {
            var id = $(this).val();
            base.requestApi('/api/area/getCounties', {"city_id": id}, function (res) {
                if (res.result == 1) {
                    var str = '<option value="">==请选择区县乡==</option>';
                    for (var i in res.data) {
                        var area = res.data[i];
                        str += '<option value="' + area.id + '">' + area.name + '</option>';
                    }
                    $("#county_id").html(str);
                }
            });
            $('#city_name').val($(this).find('option:selected').text());
            e.stopImmediatePropagation();
        }).on('change', '.county_id', function () {
            $('#county_name').val($(this).find('option:selected').text());
            geoPoint();
        });

        function geoPoint() {
            var addr = $('#province_name').val() + $('#city_name').val() + $('#county_name').val() + $("#txtAddress").val();
            if (addr) {
                // 将地址解析结果显示在地图上,并调整地图视野
                myGeo.getPoint(addr, function (point) {
                    if (point) {
                        $('#txtLat').val(point.lat);
                        $('#txtLng').val(point.lng);
                        map.centerAndZoom(point, 16);
                        marker.setPosition(point);
                    }
                });
            }
        }
    };


});