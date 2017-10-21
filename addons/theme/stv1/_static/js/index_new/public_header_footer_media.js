$(function(){
    /*导航面板的操作*/
    $(".modular ").on("click",function(){
        $(".shadow-panle").show();
        $(".window-panle").css({"marginLeft":"0","box-shadow":"5px 0 10px rgba(58,69,88,0.3)"});
        $("body").css("overflow-y", "hidden")
    })

    $(".shadow-panle ").on("click",function(){
        $(".shadow-panle").hide();
        $(".window-panle").css({"marginLeft":"-70%","box-shadow":"0 0 0 rgba(58,69,88,0.3)"})
        $("body").css("overflow-y", "auto")
    })

    /*回到顶部*/
    $(window).scroll(function(){
        var wins = $(document).scrollTop();
        if(wins>1000){
            $(".footer span").show();
        }
        else{
            $(".footer span").hide();
        }
    });

    $(".footer span").on("click",function(){
        $("html,body").animate({scrollTop:"0px"},200)
    })

    $(".users").hover(function(){
       $('.users span').show();
    },function(){
        $('.users span').hide();
    })
})


