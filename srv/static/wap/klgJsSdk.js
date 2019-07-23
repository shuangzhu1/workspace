!function (window, factory) {
    "function" == typeof define && (define.amd || define.cmd) ? define(function () {
        return factory(window)
    }) : factory(window,true)
}(window,function(window,global){
	var jsBridge = window.WebViewJavascriptBridge;
	var title = window.document.title;
	var link = window.location.href;
	//工具
	var agent = navigator.userAgent.toLowerCase();
	function isAndroid(){
		if( -1 !== agent.indexOf('android'))
			return true;
		else
			return false;
	}
	function isApple(){
		
		if( -1 !== agent.indexOf("iphone") || -1 !== agent.indexOf("ipad") )
			return true;
		else
			return false;
	}
	function isKlgWebview(){
		if( -1 !== agent.indexOf('klg'))
			return true;
		else
			return false;
	};

	//初始化jsbridge
	if( isKlgWebview )
	{
		 var readyFunc = [];

		if( isAndroid )
		{
			function connectWebViewJavascriptBridge(callback) {
            if (window.WebViewJavascriptBridge) {
                callback(WebViewJavascriptBridge)
            } else {
                document.addEventListener(
                    'WebViewJavascriptBridgeReady'
                    , function() {
                        callback(WebViewJavascriptBridge)
                    },
                    false
                );
           		}
        	}

	        connectWebViewJavascriptBridge(function(bridge) {
	            bridge.init();
	            for( var i = 0; i<readyFunc.length;i++)
				{
					var func = readyFunc[i];
					func();
				}
	    	})
		}

		if( isApple )
		{


            function setupWebViewJavascriptBridge(callback) {

                if (window.WebViewJavascriptBridge) {
		        	return callback(WebViewJavascriptBridge); 
		        }
		        if (window.WVJBCallbacks) { 
		        	return window.WVJBCallbacks.push(callback); 
		        }
		        window.WVJBCallbacks = [callback];
		        var WVJBIframe = document.createElement('iframe');
		        WVJBIframe.style.display = 'none';
		        WVJBIframe.src = 'https://__bridge_loaded__';
		        document.documentElement.appendChild(WVJBIframe);
		        setTimeout(function() { 
		        	document.documentElement.removeChild(WVJBIframe) 
		        }, 0)

            }
		    setupWebViewJavascriptBridge(function(bridge) {

                for( var i = 0; i<readyFunc.length;i++)
                {
                    var func = readyFunc[i];
                    func();
                }
			})
		}
	}

	/**
	 * 调用native方法
	 * [__call description]
	 * @param  {string}   handlerName [description]
	 * @param  {json}   data        [description]
	 * @param  {Function} callback    [description]
	 * @return {[type]}               [description]
	 */
	function __call(handlerName,data,callback)
	{
		jsBridge.callHandler(handlerName, data, callback);
	}

	/**
	 * 注册函数供native调用
	 * [__register description]
	 * @param  {string} handlerName [description]
	 * @param  {function} func        [description]
	 * @return {}             [description]
	 * func(data,callback)
	 */
	function __register(handlerName,func)
	{
		jsBridge.registerHandler(handlerName,func);
	}


    function callbackFunc(responseData,options) {
        var o = responseData.indexOf(":");
        switch (responseData.substring(o + 1)) {
        case "ok":
            options.success && options.success();
            break;
        case "cancel":
            options.cancel && options.cancel();
            break;
        }

    }

	var g = {
		'ready':function(func){//jsbridge注入完成后调用该方法
            readyFunc.push(func);
		},
		'onMenuShareAppMessage':function(options){

			/*var data = {};
			if(options.title !== undefined && options.title !== '')
				data.title = options.title;
            if(options.link !== undefined && options.link !== '')
                data.link = options.link;
            if(options.desc !== undefined )
                data.desc = options.desc;
            if(options.imgUrl !== undefined && options.imgUrl !== '')
                data.imgUrl = options.imgUrl;
            if(options.appName !== undefined )
                data.appName = options.appName;
            __call('setShareAppMessage',data,function(responData,options){
                var pos = responseData.indexOf(":");
                switch (responseData.substring(pos + 1)) {
                    case "ok":
                        options.success && options.success();
                        break;
                    case "cancel":
                        options.cancel && options.cancel();
                        break;
                }
			});*/



		}
	};
	return global && (window.klg = g),g;
	
});