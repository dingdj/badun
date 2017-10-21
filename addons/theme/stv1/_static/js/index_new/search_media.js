function shap(){
    var put_Val = $("#search_combo_input").val();
	   if(put_Val.length<1){
	   	$(".icon-quxiao").hide();
	   	$(".goods-eac-box button").css("color","#888");
	   }else if(put_Val.length>=1){
	   		$(".icon-quxiao").show();
	   		$(".goods-eac-box button").css("color","#00BED4");
	   }
	}

$(function(){
	$('.icon-quxiao').on("click",function(){
		$(".eac-per-ens input").val("");
		$(".eac-per-ens input").focus();
		$(".goods-eac-box button").css("color","#888");
		$(this).hide();
	});

	$(".aingstoit span a:first").hide();
	$(".aingstoit span a").on("click",function(){
		var itp_Val = $(".aingstoit p").text();
		var span_Val = $(this).text();
        var a_Attr = $(this).attr("attr");
		$('#search_cate').val(a_Attr);
		$(".aingstoit p").html(span_Val);
        $(".aingstoit p").attr("attr",a_Attr);
		$(this).hide().siblings().show();
        if(a_Attr == 'video'){
            $("#video_list").show().siblings('ul').hide();
        }else if(a_Attr == 'teacher'){
            $("#teacher_list").show().siblings('ul').hide();
        }else if(a_Attr == 'school'){
            $("#school_list").show().siblings('ul').hide();
        }
	})
});