$(function(){
    /*错题页面收藏*/
    $(".exam-header .view-stop .icon-shoucang4").on("click",function(){
        var iconCls = $(this).attr("class");
        if(iconCls=="icon icon-shoucang4"){
            $(this).attr("class","icon icon-shoucang2");
        }else {
            $(this).attr("class","icon icon-shoucang4");
        }
    });
})