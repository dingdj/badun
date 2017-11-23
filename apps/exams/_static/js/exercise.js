$(function(){
    /*固定exercises-left*/
    $(document).ready(function() {
        $(window).scroll(function() {
            var win_top =  $(document).scrollTop();
            if (win_top<256){
                $(".exercises-left").css({"position":"static","margin-top":"0"})
            }
            if(win_top>256){
                $(".exercises-left").css({"position":"fixed","top":"20px"})
            }
            /*if(win_top> $(document).height() - $(window).height()-70) {
                $(".exercises-left").css({"position":"fixed","bottom":"280px","top":"auto"})
            }*/
        });
    });


	function sheet(){
		var shKey = $(".answer-sheet dd");
		for(var i=0;i<shKey.length;i++){
			if((i+1)%4==0){
				$(".answer-sheet dd").eq(i).css("border-right","0");
			}
		}
	}
	sheet()
	/*取消选中*/
	// $(".test-paper .choice li label").on("click",function(){
	// 	$(this).find("input").attr("checked",true);
	// });
	// $(".test-paper .choice li label").on("dblclick",function(){
	// 	$(this).find("input").removeAttr("checked",false);
	// });

	/*收藏题目*/
	$(".test-paper .choice .like").on("click",function(){
        var _this = $(this);
		var action = _this.data('action');
        var question_id = _this.data('question_id');
        // 后台
        $.post(U('exams/Index/collect'),{action:action,source_id:question_id},function(res){
            if(typeof(res) != 'object'){
                try{
                    var res = JSON.parse(res);
                }catch(e){
                    ui.error("处理异常,请重新尝试");
                    return false;
                }
            }

            if(res.status == 1){
                //ui.success(res.data.info);
                if(action == 1){
                    _this.find("i").css("background-position","-88px -8px");
                    _this.find("small").html("已收藏");
                    _this.data('action',0);
                }else{
                    // 取消收藏
                    _this.find("i").css("background-position","-65px -8px");
                    _this.find("small").html("收藏题目");
                    _this.data('action',1);
                }
                return true;
            }else{
                ui.error(res.message);
                return false;
            }
        });
	})

	/*查看解析*/
	$(".test-paper .choice .look").on("click",function(){
		var my_Str = $(this).find("small").html();
		if(my_Str=="查看解析"){
			$(this).find("small").html("收起解析")
			$(this).find("i").css("transform","rotate(0deg)");
		}else{
			$(this).find("small").html("查看解析")
			$(this).find("i").css("transform","rotate(-180deg)");
		}
		$(this).parent().parent().siblings(".lu-ms-tim,.fz").toggle();
	});

	// 模式更换
	$("#single_mod").click(function(){
        if($(this).is(":checked")){
            // 获取锚点
            var hash = window.location.hash;
            if(!hash){
                hash = '#ex1';
            }
            $(".test-paper").not($(hash)).hide();
            $(window).scrollTop($("header").height());
            $(".next-exercises").show();
        }else{
            $(".test-paper").show();
            $(".next-exercises").hide();
        }
    });

    // 下一题
    $(".next-exercises a").click(function(){
        // 获取当前显示的试题
        var question_num = $(".test-paper:visible").data("question-num")+1;
        if($("#ex"+question_num).length > 0){
            $(".test-paper").hide();
            $("#ex"+question_num).show();
        }else{
            if(confirm("已经是最后一题了,是否返回第一题?")){
                $(".test-paper").hide();
                $("#ex1").show();
            }
        }
    });

    // 提交试卷
    $("#submitExams").click(function(){
        if(confirm('确认交卷吗?')){
            $("form[name='answers']").submit();
        }
    });

    // 下次再做
    $("#progressExams").click(function(){
        if(confirm('本次提交将保存进度,是否确认提交?')){
            $.post(U('exams/Index/doProgressExams'),$("form[name='answers']").serialize(),function(res){
                if(typeof(res) != 'object'){
                    try{
                        var res = JSON.parse(res);
                    }catch(e){
                        ui.error("处理异常,请重新尝试");
                        return false;
                    }
                }

                if(res.status == 1){
                    ui.success(res.data.info);
                    setTimeout(function(){
                        window.location.href = res.data.jumpurl;
                    },1500)
                    return true;
                }else{
                    ui.error(res.message);
                    return false;
                }
            });
        }
    });

    // 计时器
    var time = parseInt($("#anser_time").val());
    var ss = time % 60;
    var mm = parseInt(time / 60);
    var end_time = parseInt($("input[name='reply_time']").val()) * 60;
    $("#time_mm").text(mm);
    $("#time_ss").text(ss);
    var t = 0;// 计时器ID
    function startTime(){
        return setInterval(function(){
            // 考试时间
            if(end_time > 0 && (mm * 60 + ss >= end_time)){
                $("input[name='is_timeout']").val(1);
                ui.error('考试时间已到，系统自动交卷');
                setTimeout(function(){
                    $.post(U('exams/Index/doProgressExams'),$("form[name='answers']").serialize(),function(res){
                        if(typeof(res) != 'object'){
                            try{
                                var res = JSON.parse(res);
                            }catch(e){
                                ui.error("处理异常,请重新尝试");
                                return 0;
                            }
                        }

                        if(res.status == 1){
                            ui.success(res.data.info);
                            setTimeout(function(){
                                window.location.href = res.data.jumpurl;
                            },1500);
                        }else{
                            ui.error(res.message);
                        }
                    });
                },1000);
                
                clearInterval(t);
                return 0;
            }
            if(ss == 59){
                mm++;
                ss = 0;
                $("#time_mm").text(mm);
                
            }else{
                ss++;
            }
            time++;
            $("#anser_time").val(time);
            $("#time_ss").text(ss);
        },1000);
    }
    t = startTime();

    // 暂停与启动
    $("#stopTime").click(function(){
    	if($("#loging-back").length == 0){
			var html = '<div id="loging-back"></div>';
				html += '<dl class="rest-worap">';
				html += '<dt><h4>暂停</h4><i>×</i></dt>';
				html += '<dd><div class="rest-img-dem"></div><a href="javascript:(0);">继续答题</a></dd></dl>';
			$("body").children(":first").before(html);
		}
		$("#loging-back").show();
		$(".rest-worap").show();
		clearInterval(t);
		$("#stopTime").text('已暂停');
    });
    $('.rest-worap i,.rest-worap a').live("click",function(){
		$("#loging-back").hide();
		$(".rest-worap").hide()
		t = startTime();
		$("#stopTime").text('暂停');
	});

    // 已做试题添加样式
    $(".test-paper-box li.test-paper .anserItem").die("click").on("click input propertychange",function(){
        var _this = $(this);
        var user_answers = _this.parents(".choice").find(".anserItem");
        var question_item = _this.parents(".test-paper").attr("id");
        var is_addClass = 0;
        user_answers.each(function(){
            if(_this.attr("type") == 'checkbox' && $(this).is(":checked")){
                $(".answer-sheet a[href='#"+question_item+"']").parent().addClass("on");
                is_addClass = 1;
                return false;
                
            }
            if(_this.attr("type") != 'checkbox' && $(this).val() != ''){
                $(".answer-sheet a[href='#"+question_item+"']").parent().addClass("on");
                is_addClass = 1;
                // 如果为单选题,跳转到下一题
                if($(this).attr("type") == 'radio'){
                    window.location.hash = '#ex'+(_this.parents(".test-paper").data("question-num")+1);
                }
                return false;
            }
        });
        if(is_addClass == 0){
            $(".answer-sheet a[href='#"+question_item+"']").parent().removeClass("on");
        }
        return true;
    });
})