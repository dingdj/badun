$(function(){
    /*header选择要搜索的类型*/
    $(".direction a").on("click",function(){
        var inputKey = $(this).index();
        if(inputKey==0){
            $(".lookup input").attr('placeholder','请输入您要搜索的课程');
            $(".direction a").removeClass("active02");
            $(this).addClass("active02");
        }else if(inputKey==1){
            $(".lookup input").attr('placeholder','请输入您要搜索的机构');
            $(".direction a").removeClass("active02");
            $(this).addClass("active02");
        }else if(inputKey==2){
            $(".lookup input").attr('placeholder','请输入您要搜索的老师');
            $(".direction a").removeClass("active02");
            $(this).addClass("active02");
        }
    });

    /*banner*/
    var swiper = new Swiper('.swiper-container', {
        autoplay : 4000,
        speed:800,
        pagination: '.swiper-pagination',
        paginationClickable: true,
        nextButton: '.swiper-button-next',
        prevButton: '.swiper-button-prev',
        spaceBetween: 30,
        effect: 'fade',
        autoplayDisableOnInteraction : false,
        simulateTouch : false,
        preventClicks : false,
        keyboardControl : true,
        loop:true
    });

    /*热门资讯*/
    function hotFun(){
        var ulHeig = $('.hot-news ul').height()-20;
        var allHei = "-" + ulHeig + "px";
        var noHei = $('.hot-news ul').css("marginTop");
        if(noHei==allHei){
            $('.hot-news ul').css("marginTop","32px");

        }
        $('.hot-news ul').css("marginTop","+=-1px");
    }
    var time;
    time = setInterval(hotFun,80);
    $(".hot-news ul").hover(
        function(){
        clearInterval(time)
        },function(){
            time = setInterval(hotFun,80);
        }
    )

    /*直播预告*/
    $(".slideTxtBox").slide({
        effect:"left",
        autoPlay:true,
        pnLoop:false,
        interTime:4000,
        delayTime:500,
        easing:"easeInQuint"
    });


})
