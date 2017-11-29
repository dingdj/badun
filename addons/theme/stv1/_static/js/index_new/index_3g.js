$(function(){
    //banner
    var mySwiper = new Swiper('.swiper-container',{
        pagination: '.pagination',
        loop:true,
        grabCursor: true,
        paginationClickable: false,
        autoplayDisableOnInteraction:false,
        autoplay :4000,
    })

    //直播课
    $('.custom-card ul:first-child').show();
    $('.mw-rank-tabs li').on("click",function(){
        $(this).addClass("on").siblings().removeClass("on");
        $(this).parent().parent().siblings().find("ul").fadeOut(200).eq($(this).index()).fadeIn(200);
    });
})