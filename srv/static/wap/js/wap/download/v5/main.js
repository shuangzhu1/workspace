
var pageHeight = $()
$("#imgtip").on("click",function(){
    event.stopPropagation();
    $("#imgtip").hide();
})
$('.btn,.download-btn').on('click',function () {
    window.location.href = "http://a.app.qq.com/o/simple.jsp?pkgname=com.hn.d.valley";
});
$(function () {
    $(window).scroll(function () {
        var a = document.getElementsByClassName('btn')[0].offsetTop + document.getElementsByClassName('btn')[0].offsetHeight ;
        if (a >= $(window).scrollTop() && a < ($(window).scrollTop()+$(window).height())) {//可见
            $('.footer').slideUp('fast');
        }else {//不可见
            $('.footer').slideDown('fast');

        }
    });

})