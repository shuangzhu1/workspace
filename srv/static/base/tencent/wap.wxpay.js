/**
 * Created by arimis on 15-2-12.
 */

/**
 * 创建一个微信支付接口对象
 * @constructor
 * @param {{}} config
 * @example
 *
 *      //1 初始化对象
 *      var ww = new WapWxpay();
 *      ww.setConfig(appId, appKey, partnerId, partnerKey});
 *
 *      //2 设置处理函数
 *      ww.setPayHandle('#payBtn', {body:"",orderNumber:"",ip:"",totalFee:1.00}, function(res) {
 *          alert("支付成功！");
 *      }, function(res) {
 *          alert("支付失败了");
 *      });
 *
 *      //3 发送请求
 *      ww.request();
 *  也可以采用method chains的方式调用
 *      new WapWxpay({
 *          appId: "",
 *          appKey: "",
 *          partnerId: "",
 *          partnerKey: ""
 *      }).setPayHandle('#payBtn', {body:"",orderNumber:"",ip:"",totalFee:1.00}, function(res) {
 *          alert("支付成功！");
 *      }, function(res) {
 *          alert("支付失败了");
 *      }).request();
 */
var WapWxpay = function (config) {
    //如果没有加载加密js文件，先加载
    if(typeof CryptoJS == "undefined") {
        var headEle = document.getElementsByTagName("head").item(0);
        var md5jsEle = document.createElement('script');
        md5jsEle.src = "/static/base/CryptoJSv3.1.2/rollups/md5.js";
        var sha1jsEle = document.createElement('script');
        sha1jsEle.src = "/static/base/CryptoJSv3.1.2/rollups/sha1.js";
        headEle.appendChild(md5jsEle);
        headEle.appendChild(sha1jsEle);
    }
    if(typeof config == "object") {
        this.setConfig(config.appId, config.appKey, config.partnerId, config.partnerKey);
    }
    return this;
};

/**
 * 生成的签名，存储以便多次利用
 * @type {string}
 */
WapWxpay.prototype.sign = "";
/**
 * 当前时间戳，防止多次计算从而导致签名跟pkg包值不一致
 * @type {string}
 */
WapWxpay.prototype.timestamp ='';
/**
 * 生成的随机字符串
 * @type {string}
 */
WapWxpay.prototype.nonceStr = "";

/**
 * 生成的请求包
 * @type {string}
 */
WapWxpay.prototype.pkgStr = "";

WapWxpay.prototype.status = "";

/**
 * 支付成功时的回调函数
 * @param res
 */
WapWxpay.prototype.okFunc = function (res) {};

/**
 * 支付失败时的回调函数
 * @param res
 */
WapWxpay.prototype.errFunc = function (res) {};


/**
 * 支付接口参数
 * @type {{partnerId: string, partnerKey: string, appId: string, appKey: string}}
 */
WapWxpay.prototype.config = {
    partnerId: "",
    partnerKey: "",
    appId: "",
    appKey: ""
};


/**
 * 支付请求时的订单数据
 * @type {{banktype: string, body: string, fee_type: string, input_charset: string, notify_url: string, out_trade_no: string, partner: string, spbill_create_ip: string, total_fee: number, partnerKey: string}}
 */
WapWxpay.prototype.pkgData = {
    banktype: "WX",
    body: "",//商品名称信息，这里由测试网页填入。
    fee_type: "1",//费用类型，这里1为默认的人民币
    input_charset: "UTF-8",//字符集，可以使用GBK或者UTF-8
    notify_url: "",//支付成功后将通知该地址
    out_trade_no: "",//订单号，商户需要保证该字段对于本商户的唯一性
    partner: "",//测试商户号
    spbill_create_ip: "127.0.0.1",//用户浏览器的ip，这个需要在前端获取。这里使用127.0.0.1测试值
    total_fee: 0,//总金额。
    partnerKey: ""//这个值和以上其他值不一样是：签名需要它，而最后组成的传输字符串不能含有它。这个key是需要商户好好保存的。
};


/**
 *
 * @type {string}
 */
WapWxpay.prototype.STATUS_BASE = "0";

/**
 *
 * @type {string}
 */
WapWxpay.prototype.STATUS_OK = "get_brand_wcpay_request:ok";

/**
 *
 * @type {string}
 */
WapWxpay.prototype.STATUS_CANCEL = "get_brand_wcpay_request:cancel";

