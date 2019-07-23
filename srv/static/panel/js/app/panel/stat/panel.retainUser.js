define(function (require, exports, module) {
    var base = require('app/panel/panel.base.js?v=1.0');//公共函数
    exports.page = function (option) {
        opt = {'url': '/api/stat/retainUser'};
        opt = $.extend(opt, option);
        var page = base.pageList(opt);
    };
});