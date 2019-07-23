/**
 * Created by ykuang on 2017/7/20.
 */
function Url() {
    this.baseUrl = '';
    this.query = {};
    this.init();
}

Url.prototype = {
    init: function () {
        this.getBaseUrl(true);
        this.getUrlArgObject();
    },
    //获取地址栏参数 并以json格式返回
    getUrlArgObject: function () {
        var args = new Object({});
        var query = location.search.substring(1);//获取查询串
        var pairs = query.split("&");//在逗号处断开
        for (var i = 0; i < pairs.length; i++) {
            var pos = pairs[i].indexOf('=');//查找name=value
            if (pos == -1) {//如果没有找到就跳过
                continue;
            }
            var argname = pairs[i].substring(0, pos);//提取name
            var value = pairs[i].substring(pos + 1);//提取value
            args[argname] = unescape(value);//存为属性
        }
        this.query = args;
        return args;
    },
    //获取地址栏基本地址 如：http://www.baidu.com
    getBaseUrl: function (pathname) {
        if (pathname !== undefined) {
            this.baseUrl = (window.location.protocol) + '//' + (window.location.host) + (window.location.pathname);
        } else {
            this.baseUrl = '/' + (window.location.pathname);
        }
        return this.baseUrl;
    },
    //设置参数 {p:20,type:2}
    setArgs: function (args) {
        var __this = this;
        for (var i in args) {
            //之前存在
            if (__this.query[i] !== undefined) {
                __this.query[i] = args[i];
            } else {
                __this.query[i] = args[i];
            }
        }
    },
    //删除参数 ['p','type']
    rmArgs: function (args) {
        var __this = this;
        for (var i in args) {
            delete __this.query[args[i]];
        }

    },
    //获取参数 ['p','type']
    getArgs: function (keys) {
        var result = {};
        for (var i in keys) {
            //存在
            if (this.query.keys[i] !== undefined) {
                result.keys[i] = this.query.keys[i];
            }
        }
        return result;
    },
    //获取最终url 例如http://www.baidu.com?p=20&type=8
    getUrl: function () {
        var url = this.baseUrl;
        if (JSON.stringify(this.query) == "{}") {
            return url;
        } else {
            var query = "";

            for (var i in  this.query) {
                query += "&"+i + '=' + this.query[i];
            }
            return url + '?' + query.substr(1);
        }
    }
};