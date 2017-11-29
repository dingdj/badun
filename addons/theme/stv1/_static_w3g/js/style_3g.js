$(function(){
    $(".df_pay-style dd:last-child").children("i").css("border","1px solid #ededed");
    $(".df_pay-style dd").on("click",function(){
        $(this).children("i").attr("class","icon icon-xuanze").css("border","1px solid transparent").parent().siblings().children("i").removeAttr("class","icon icon-xuanze").css("border","1px solid #ededed");
    });

    $(".df_pay-protocol i").on("click",function(){
        var proVal = $(this).attr("attr");
        console.log(proVal);
       if(proVal==1){
           $(this).attr("attr","0").css("color","#656565");
       }else{
           $(this).attr("attr","1").css("color","#57BC4C")
       }
    });
})