/**
 *
 * @type {string}
 */
WapWxpay.prototype.STATUS_FAIL = "get_brand_wcpay_request:fail";

/**
 *
 * @type {{get_brand_wcpay_request:cancel: string, get_brand_wcpay_request:fail: string}}
 * @private
 */
WapWxpay._errorMessage = {
    '0' : "未支付",
    'get_brand_wcpay_request:ok' : "支付成功",
    "get_brand_wcpay_request:cancel" : "用户取消",
    "get_brand_wcpay_request:fail" : "支付失败"
};

/**
 * 活取当前对象支付状态
 * @returns {string}
 */
WapWxpay.prototype.getStatus = function() {
    return this._errorMessage[this.status];
};

/**
 * 设置当前对象支付状态
 * @param {string} tatus
 * @returns {WapWxpay}
 */
WapWxpay.prototype.setStatus = function(status) {
    this.status = status;
    return this;
};

/**
 * 初始化支付接口参数
 * @param {string} appId
 * @param {string} appKey
 * @param {string} partnerId
 * @param {string} partnerKey
 * @returns {WapWxpay}
 */
WapWxpay.prototype.setConfig = function (appId, appKey, partnerId, partnerKey) {
    this.config.appId = appId;
    this.config.appKey = appKey;
    this.config.partnerId = partnerId;
    this.config.partnerKey = partnerKey;
    return this;
};

/**
 * 设置支付请求的订单相关信息
 * @param {string} body
 * @param {string} orderNo
 * @param {string} clientIp
 * @param {number} totalFee
 * @returns {WapWxpay}
 */
WapWxpay.prototype.setPkgData = function(body, orderNo, clientIp, totalFee) {
    this.pkgData.body = body;
    this.pkgData.out_trade_no = orderNo;
    this.pkgData.spbill_create_ip = clientIp;
    this.pkgData.total_fee = totalFee;
    this.pkgData.partner = this.getPartnerId();
    this.pkgData.partnerKey = this.getPartnerKey();
    alert(this.pkgData);
    return this;
};

/**
 *
 * @returns {string|*}
 */
WapWxpay.prototype.getPartnerId = function () {
    return this.config.partnerId;
};


/**
 *
 * @returns {string|*}
 */
WapWxpay.prototype.getPartnerKey = function () {
    //return "8934e7d15453e97507ef794cf7b0519d";
    return this.config.partnerKey;
};

/**
 *
 * @returns {string|*}
 */
WapWxpay.prototype.getAppId = function () {
    //return "wxf8b4f85f3a794e77";
    return this.config.appId;
};

/**
 *
 * @returns {string|*}
 */
WapWxpay.prototype.getAppKey = function () {//替换appkey
    //return "2Wozy2aksie1puXUBpWD8oZxiD1DfQuEaiC7KcRATv1Ino3mdopKaPGQQ7TtkNySuAmCaDCrw4xhPY5qKTBl7Fzm0RgR3c0WaVYIXZARsxzHV2x7iwPPzOz94dnwPWSn";
    return this.config.appKey;
};

/**
 * @return {object}
 */
WapWxpay.prototype.helper = {

    /**
     * 去除字符串中的多余空白符
     * @param {string} str
     * @param {string} is_global 'g' or null
     * @returns {XML|string|void}
     */
    trim: function (str, is_global) {
        var result;
        result = str.replace(/(^\s+)|(\s+$)/g, "");
        if (is_global.toLowerCase() == "g") result = result.replace(/\s/g, "");
        return result;
    },

    /**
     * 去除字符串中的br标签
     * @param {string} key
     * @returns {string}
     */
    clearBr: function (key) {
        key = this.trim(key, "g");
        key = key.replace(/<\/?.+?>/g, "");
        key = key.replace(/[\r\n]/g, "");
        return key;
    },

    /**
     * 获取随机数
     * @returns {number}
     */
        getANumber: function () {
        var date = new Date();
        var times1970 = date.getTime();
        var times = date.getDate() + "" + date.getHours() + "" + date.getMinutes() + "" + date.getSeconds();
        var encrypt = times * times1970;
        if (arguments.length == 1) {
            return arguments[0] + encrypt;
        } else {
            return encrypt;
        }
    },

    /**
     * 获得时间戳
     * @returns {string}
     */
    getTimeStamp: function () {
        if (this.timestamp > 0) {
            return this.timestamp;
        }
        var timestamp = new Date().getTime();
        var timestampstring = timestamp.toString();//一定要转换字符串
        this.config.timestamp = timestampstring;
        return timestampstring;
    },


    /**
     * 获得32位随机字符串
     * @returns {string}
     */
    getNonceStr: function () {
        var $chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789';
        var maxPos = $chars.length;
        var nonceStr = "";
        for (var i = 0; i < 32; i++) {
            nonceStr += $chars.charAt(Math.floor(Math.random() * maxPos));
        }
        this.config.nonceStr = nonceStr;
        return nonceStr;
    }
};

