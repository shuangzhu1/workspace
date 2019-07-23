define(function (require, exports) {
    require("/static/base/CryptoJSv3.1.2/crypto-js.js");
    exports.encrypt = function (params) {
        function js_encrpyt(content) {
            var md5_text = "123kjh878kjkuy76";
            var iv_text = "klgwl.com4444444";////16位
            var key_hash = CryptoJS.MD5(md5_text);
            var key = CryptoJS.enc.Utf8.parse(key_hash);
            var iv = CryptoJS.enc.Utf8.parse(iv_text);
            var encrypted = CryptoJS.AES.encrypt(content, key, {
                iv: iv,
                mode: CryptoJS.mode.CBC,
                padding: CryptoJS.pad.ZeroPadding
            });
            return encrypted.toString();
        }

        var content = 't=' + (new Date()).valueOf();
        for (var i in params) {
            content += "&" + i + "=" + params[i];
        }
        return js_encrpyt(content);
    }
});