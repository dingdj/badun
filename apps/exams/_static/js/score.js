$(function(){
    /*固定exercises-left*/
    $(window).scroll(function() {
        var win_top =  $(document).scrollTop();
        if (win_top<530){
            $(".score-left").css({"position":"static","margin-top":"0"})
        }
        if(win_top>530){
            $(".score-left").css({"position":"fixed","top":"20px","bottom":"auto"})
        }
        if (win_top> $(document).height() - $(window).height()-280) {
            $(".score-left").css({"position":"fixed","bottom":"280px","top":"auto"})
        }
    });

    // 只看错题
    $("#onlyShowWrong").click(function(){
        if($(this).is(":checked")){
            $(".test-paper").hide();
            $(".wrong").show();
            $(".error-exam dd a").hide();
            $(".error-exam dd a.on").show();
        }else{
            $(".test-paper").show();
            $(".error-exam dd a").show();
        }
    });
})