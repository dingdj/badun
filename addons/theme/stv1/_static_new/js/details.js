$(function(){
	/*课程详情的操作*/
	$(".head-li-per li:first").addClass("on")
	$(".head-li-per li").on("click",function(){
		$(this).addClass("on").siblings().removeClass("on");
		var tit_Index =  $(this).index();
		$(".course-contents .course-infos").hide();
		$(".course-contents .course-infos").eq(tit_Index).show();
	});

	/*我写评论*/
	$(".title a").on("click",function(){
		$(this).parent().siblings().find("#review-comments").toggle();
	})

	/*评论星级*/
	$(".star_ul i").on("mouseover",function(){
		var i_Index = $(".star_ul i");
		for(var i=0;i<i_Index.length;i++){
			var j = $(this).index()+1;
			for(var q=0;q<j;q++){
				if(i<=q){
					$(".star_ul i").eq(q).css("color","#FF6813");
				}else{
					$(".star_ul i").eq(i).css("color","#ccc");
				}
			}
		}
	});

	/*查看别人对别人的评论*/
	$(".qa-total-reply .js-qa-tr-num").on("click",function(){
		alert("sss");
		var s = $(this).parent().parent().parent().parent().parent().parent().parent().parent().next(".qa-replies")
		console.log(s)
		$(this).parent().parent().parent().parent().parent().parent().parent().parent().next(".qa-replies").show();
		$(this).hide();
		$(".qa-total-reply .qa-tr-up ").show();
	});

	/*影藏别人对别人的评论*/
	$(".qa-total-reply .qa-tr-up").on("click",function(){
		$(this).parent().parent().parent().parent().parent().parent().parent().parent().siblings(".qa-replies").hide();
		$(this).hide();
		$(".qa-total-reply .js-qa-tr-num").show();
	})

	/*显示回复评论框*/
	$(".qa-reply-item-reply").on("click",function(){
		$(".qa-reply-ibox").show();
		var txtKey = $(this).parent().siblings().find(".qa-reply-nick").text();
		$(".qa-reply-iwrap .qa-reply-iarea textarea").attr("placeholder","@"+txtKey);
	});

	$(".qa-reply-ibox .btn-mc-light").on("click",function(){
		$(".qa-reply-ibox").hide()
	});

	/*点赞*/
	$(".js-qacom-supported-user").on("click",function(){
		$(this).find("i").attr("class","icon icon-dianzan1").css("color","#00BED4");
		var qx = $(this).find(".t_num").text();
	});
})