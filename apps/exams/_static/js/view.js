$(function(){
	$(".card-main .classlist li").on('click',function(){
		if(MID <= 0){
			reg_login();
			return true;
		}
		if($("#model-back").length == 0){
			var html = '<div id="model-back"></div>';
				html += '<dl class="pattern-worap">';
				html += '<dt><h4>选择模式</h4><i>×</i></dt>';
				if($(this).data('is_practice') == 1){
					html += '<dd><a class="join-type" href="javascript:(0);">练习模式</a><a class="join-type" href="javascript:(0);">考试模式</a></dd></dl>';
				}else{
					html += '<dd><a class="join-type" href="javascript:(0);">考试模式</a></dd></dl>';
				}
			$("body").children(":first").before(html);
		}
		$("#model-back").show();
		$(".pattern-worap").show();
		// 获取参数
		var paper_id = $(this).data("paper_id");
		if($(".join-type").length > 1){
			$(".join-type:eq(0)").attr('href',U("exams/Index/examsroom",['paper_id='+paper_id,'joinType=1']));
			$(".join-type:eq(1)").attr('href',U("exams/Index/examsroom",['paper_id='+paper_id,'joinType=2']));
		}else{
			$(".join-type:eq(0)").attr('href',U("exams/Index/examsroom",['paper_id='+paper_id,'joinType=2']));
		}
	});
	$('.pattern-worap i').live("click",function(){
		$("#model-back").hide();
		$(".pattern-worap").hide()
	});
})