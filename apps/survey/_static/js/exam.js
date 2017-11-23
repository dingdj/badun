

function checkForm(){
    if(confirm("你确定要提交试卷?")){
      return true;
    }
  }
  function j_validateCallback(form,call,callback) {
    var $form = $(form);
    if(typeof call != 'undefined' && call instanceof Function){    
      $i = call($form);
      if(!$i){
        return false;
      }
    }
    var _submitFn = function(){
      $.ajax({
        type: form.method || 'POST',
        url:$form.attr("action"),
        data:$form.serializeArray(),
        dataType:"json",
        cache: false,
        success: function(xMLHttpRequest, textStatus, errorThrown){
          if(typeof callback != 'undefined' && callback instanceof Function){   
            callback($form,xMLHttpRequest);
          }  
        },
        error: function(xhr, ajaxOptions, thrownError){
          ui.error("未知错误!");
        }
      });
    }
    _submitFn();
    return false;
  }
  function post_callback(_form,data){
    if(data.status != undefined){
      if(data.status == '0'){
        ui.error(data.info);
      } else {
        $("#bg").css("padding-top",document.body.offsetHeight);
          $("#bg").show();
          $("#show").show();
          $("#divbox1").show();
          $("#result").html("是否查看调查结果?");
      }
    }
  }