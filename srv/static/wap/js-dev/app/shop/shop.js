/**
 * Created by ykuang on 2017/11/7.
 */
define(function (require, exports) {
    var base = require('base');
    var load_more = require("app/more");
    exports.shop = function (shop_id) {
        load_more.more('/api/shop/goods', {to: shop_id}, function () {
            // psinit.init();
        }, "#goods_wrap", ".goods_item")
    }
});