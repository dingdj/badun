$(function () {
    $(".content-card:first").show();
    $(".center_right_tit li:first").addClass("on")
    $(".center_right_tit li").on("click",function(){
        $(this).addClass("on").siblings().removeClass("on");
        var crIndex = $(this).index();
        $(".content-card").hide();
        $(".content-card").eq(crIndex).show();
    });
})