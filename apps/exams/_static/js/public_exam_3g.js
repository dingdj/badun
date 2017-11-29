$(function(){
    /*查看/收起解析*/
    $(".static-exam dl .operation strong").on("click",function(){
        var anhish = $(this).parent().siblings(".analysis").css("display");
        if(anhish=="none"){
            $(this).children("small").html("收起解析");
            $(this).children("i").css("transform","rotate(-180deg)");
            $(this).parent().siblings(".analysis").show();
        }else{
            $(this).children("small").html("查看解析");
            $(this).children("i").css("transform","rotate(0deg)");
            $(this).parent().siblings(".analysis").hide();
        }
    });

    /*收藏题目*/
    $(".static-exam dl .operation b").on("click",function(){
        var _this = $(this);
        var action = _this.data('action');
        var question_id = _this.data('question_id');
        // 后台
        $.post(U('exams/Index/collect'),{action:action,source_id:question_id},function(res){
            if(typeof(res) != 'object'){
                try{
                    var res = JSON.parse(res);
                }catch(e){
                    alert("处理异常,请重新尝试");
                    return false;
                }
            }

            if(res.status == 1){
                //ui.success(res.data.info);
                if(action == 1){
                    _this.find("i").css("color","#F7B659");
                    _this.find("small").html("已收藏");
                    _this.data('action',0);
                }else{
                    // 取消收藏
                    _this.find("i").css("color","#888");
                    _this.find("small").html("收藏题目");
                    _this.data('action',1);
                }
                return true;
            }else{
                alert(res.message);
                return false;
            }
        });
    });

    // 下一题
    $(".next_question,.go_question").click(function(){
        if($(this).hasClass("go_question")){
            var question_num = parseInt($(this).data("question_num"));
        }else{
            var question_num = parseInt($(this).data("question_num")) + 1;
        }
        $(".answer-card").hide();
        if($("#ex"+question_num).length > 0){
            $("#ex"+question_num).show().siblings().hide();
            
        }else{
            if(confirm("已经是最后一题了,是否返回第一题?")){
                $("#ex1").show().siblings().hide();
                
            }
        }
        if(question_num <= 1){
            $(".back-date i").hide();
        }else{
            $(".back-date i").show();
        }
        $(".static-exam,.footer").show();
    });
    /*多选选择*/
    $(".static-exam dl dd p").on("click",function(){
        var type = $(this).parents("dl").data("type");
        if($(this).hasClass("on")){
            $(this).removeClass("on");
            setSelectValue($(this));
            return true;
        }
        if(type == 'multiselect'){
            $(this).addClass("on");
        }else{
            $(this).addClass("on").siblings().removeClass("on");
        }
        setSelectValue($(this));
    });

    /** 返回前一题 **/
    $(".back-date i").click(function(){
        var question_num = $(".static-exam dl:visible").data("question_num") - 1;
        if(question_num <= 1){
            $("#ex1").show().siblings().hide();
            $(".back-date i").hide();
        }else{
            if($("#ex"+question_num).length > 0){
                $("#ex"+question_num).show().siblings().hide();
                $(".back-date i").show();
            }
        }
    });

    function setSelectValue(_this){
        var selectedObj = _this.parent().find("p.on");
        var value = '';
        selectedObj.each(function(){
            var answer = $(this).data("answer");
            value += answer+',';
        });
        value = value.substr(0,value.length-1);
        _this.parent().find(".user_answer_hidden input").val(value);
        var question_num = _this.parents("dl").data("question_num");
        if(value){
            $("#card"+question_num).addClass("on");
        }else{
            $("#card"+question_num).removeClass("on");
        }
    }

    // 显示答题面板
    $("#show-answer-card").click(function(){
        if($(".answer-card").is(":hidden")){
            $(".static-exam,.footer").hide();
            $(".answer-card").show();
        }else{
            $(".static-exam,.footer").show();
            $(".answer-card").hide();
        }
    });

    // 已做试题添加样式
    $(".static-exam dl .anserItem").die("click").on("click input propertychange",function(){
        var question_num = $(this).parents("dl").data("question_num");
        var user_answers = $(this).parents("dd").find(".anserItem");
        var is_addClass = 0;
        user_answers.each(function(){
            if($(this).val() != ''){
                $("#card"+question_num).addClass("on");
                is_addClass = 1;
                return false;
            }
        });
        if(is_addClass == 0){
            $("#card"+question_num).removeClass("on");
        }
        return true;
    });

    // 交卷
    $(".btns-complete .assignment").click(function(){
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
                    alert(res.data.info);
                    window.location.href = res.data.jumpurl;
                    return true;
                }else{
                    alert(res.message);
                    return false;
                }
            });
        }
    });

    // 计时器
    var time = parseInt($("#anser_time").val());
    var end_time = parseInt($("input[name='reply_time']").val()) * 60;
    var t = 0;// 计时器ID
    function startTime(){
        return setInterval(function(){
            // 考试时间
            if(end_time > 0 && (time >= end_time)){
                $("input[name='is_timeout']").val(1);
                ui.error('考试时间已到，系统自动交卷');
                setTimeout(function(){
                    $.post(U('exams/Index/doProgressExams'),$("form[name='answers']").serialize(),function(res){
                        if(typeof(res) != 'object'){
                            try{
                                var res = JSON.parse(res);
                            }catch(e){
                                alert("处理异常,请重新尝试");
                                return 0;
                            }
                        }

                        if(res.status == 1){
                            alert(res.data.info);
                            window.location.href = res.data.jumpurl;
                        }else{
                            alert(res.message);
                        }
                    });
                },1000);
                
                clearInterval(t);
                return 0;
            }
            var hours = Math.floor(time/3600);
            var minutes = Math.floor((time % 3600) / 60);
            var seconds = Math.round(time % 60);
            $("#time_hh").text(hours < 10 ? '0'+hours : hours);
            $("#time_mm").text(minutes < 10 ? '0'+minutes : minutes);
            $("#time_ss").text(seconds < 10 ? '0'+seconds : seconds);
            $("#anser_time").val(time);
            time++;
        },1000);
    }
    t = startTime();

    /*暂停*/
    $(".exam-header .view-stop .icon-zanting").on("click", function() {
        $(this).parent().siblings(".the-stop").show();
        $(".cover").show();
        clearInterval(t);
    });
    $(".exam-header .the-stop li").on("click",function(){
        $(this).parent().hide();
        $(".cover").hide();
        t = startTime();
    });
})