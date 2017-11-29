$(function(){
	function adco(){
		var i = $(".btn_je_box .year-display input").val();
		$(".btn_je_box .year-display .reduce").click(function(){
			i<=1? 1+1 : i--;
			/**$(".btn_je_box .year-display input.count").val(i);**/
			$("input[name='count']").val(i);
		})
		$(".btn_je_box .year-display .add").click(function(){
			i++;
			/**$(".btn_je_box .year-display input.count").val(i);**/
			$("input[name='count']").val(i);
		})
	}
	adco();

	/**$(".login-vip-box .vip-recharge").on("click",function(){
		$("#transparent").show();
		$(".continue-one").show();
	});

	$(".login-vip-box .vip-upgrade").on("click",function(){
		$("#transparent").show();
		$(".continue-two").show();
	});

	$(".vip-style li").on("click",function(){
		$("#transparent").show();
		$(".continue-two").show();
	});**/

	$(".card-main .classlist:first").show();
	$(".tle-all-card li:first").addClass("on");
	$(".tle-all-card li").on("mouseenter",function(){
		$(this).addClass("on").siblings().removeClass("on");
		$(this).parent().siblings().find(".classlist").hide();
		$(this).parent().siblings().find(".classlist").eq($(this).index()).show();
	});
})