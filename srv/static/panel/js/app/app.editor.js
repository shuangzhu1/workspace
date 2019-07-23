define(function (require, exports) {
    // jquery版本降级插件
    require('tools/xheditor/xheditor-1.2.2.min.js');

    exports.init = function (elem) {


        $(elem).ready(function () {
            $(elem).xheditor({
                tools: "Cut,Copy,Paste,Pastetext,Blocktag,Fontface,FontSize,Bold,Italic,Underline,Strikethrough,FontColor,BackColor,SelectAll,Removeformat,Align,List,Outdent,Indent,Link,Unlink,Anchor,Img,Hr,Table,Source,Preview,Fullscreen",
                remoteImgSaveUrl: '/api/upload/saveimg?from=xheditor',
                upLinkUrl: '/api/upload/file?from=xheditor',
                upLinkExt: "zip,rar,txt,doc,xls,ppt,docx,pptx,xlsx",
                upImgUrl: '/api/upload/img?from=xheditor',
                upImgExt: "jpg,jpeg,gif,png,bmp"
            });
        });
    }
});