$(function(){
	$(".course-deta-service span i:first").on("click",function(){
		var rest = $(this).css("color","#fb8484");
		var restVal = $(this).prop("class","icon icon-shoucang1");
		if(rest==true && restVal==true){
			$(this).css("color","#656565")
			$(this).prop("class","icon icon-shoucang");
		}
	});

	/*课程详情 && 课程评价*/
	$('.course-evaluate-head li:first').css({"color":"#00BED4","border-color":"#00BED4"});
	$(".course-evaluate-content .somle:first").show();
	$('.course-evaluate-head li').on("click",function(){
		$(this).css({"color":"#00BED4","border-color":"#00BED4"}).siblings().css({"color":"#888","border-color":"transparent"});
		var couThis = $(this).index();
		$(".course-evaluate-content .somle").hide();
		$(".course-evaluate-content .somle").eq(couThis).show(100);
	});

	/*显示回复*/
	$(".somle-head-txt .em01").on("click",function(){
		$(this).parent().parent().parent().find("ul").toggle();
	});

	/*点击回复*/
	$(".somle-head-txt strong").on("click",function(){
		$("body").css("overflow","hidden");
		$(".cover").show();
		$(".com-star").hide();
		$(".text-speak").show();
		var txt = $(this).parent().parent().find("h1").text();
		$(".text-speak textarea").attr("placeholder","回复 "+ txt);
		$(".text-speak textarea").focus();
	});

	/*评论*/
	$(".somle .somle-head .comment-box").on("click",function(){
		$("body").css("overflow","hidden");
		$(".cover").show();
		$(".com-star").show();
		$(".text-speak").show();
		$(".text-speak textarea").attr("placeholder","说点什么... ");
		$(".text-speak textarea").focus();
	});

	$(".cover").on("click",function () {
		$("body").css("overflow","auto");
		$(".cover").hide();
		$(".text-speak").hide();
	});

	/*评论星级*/
	$(".com-star i").on("click",function(){
		var i_Index = $(".com-star i");
		for(var i=0;i<i_Index.length;i++){
			var j = $(this).index()+1;
			console.log(j)
			for(var q=0;q<j;q++){
				if(i<=q){
					$(".com-star i").eq(q).css("color","#FF6813");
					$(".com-star small").html("（" + j+".0分）");
				}else{
					$(".com-star i").eq(i).css("color","#ccc");
				}
			}
		}
	});



	/*点赞*/
	$(".somle-head-txt .em02").on("click",function(){
		$(this).children("i").attr("class","icon icon-dianzan1").css("color","#00BED4")
	});

})

function keys(){
    var put_Val = $(".text-speak textarea").val();
   if(put_Val.length<1){
   	$(".text-speak button").css("color","#888");
   }else if(put_Val.length>=1){
   		$(".text-speak button").css("color","#00BED4");
   }
}