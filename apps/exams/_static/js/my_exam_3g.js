$(function(){
    $(".suren-coas li:first-child").addClass("on");
    $(".suren-coas li").on("click",function(){
        $(this).addClass("on").siblings().removeClass("on");
        var i = $(this).index();
        $(".asmarfr-pr .suren-content").hide();
        $(".asmarfr-pr .suren-content").eq(i).show();
    });

    var timeout;

    $(".suren-content li, .eaxm-record li").mouseover(function() {
        timeout = setTimeout(function() {
            $(".cover,.delete").show();
        }, 400);
    });

    $(".delete .c01").on("click",function(){
        $(".cover,.delete").hide();
    });
})
