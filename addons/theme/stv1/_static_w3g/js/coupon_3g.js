$(function(){

    $(".yh_coupon li:first-child").addClass("active");
    $(".yh_coupon_order:first-child").show();
    $(".yh_coupon li").on('click',function() {
        $(this).addClass("active").siblings().removeClass("active");
        var i = $(this).index();
        $(".yh_coupon_order").hide();
        $(".yh_coupon_order").eq(i).show();
    });
})