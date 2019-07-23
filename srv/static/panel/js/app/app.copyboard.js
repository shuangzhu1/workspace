/**
 *
 * -- usage --
 * <a href="javascript:;" class="copyBtn miBtn" data-clipboard-text="http://12345709.m.local/article/detail/NZDXQN-0">复制链接</a>
 *  seajs.use('app/app_copyboard', function(copy) {
        copy.copyBoard('.copyBtn');
    });
 *
 */
define(function (require, exports) {
    require('tools/ZeroClipboard.js');
    exports.copyBoard = function (elem) {
        ZeroClipboard.config({ moviePath: '/static/panel/js/tools/ZeroClipboard.swf' });

        var client = new ZeroClipboard($(elem));


        client.on('ready', function (event) {
            // console.log( 'movie is loaded' );

            client.on('copy', function (event) {
//                event.clipboardData.setData('text/plain', event.target.innerHTML);
            });

            client.on('aftercopy', function (event) {
//                console.log(event.target);
                alert('复制成功！');
//                console.log('Copied text to clipboard: ' + event.data['text/plain']);
            });
        });

        client.on('error', function (event) {
            console.log('ZeroClipboard error of type "' + event.name + '": ' + event.message);
            ZeroClipboard.destroy();
        });
    }
});