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
});