/**
 * 获取支付请求的订单信息package字符串
 * @returns {string}
 */
WapWxpay.prototype.getPackage = function () {
    if(this.pkgStr.length > 0) {
        return this.pkgStr;
    }
    var banktype = this.pkgData.banktype;
    var body = this.pkgData.body;//商品名称信息，这里由测试网页填入。
    var fee_type = this.pkgData.fee_type;//费用类型，这里1为默认的人民币
    var input_charset = this.pkgData.input_charset;//字符集，可以使用GBK或者UTF-8
    var notify_url = this.pkgData.notify_url;//支付成功后将通知该地址
    var out_trade_no = this.pkgData.out_trade_no;//订单号，商户需要保证该字段对于本商户的唯一性
    var partner = this.getPartnerId();//测试商户号
    var spbill_create_ip = this.pkgData.spbill_create_ip;//用户浏览器的ip，这个需要在前端获取。这里使用127.0.0.1测试值
    var total_fee = this.pkgData.total_fee;//总金额。
    var partnerKey = this.getPartnerKey();//这个值和以上其他值不一样是：签名需要它，而最后组成的传输字符串不能含有它。这个key是需要商户好好保存的。

    //首先第一步：对原串进行签名，注意这里不要对任何字段进行编码。这里是将参数按照key=value进行字典排序后组成下面的字符串,在这个字符串最后拼接上key=XXXX。由于这里的字段固定，因此只需要按照这个顺序进行排序即可。
    var signString = "bank_type=" + banktype + "&body=" + body + "&fee_type=" + fee_type + "&input_charset=" + input_charset + "&notify_url=" + notify_url + "&out_trade_no=" + out_trade_no + "&partner=" + partner + "&spbill_create_ip=" + spbill_create_ip + "&total_fee=" + total_fee + "&key=" + partnerKey;

    var md5SignValue = ("" + CryptoJS.MD5(signString)).toUpperCase();
    //然后第二步，对每个参数进行url转码，如果您的程序是用js，那么需要使用encodeURIComponent函数进行编码。

    banktype = encodeURIComponent(banktype);
    body = encodeURIComponent(body);
    fee_type = encodeURIComponent(fee_type);
    input_charset = encodeURIComponent(input_charset);
    notify_url = encodeURIComponent(notify_url);
    out_trade_no = encodeURIComponent(out_trade_no);
    partner = encodeURIComponent(partner);
    spbill_create_ip = encodeURIComponent(spbill_create_ip);
    total_fee = encodeURIComponent(total_fee);

    //然后进行最后一步，这里按照key＝value除了sign外进行字典序排序后组成下列的字符串,最后再串接sign=value
    var completeString = "bank_type=" + banktype + "&body=" + body + "&fee_type=" + fee_type + "&input_charset=" + input_charset + "&notify_url=" + notify_url + "&out_trade_no=" + out_trade_no + "&partner=" + partner + "&spbill_create_ip=" + spbill_create_ip + "&total_fee=" + total_fee;
    completeString = completeString + "&sign=" + md5SignValue;

    this.pkgStr = completeString;//记住package，方便最后进行整体签名时取用
    return completeString;
};


/**
 * 获得签名算法类型名称
 * @returns {string}
 */
WapWxpay.prototype.getSignType = function () {
    return "SHA1";
};

/**
 * 获得签名
 * @returns {string}
 */
WapWxpay.prototype.getSign = function () {
    var app_id = this.getAppId().toString();
    var app_key = this.getAppKey().toString();
    var nonce_str = this.config.nonceStr;
    var package_string = this.config.pkgStr;
    var time_stamp = this.config.timestamp;
    //第一步，对所有需要传入的参数加上appkey作一次key＝value字典序的排序
    var keyvaluestring = "appid=" + app_id + "&appkey=" + app_key + "&noncestr=" + nonce_str + "&package=" + package_string + "&timestamp=" + time_stamp;
    this.sign = CryptoJS.SHA1(keyvaluestring).toString();
    return sign;
};

