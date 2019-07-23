/**
 * Override bootstrap modal's enforceFocus method to fix
 * too much recursion bug when using multiple dialogs
 */
$.fn.modal.Constructor.prototype.enforceFocus = function () {
    var element = this.$element;
    element.focus();
    var modals = $('.modal:visible');
    if (modals.length > 1) {
        var oz = 1050;
        modals.each(function () {
            if (this == element.get(0)) return;
            var zi = parseInt($(this).css('z-index'));
            if (zi > oz) oz = zi;
        });
        this.$backdrop.css('z-index', ++oz);
        this.$element.css('z-index', ++oz);
    }
};

/**
 * Common dialog
 * Created by dante@flashbay.com
 */
function BSDialog() {
}

/**
 * Get the dialog widget
 */
BSDialog.getDialog = function () {
    var $dialog = null;
    if (BSDialog.$dialog) {
        // Has cached dialog
        $dialog = BSDialog.$dialog;
    } else {
        // No cached dialog found, create a new one.
        var $dialogWindow = $(document.createElement('div'));
        $dialogWindow.addClass('bs-dialog modal fade');
        var $dialogBox = $("<div class='modal-dialog'></div>");
        var $dialogContent = $("<div class='modal-content'></div>");
        $dialogContent.append('<div class="modal-body"><div class="bs-dialog-body"></div></div>');
        $dialogContent.append('<div class="modal-footer"><div class="bs-dialog-footer"></div></div>');
        $dialogBox.append($dialogContent);
        $dialogWindow.append($dialogBox);
        $dialog = $dialogWindow;

        // Cache this dialog
        $('body').append($dialogWindow);
        BSDialog.$dialog = $dialog;

        // Make window draggable
        /*$dialog.draggable({
         handle: '.modal-body'
         });*/
    }

    return $dialog;
};

/**
 * Dialog
 * Param btns:
 * [{
 * 		label 		: '',
 * 		cssClass	: 'btn-primary',
 * 		onclick	: function,	
 * }]
 */
BSDialog.dialog = function (message, btns) {
    var $dialog = BSDialog.getDialog();

    // Set content
    $dialog.find('.bs-dialog-body').html(message);
    var $dialogFooter = $dialog.find('.bs-dialog-footer');
    $dialogFooter.html('');
    for (var i = 0; i < btns.length; i++) {
        var btn = btns[i];
        var $btn = $('<button class="btn" data-dismiss="modal">关闭</button>');
        if (btn.label) {
            $btn.text(btn.label);
        }
        if (btn.cssClass) {
            $btn.addClass(btn.cssClass);
        }
        $btn.click({btn: btn, $btn: $btn}, function (event) {
            if (event.data.btn.onclick) {
                event.data.btn.onclick();
            }
        });
        $dialogFooter.append($btn);
    }

    // Show window
    $dialog.modal({
        backdrop: 'static',
        show: true
    });
};

/**
 * Override native alert
 * This alert won't get the process paused.
 */
BSDialog.alert = function (message, callback) {
    BSDialog.dialog(message, [
        {
            label: '关闭',
            onclick: callback
        }
    ]);
};

/**
 * Confirm dialog
 * Just an alias of BSDialog.dialog
 * Param btns:
 * [{
 * 		label 		: '',
 * 		cssClass	: 'btn-primary',
 * 		onclick	: function,	
 * }]
 */
BSDialog.confirm = function (message, btns) {
    BSDialog.dialog(message, btns);
};

// Simply replace the native alert
//window.alert = BSDialog.alert;
//window.confirm = BSDialog.confirm;