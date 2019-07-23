/**
 * 图标选择器 icon picker
 * -- 固定图标选择

 * ====== usage ========================================

  *   --  seajs.use
 seajs.use('app/app.icon', function (icon) {

    // '.icon-preview' -- the btn for preview and click to pick
    // '.icon-wrap'  -- the wrapper to show all defined icon
    icon.setIcon('.icon-preview', '.icon-wrap', function (icon) {
        $('#icon').val(icon);
    });
 });

 * @author yanue
 * @time 2014-05-21
 * @version 1.1
 */
define(function (require, exports) {

    var icons = [
        'icon-phone',
        'icon-mobile-phone',
        'icon-envelope-alt',
        'icon-paper-clip',
        'icon-laptop',
        'icon-asterisk',
        'icon-user',
        'icon-credit-card',
        'icon-compass',
        'icon-map-marker',
        'icon-certificate',
        'icon-ok-sign',
        'icon-star-empty',
        'icon-link',
        'icon-cog',
        'icon-picture',
        'icon-camera',
        'icon-film',
        'icon-music',
        'icon-gift',
        'icon-bookmark-empty',
        'icon-book',
        'icon-thumbs-up',
        'icon-time',
        'icon-bell',
        'icon-qrcode',
        'icon-home',
        'icon-comments-alt',
        'icon-retweet',
        'icon-rss',
        'icon-sun',
        'icon-adjust',
        'icon-barcode',
        'icon-bullhorn',
        'icon-folder-close-alt',
        'icon-check',
        'icon-leaf',
        'icon-refresh',
        'icon-file-alt',
        'icon-tag',
        'icon-tasks',
        'icon-plane',
        'icon-globe',
        'icon-info-sign',
        'icon-question-sign',
        'icon-off',
        'icon-magic',
        'icon-flag',
        'icon-exclamation-sign'
    ];

    var iconChoose = "icon-home";
    var callback = null;

    var style = '<style>.iconPicker{width: 202px;background: #f9f9f9;border: 1px solid #428bca;padding: 2px;display: none;position: absolute;z-index: 2;}' +
        '.iconPicker .icon{width: 24px;height: 24px;line-height: 24px;font-size: 16px;margin: 2px;color: #2a6496;display: inline-block;border: 1px solid #e0e0e0;text-align: center;}</style>';

    exports.setIcon = function (btn, wrap, func) {
        callback = func;

        var str = '<span class="iconPicker" onmouseout="mouseOutIcon()">';
        for (var i in icons) {
            str += '<i class="icon ' + icons[i] + '" onclick="clickIcon(\'' + icons[i] + '\')" onmouseover="mouseOverIcon(\'' + icons[i] + '\')" ></i>';
        }
        str += '</span>';

        $(wrap).html(style + str);
//        console.log(str);

        $(document).on('click', '.icon-preview', function () {
            $('.iconPicker').fadeIn();
        });

    };

    window.clickIcon = function (icon) {
        // 设定colorHex
        iconChoose = icon;
        $('#iconVal').val(iconChoose);
        $('.iconPicker').hide();

        if (typeof callback == 'function') {
            callback(iconChoose);
        }
    };

    window.mouseOverIcon = function (icon) {
        $('.icon-preview').removeClass().addClass('icon-preview ' + icon);
    };

    window.mouseOutIcon = function () {
        $('.icon-preview').removeClass().addClass('icon-preview ' + iconChoose);
    }


});