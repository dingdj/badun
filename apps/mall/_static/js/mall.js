$(function(){
    var mySwiper = new Swiper('.swiper-container',{
        autoplay : 5000,
        autoplayDisableOnInteraction:false,
        noSwiping : true,
        pagination: '.pagination',
        loop:true,
        grabCursor: true,
        paginationClickable: true

    })

    $(".picScroll-left").slide({
        titCell:".hd ul",
        mainCell:".bd ul",
        autoPage:true,
        effect:"left",
        autoPlay:true,
        vis:6,
        trigger:"click",
        scroll:6,
        easing:"swing",
        interTime:4000,
    });

    $(".selected-goods-head li:first-child").addClass("se-go-on");
    $(".selected-goods-content ul:first-child").show();
    $(".selected-goods-head li").on("mouseover",function(){
        $(this).addClass("se-go-on").siblings().removeClass("se-go-on");
        $(this).parent().parent().siblings().find("ul").hide().eq($(this).index()).show();
    })
})