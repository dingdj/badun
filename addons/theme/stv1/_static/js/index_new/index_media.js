$(function () {
    /*banner*/
    var mySwiper = new Swiper('.swiper-container',{
        pagination: '.pagination',
        loop:true,
        grabCursor: true,
        paginationClickable: false,
        autoplayDisableOnInteraction:false,
        autoplay :4000,
    })

    /*直播预告*/
    $(".live-date li:first").css("color","#00BED4");
    $(".live-date li i:first").css("color","#00BED4");
    $(".live-data:first").show();
    $(".live-date li").on("click",function(){
        $(this).css("color","#00BED4").siblings().css("color","#656565");
        var liKey = $(this).index();
        $(".live-date li i").css("color","#e5e5e5");
        $(".live-date li i").eq(liKey).css("color","#00BED4");
        $(".live-data").fadeOut(200).hide();
        $(".live-data").eq(liKey).fadeIn(200).show();
    })

    $(window).on("ready",function(){
        $(".live-content li:last-child").css("border","none")
    })

    /*热门资讯*/
     $(".slideTxtBox").slide({
        autoPlay:true,
        delayTime:500,
        easing:"easeInQuint",
        effect:"left",
        pnLoop:true,
        interTime:5000
    });

     var lis = $(".live-list li").siblings();
     for(var i=0;i<lis.length;i++){
         if((i+1)%2==0){
             lis.eq(i).css("float","right");
         }
     }
})
