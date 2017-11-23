$(function(){
	/*$(".nav-screen-all div").click(function(){
		$(this).css("color","#00BED4").siblings().css("color","#333");
		$(this).children("i").css("transform","rotate(180deg)").parent().siblings().children("i").css("transform","rotate(0deg)");
        var i = $(this).index();
        $(".one-nav-layer").hide();
        if($(".one-nav-layer").eq(i).css("display") == 'none'){
            $(".one-nav-layer").eq(i).show();
            $(".nav-screen .cover").show();
        }else{
            $(".nav-screen .cover").hide();
        }
	});*/

    $(".nav-screen-all div").click(function(){
        var i = $(this).index();
        var shHi = $(".one-nav-layer").eq(i).css('display');
        if(shHi=="none"){
            $(".nav-screen-head").css("color","#333");
            $('.nav-screen-head i').css("transform","rotate(0deg)");
            $(this).css("color","#00BED4");
            $(this).children("i").css("transform","rotate(180deg)")
            $(".one-nav-layer").hide();
            $(".nav-screen .cover").show();
            $(".one-nav-layer").eq(i).show();
        }else{
            $(this).css("color","#333");
            $(this).children("i").css("transform","rotate(0deg)");
            $(".one-nav-layer").eq(i).hide();
            $(".nav-screen .cover").hide();
        }
    });


    $(".nav-screen .cover").on("click",function(){
		$(this).hide();
		$(".one-nav-layer").hide();
		$(".nav-screen-all div").css("color","#333");
		$(".nav-screen-all div i").css("transform","rotate(0deg)");
	});

	/*一级*/
	/*$(".one-on").parent().parent().siblings().find(".one-on:first").css({"border-bottom":"1px solid #00BED4","color":"#00BED4","background-color":"#f9f9f9"});
	$(".one-on").on("click",function(){
		$(".one-on").css({"color":"#757575","border-bottom":"1px solid transparent","background-color":"#fff"});
		$(this).css({"border-bottom":"1px solid #00BED4","color":"#00BED4","background-color":"#f9f9f9"});
		$(".two-on").parent().parent().parent().siblings().find(".two-on:first").css({"color":"#00BED4","background-color":"#f4f4f4"})
	});*/

	/*二级*/
	/*$(".two-on").parent().parent().parent().siblings().find(".two-on:first").css({"color":"#00BED4","background-color":"#f4f4f4"})
	$(".two-on").on("click",function(){
		$(".two-on").css({"color":"#757575","background-color":"transparent"});
		$(this).css({"color":"#00BED4","background-color":"#f4f4f4"})
		$(".three-on").parent().parent().parent().siblings().find(".three-on:first").css({"color":"#00BED4"})
	});*/

	/*三级*/
	/*$(".three-on").parent().parent().parent().siblings().find(".three-on:first").css({"color":"#00BED4"})
	$(".three-on").on("click",function(){
		$(".three-on").css({"color":"#757575"});
		$(this).css({"color":"#00BED4"})
	});*/
})