/**
 * 绑定微信支付请求
 * @param {string} eTarget 绑定事件的对象ID
 * @param {{body:string, orderNumber:string, ip:string, totalFee:float}} orderInfo 支付请求的订单信息，结构如下： {body:"",orderNumber:"",ip:"",totalFee:1.00}
 * @param {function} okFunc 支付成功时的回调函数
 * @param {function} errFunc 支付失败时的回调函数
 * @returns {WapWxpay}
 */
WapWxpay.prototype.setPayHandle = function(orderInfo, okFunc, errFunc, bind, eTarget) {
    this.setPkgData(orderInfo.body, orderInfo.orderNumber, orderInfo.ip, orderInfo.totalFee);
    if(typeof okFunc == "function") {
        this.okFunc = okFunc;
    }
    if(typeof errFunc == "function") {
        this.errFunc = errFunc;
    }
    if(typeof bind != "undefined" && bind && typeof eTarget == "string" && eTarget.length > 0) {
        this._bindEvent(eTarget);
    }
    return this;
};


/**
 * 绑定事件
 * @param {string} 被绑定事件的对象ID
 * @returns {WapWxpay}
 */
WapWxpay.prototype._bindEvent = function(eTarget) {
    var runtimeObj = this;
    // 当微信内置浏览器完成内部初始化后会触发WeixinJSBridgeReady事件。
    document.addEventListener('WeixinJSBridgeReady', function onBridgeReady() {
    //公众号支付
        if(e.preventDefault) {
            e.preventDefault();
        }
        else {
            e.returnValue = false;
        }
        runtimeObj.request();
        WeixinJSBridge.log('yo~ ready.');
    }, false);
    return this;
};

/**
 * 发起支付请求
 */
WapWxpay.prototype.request = function() {
    var runtimeObj = this;
    var param = {
        "appId" : runtimeObj.getAppId(), //公众号名称，由商户传入
        "timeStamp" : runtimeObj.helper.getTimeStamp(), //时间戳
        "nonceStr" : runtimeObj.helper.getNonceStr(), //随机串
        "package" : runtimeObj.getPackage(),//扩展包
        "signType" : runtimeObj.getSignType(), //微信签名方式:1.sha1
        "paySign" : runtimeObj.getSign() //微信签名
    };
    // 当微信内置浏览器完成内部初始化后会触发WeixinJSBridgeReady事件。
    document.addEventListener('WeixinJSBridgeReady', function onBridgeReady() {
        //公众号支付
        WeixinJSBridge.invoke('getBrandWCPayRequest', param, function(res){
            runtimeObj.setStatus(res.err_msg);
            if(res.err_msg == runtimeObj.STATUS_OK) {
                runtimeObj.okFunc(runtimeObj, res);
            }
            else {
                runtimeObj.errFunc(runtimeObj, res);
            }
            // 使用以上方式判断前端返回,微信团队郑重提示：res.err_msg将在用户支付成功后返回ok，但并不保证它绝对可靠。
            //因此微信团队建议，当收到ok返回时，向商户后台询问是否收到交易成功的通知，若收到通知，前端展示交易成功的界面；若此时未收到通知，商户后台主动调用查询订单接口，查询订单的当前状态，并反馈给前端展示相应的界面。
        });


        WeixinJSBridge.log('yo~ ready.');

    }, false)

};


/**
 * 判断外部环境是否时微信
 * @param {boolean} ver5 是否需要判断微信是否时v5以上版本
 */
WapWxpay.prototype.isWechat = function (ver5) {
    //Mozilla/5.0(iphone;CPU iphone OS 5_1_1 like Mac OS X) AppleWebKit/534.46(KHTML,likeGeocko) Mobile/9B206 MicroMessenger/5.0
    var ua = navigator.userAgent.toLowerCase();
    var rwx = /.*(micromessenger)\/([\w.]+).*/;
    var match = rwx.exec(ua);
    if (match) {
        if (match[1] === 'micromessenger') {
            if (ver5) {
                if (parseFloat(match[2]) >= 5) {
                    return true;
                }
            } else {
                return true;
            }
        }
    }
    return false;
};
