/**
 * 色彩选择器 color picker
 * -- 固定色彩选择

 * ====== usage ========================================

 *   -- 1. import view tpl
 <?php $this->partial('base/widget/colorPicker'); ?>

 *   -- 2. seajs.use
 seajs.use('app/app.color', function (color) {
    // set color
    color.pick(defaultColorClass, function (colorCls, hex) {
        $('#color').val(colorCls);
    });
 });

 * @author yanue
 * @time 2014-05-21
 * @version 1.1
 */
define(function (require, exports) {
    var color = [];
    color["#003366"] = "color1";
    color["#336699"] = "color2";
    color["#3366CC"] = "color3";
    color["#003399"] = "color4";
    color["#000099"] = "color5";
    color["#0000CC"] = "color6";
    color["#000066"] = "color7";
    color["#006666"] = "color8";
    color["#006699"] = "color9";
    color["#0099CC"] = "color10";
    color["#0066CC"] = "color11";
    color["#0033CC"] = "color12";
    color["#0000FF"] = "color13";
    color["#3333FF"] = "color14";
    color["#333399"] = "color15";
    color["#669999"] = "color16";
    color["#009999"] = "color17";
    color["#33CCCC"] = "color18";
    color["#00CCFF"] = "color19";
    color["#0099FF"] = "color20";
    color["#0066FF"] = "color21";
    color["#3366FF"] = "color22";
    color["#3333CC"] = "color23";
    color["#666699"] = "color24";
    color["#339966"] = "color25";
    color["#00CC99"] = "color26";
    color["#00FFCC"] = "color27";
    color["#00FFFF"] = "color28";
    color["#33CCFF"] = "color29";
    color["#3399FF"] = "color30";
    color["#6699FF"] = "color31";
    color["#6666FF"] = "color32";
    color["#6600FF"] = "color33";
    color["#6600CC"] = "color34";
    color["#339933"] = "color35";
    color["#00CC66"] = "color36";
    color["#00FF99"] = "color37";
    color["#66FFCC"] = "color38";
    color["#66FFFF"] = "color39";
    color["#66CCFF"] = "color40";
    color["#99CCFF"] = "color41";
    color["#9999FF"] = "color42";
    color["#9966FF"] = "color43";
    color["#9933FF"] = "color44";
    color["#9900FF"] = "color45";
    color["#006600"] = "color46";
    color["#00CC00"] = "color47";
    color["#00FF00"] = "color48";
    color["#66FF99"] = "color49";
    color["#99FFCC"] = "color50";
    color["#CCFFFF"] = "color51";
    color["#CCCCFF"] = "color52";
    color["#CC99FF"] = "color53";
    color["#CC66FF"] = "color54";
    color["#CC33FF"] = "color55";
    color["#CC00FF"] = "color56";
    color["#9900CC"] = "color57";
    color["#003300"] = "color58";
    color["#009933"] = "color59";
    color["#33CC33"] = "color60";
    color["#66FF66"] = "color61";
    color["#99FF99"] = "color62";
    color["#CCFFCC"] = "color63";
    color["#FFFFFF"] = "color64";
    color["#FFCCFF"] = "color65";
    color["#FF99FF"] = "color66";
    color["#FF66FF"] = "color67";
    color["#FF00FF"] = "color68";
    color["#CC00CC"] = "color69";
    color["#660066"] = "color70";
    color["#336600"] = "color71";
    color["#009900"] = "color72";
    color["#66FF33"] = "color73";
    color["#99FF66"] = "color74";
    color["#CCFF99"] = "color75";
    color["#FFFFCC"] = "color76";
    color["#FFCCCC"] = "color77";
    color["#FF99CC"] = "color78";
    color["#FF66CC"] = "color79";
    color["#FF33CC"] = "color80";
    color["#CC0099"] = "color81";
    color["#993399"] = "color82";
    color["#333300"] = "color83";
    color["#669900"] = "color84";
    color["#99FF33"] = "color85";
    color["#CCFF66"] = "color86";
    color["#FFFF99"] = "color87";
    color["#FFCC99"] = "color88";
    color["#FF9999"] = "color89";
    color["#FF6699"] = "color90";
    color["#FF3399"] = "color91";
    color["#CC3399"] = "color92";
    color["#990099"] = "color93";
    color["#666633"] = "color94";
    color["#99CC00"] = "color95";
    color["#CCFF33"] = "color96";
    color["#FFFF66"] = "color97";
    color["#FFCC66"] = "color98";
    color["#FF9966"] = "color99";
    color["#FF6666"] = "color100";
    color["#FF0066"] = "color101";
    color["#CC6699"] = "color102";
    color["#993366"] = "color103";
    color["#999966"] = "color104";
    color["#CCCC00"] = "color105";
    color["#FFFF00"] = "color106";
    color["#FFCC00"] = "color107";
    color["#FF9933"] = "color108";
    color["#FF6600"] = "color109";
    color["#FF5050"] = "color110";
    color["#CC0066"] = "color111";
    color["#660033"] = "color112";
    color["#996633"] = "color113";
    color["#CC9900"] = "color114";
    color["#FF9900"] = "color115";
    color["#CC6600"] = "color116";
    color["#FF3300"] = "color117";
    color["#FF0000"] = "color118";
    color["#CC0000"] = "color119";
    color["#990033"] = "color120";
    color["#663300"] = "color121";
    color["#996600"] = "color122";
    color["#CC3300"] = "color123";
    color["#993300"] = "color124";
    color["#990000"] = "color125";
    color["#800000"] = "color126";
    color["#993333"] = "color127";

    var colorHex = "#FFFFFF"
    var colorClass = "color64";
    var callback = null;

    //
    exports.pick = function (defaultColorClass, func) {
        callback = func;
        $('.colorPreview').removeClass().addClass('colorPreview ' + (defaultColorClass ? defaultColorClass : colorClass));
        $(document).on('click', ".colorPreview", function () {
            $('.colorPicker').fadeIn();
        });
    };

    window.mouseOverColor = function (hex) {
        $('.colorPreview').removeClass().addClass('colorPreview ' + color[hex]);
    };

    window.clickColor = function (hex, seltop, selleft) {
        // 设定colorHex
        colorHex = hex;
        $('.colorPicker').hide();
        if (seltop > -1 && selleft > -1) {
            $('.colorPicker .selectedColor').css({"top": seltop, "left": selleft, "visibility": "visible"});
        } else {
            $('.colorPreview').removeClass().addClass('colorPreview ' + color[colorHex]);
            $('.colorPicker .selectedColor').css({"visibility": "hidden"});
        }

        if (typeof callback == 'function') {
            callback(color[colorHex], hex);
        }
    };

    window.mouseOutMap = function () {
        $('.colorPreview').removeClass().addClass('colorPreview ' + color[colorHex]);
    };

    function outColorTool() {
        var n = 1;
        var str = '{';
        $('.colorMap area[shape="poly"]').each(function () {
            var color = $(this).attr('alt');
            color = color.substr(1);
//            str += 'color["#' + color + '"]=' + '"color' + n + "\";\r\n";
            str += '.btn-color' + n + ' { border: 1px solid #' + color + ';background: #' + color + ';' + '}' + "\r\n";
            n++;
        });
        str += '}';
//        console.log(str);
    }

//    outColorTool();
//    outColorCss();
});

