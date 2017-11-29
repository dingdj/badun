$(function(){
    /*header选择要搜索的类型*/
    $(".direction a").on("click",function(){
        var inputKey = $(this).index();
        if(inputKey==0){
            $(".lookup input").attr('placeholder','请输入您要搜索的课程');
            $(".direction a").css({"background-color":"transparent","color":"#555"});
            $(this).css({"background-color":"#00bed4","color":"#fff"});
        }else if(inputKey==1){
            $(".lookup input").attr('placeholder','请输入您要搜索的机构');
            $(".direction a").css({"background-color":"transparent","color":"#555"});
            $(this).css({"background-color":"#00bed4","color":"#fff"});
        }else if(inputKey==2){
            $(".lookup input").attr('placeholder','请输入您要搜索的老师');
            $(".direction a").css({"background-color":"transparent","color":"#555"});
            $(this).css({"background-color":"#00bed4","color":"#fff"});
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
    /*function hotFun(){
        var ulHeig = $('.hot-news ul').height()+40;
        var allHei = "-" + ulHeig + "px";
        var noHei = $('.hot-news ul').css("marginTop");
        if(noHei==allHei){
            $('.hot-news ul').css("marginTop","-20px");

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
    )*/

    /*直播预告*/
    $(".slideTxtBox").slide({
        effect:"left",
        autoPlay:true,
        pnLoop:false,
        interTime:4000,
        delayTime:500,
        easing:"easeInQuint"
    });

    /*分享*/
    var options = {
        useEasing : true,
        useGrouping : false,
        separator : ',',
        decimal : '.',
        prefix : '',
        suffix : ''
    };


    /*热门资讯*/
    $('.single-item').slick({
        dots: true,
        infinite: true,
        speed: 300,
        slidesToShow: 1,
        slidesToScroll: 1,
        autoplay: true,
    });

})
