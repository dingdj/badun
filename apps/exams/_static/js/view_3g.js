$(function(){
    /*隐藏/显示筛选*/
    $(".selectul li").click(function(){
        var i = $(this).index();
        var shHi = $(".selectul-screen-content .selectul-layer").eq(i).is(":hidden");
        if(shHi){
            $(".selectul li").css("color","#333");
            $(this).css("color","#00BED4");
            $(".selectul-screen").css("z-index","100");
            $('.selectul li i').css("transform","rotate(0deg)");
            $(this).children("i").css("transform","rotate(180deg)")
            $(".selectul-screen-content .selectul-layer").hide();
            $(".cover").show();
            $(".cover").css("top","58px");
            $(".selectul-screen-content .selectul-layer").eq(i).show();
        }else{
            $(".selectul-screen").css("z-index","0");
            $(this).css("color","#333");
            $(this).children("i").css("transform","rotate(0deg)");
            $(".selectul-screen-content .selectul-layer").eq(i).hide();
            $(".cover").css("top","0px");
            $(".cover").hide();
        }
    });

    /*隐藏弹出框*/
    $(".cover").on("click",function(){
        $(this).hide();
        $(".cover").css("top","0px");
        var index = $(".selectul-screen-content .selectul-layer:visible").index();
        if(index == 0){
            $("#search_by_cid").click();
        }
        $(".selectul-screen-content .selectul-layer").hide();
        $(".selectul li").css("color","#333");
        $(".selectul li i").css("transform","rotate(0deg)");
        $(".selectul-screen").css("z-index","0");
        $(".pattern-worap").hide();
    });

    // 选择专业
    $(".three-selectul li").click(function(){
        // 当前选中
        $(this).addClass("on").siblings("li").removeClass("on");
        var id = $(this).data('cid');
        // 锁定当前点击的列表
        $(this).parents(".three-selectul").addClass("locked");
        $(this).parents(".three-selectul").nextAll(".three-selectul").removeClass("locked");
        $(this).siblings().each(function(){
            var cid = $(this).data('cid');
            if(cid == 0){
                return false;
            }
            $(".selectul-"+cid).removeClass("locked");
            $(".selectul-"+cid).find("li").first().addClass("on").siblings().removeClass("on");
        });
        $(this).parents(".three-selectul").siblings(".three-selectul").not($(".locked")).hide();
        
        $(".selectul-"+id).show();
    });

    /*选择习题模式*/
    $(".exam-subject li").on("click",function(){
        if(!MID){
            if(confirm("请先登录")){
                window.location.href = U('public/Passport/login_g');
            }
            return false;
        }
        var html = $(this).find("dl").html();
        $(".pattern-worap").html(html);
        $(".cover,.pattern-worap").show();

    });
    // 选择条件
    $(".date-condition .box button").click(function(){
        var type = $(this).data("type");
        $(".date-condition .box button[data-type='"+type+"']").removeClass("selected");
        $(this).addClass("selected");
    })
    // 重置条件
    $("#reset_search").click(function(){
        $(".date-condition .box button").removeClass("selected");
    });
    
})