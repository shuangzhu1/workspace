/**
 * Created by Gold on 14-5-22.
 */
(function($){

	seajs.use('app/panel/panel.storage', function (s) {
        s.getImg('#upload', function (res) {
            $('#activityPic').val(res.url);
            $('#picPreview').attr('src', res.url);
        }, false);
    });

	seajs.use('app/app.editor', function(editor) {
        editor.init("#description"); //选择器作为参数
    });

	$('#wheelsetting').validate({
		errorElement: 'div',
		errorClass: 'help-block',
		focusInvalid: false,
		rules: {
			name:"required",
			prize_degree:"required"
		},

		messages: {
			name: {
				required: "活动名称不能为空"
			},
			prize_degree: {
				required: "中奖角度不能为空"
			}
		},

		highlight: function (e) {
			$(e).closest('.form-group').removeClass('has-info').addClass('has-error');
		},

		success: function (e) {
			$(e).closest('.form-group').removeClass('has-error').addClass('has-info');
			$(e).remove();
		}
	});
	$('#option_number').ace_spinner({value:$('#option_number').val() ? parseInt($('#option_number').val()) : 0,min:0,step:1, on_sides: true, icon_up:'icon-plus smaller-75', icon_down:'icon-minus smaller-75', btn_up_class:'btn-success' , btn_down_class:'btn-danger'});
	$('#times').ace_spinner({value:$('#times').val() ? parseInt($('#times').val()) : 0,min:0,step:1, on_sides: true, icon_up:'icon-plus smaller-75', icon_down:'icon-minus smaller-75', btn_up_class:'btn-success' , btn_down_class:'btn-danger'});

	$('#wheeloption').validate({
		errorElement: 'div',
		errorClass: 'help-block',
		focusInvalid: false,
		rules: {
			option_name:"required",
			angle:{required:true, number:true},
			chance:{required:true, number:true}
		},

		messages: {
			option_name: {
				required: "奖项名称不能为空"
			},
			angle:{required:"请填写中奖角度", number:"中奖角度必须为数字"},
			chance:{required:"请填写中奖率", number:"中奖率必须为数字"}
		},

		highlight: function (e) {
			$(e).closest('.form-group').removeClass('has-info').addClass('has-error');
		},

		success: function (e) {
			$(e).closest('.form-group').removeClass('has-error').addClass('has-info');
			$(e).remove();
		}
	});

	$('#scratchoption').validate({
		errorElement: 'div',
		errorClass: 'help-block',
		focusInvalid: false,
		rules: {
			option_name:"required",
			chance:{required:true, number:true}
		},

		messages: {
			option_name: {
				required: "奖项名称不能为空"
			},
			chance:{required:"请填写中奖率", number:"中奖率必须为数字"}
		},

		highlight: function (e) {
			$(e).closest('.form-group').removeClass('has-info').addClass('has-error');
		},

		success: function (e) {
			$(e).closest('.form-group').removeClass('has-error').addClass('has-info');
			$(e).remove();
		}
	});

	$('.date-picker').datepicker({autoclose:true}).next().on(ace.click_event, function(){
		$(this).prev().focus();
	});
})(jQuery)
