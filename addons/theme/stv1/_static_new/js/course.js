$(function(){

	$(".school-choice .region").on("click",function(){
		$(this).children("ul").toggle(200);
	});

	/*专业分类*/
	$(".fi-look-date .region ul li").on("click",function(){
		$(this).addClass("regin-on").siblings().removeClass("regin-on");
		var chol_Val = $(this).children("a").attr("attr");
		var chol_txt = $(this).children("a").text();
		$(this).parent().parent().parent().parent().find("p").text(chol_txt);
		$(this).parent().parent().parent().parent().find("p").attr("attr",chol_Val);
	});

	/*开班时间*/
	$(".date-choice .region ul li").on("click",function(){
		var dur = new Date();
		var phmou = dur.getMonth();
		var moIndex = $(".date-choice .start li");
		for(var i=0;i<moIndex.length;i++){
			var curre = $(this).index();
			console.log(phmou)
			if(curre<phmou){
				$(this).off("click")
			}
		}
		$(this).addClass("regin-on").siblings().removeClass("regin-on");
		var chol_Val = $(this).children("a").attr("attr");
		var chol_txt = $(this).children("a").text();
		$(this).parent().siblings().find("p").text(chol_txt);
		$(this).parent().siblings().find("p").attr("attr",chol_Val);
	})
})