define(function (require, exports) {

    exports.Search = function (elem, callback) {
        var search = new Search();
        search.init(elem, callback);

    };
    function Search() {
    }

//inputType //输入法 1-英文输入法 2-中文输入法
    Search.prototype = {
        opt: {inputType: 1, input_elem: '', lastKeyCode: '', keywords: '', timer: ''},
        init: function (elem, callback) {
            this.opt.input_elem = $(elem);
            this.opt.callback = callback;
            var __this = this;
            $(this.opt.input_elem)
                .on('compositionstart', function (e) {
                    __this.onCompositionStart(e);
                })
                .on("compositionend", function (e) {
                    __this.onCompositionEnd(e)
                })
                .on('keyup', function (e) {
                    __this.onKeyUp(e)
                })
                .on('keydown', function (e) {
                    __this.onKeyDown(e)
                })
                .on('input', function (e) {
                    __this.onChange(e)
                })

            ;
        },
        onCompositionStart: function (e) {
            this.opt.inputType = 2;
            if (e.target.value != this.opt.keywords && e.target.value != '') {
                this.opt.keywords = e.target.value;
                this.commit(e.target.value)
            }
        },
        onCompositionEnd: function (e) {
            this.opt.inputType = 1;
            if (e.target.value != this.opt.keywords && e.target.value != '') {
                this.opt.keywords = e.target.value;
                this.commit(e.target.value)
            }
        },
        onKeyUp: function (e) {
            var isEnterKey = e.keyCode === 13;
            if (isEnterKey && e.keyCode === this.opt.lastKeyCode) {
                var value = e.target.value;
                this.commit(value);
            }
        },
        onKeyDown: function (e) {
            this.opt.lastKeyCode = e.keyCode;
        },

        onChange: function (e) {
            var value = e.target.value;
            if (this.opt.inputType == 1) {
                this.commit(value);
            }
        },
        /*提交数据*/
        commit: function (value) {
            var __this = this;
            this.opt.timer && clearTimeout(__this.opt.timer);
            this.opt.timer = setTimeout(function () {
                __this.opt.callback(value)
            }, 500);
        }
    };
});
