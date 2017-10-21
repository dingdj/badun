var CLICK_VERIFY="{:U('w3g/Passport/clickVerify')}";
var CLICK_UNAME="{:U('w3g/Passport/clickUname')}";
var CLICK_PHONE="{:U('w3g/Passport/clickPhone')}";
var CLICK_PHONEVER="{:U('w3g/Passport/clickPhoneVer')}";
var SETUSERFACE="{:U('w3g/Passport/setUserFace')}";
var GET_PHONEVERIFY="{:U('w3g/Passport/getVerify')}";
var REPOHNE_VAR="{:U('w3g/Passport/getVrifi')}";

//手机注册下一部
function reg_phone(){
	user=$.trim($("#prphone").val());//获取用户手机号
	verify=$.trim($("#prverify").val());//获取手机验证码
    password=$.trim($("#prpassword").val());//获取密码
  //检查密码
	if(password=="" ||password.length<6 || password.length>20){
		 alert('对不起，密码长度不正确!');
		 return;
	}
	//检查验证码
	if(verify=="" ||verify.length!=6){
		 alert('对不起，手机验证码长度不正确!');
		 return;
	}
	//检查手机号格式
	if(!user.match(/^1[3|4|5|7|8][0-9]\d{8}$/)){
		 alert('对不起，请填写正确的手机号!');
		 return;
	}else{
		//验证手机
	    $.ajax({
	        type: "POST",
	        url:CLICK_PHONEVER,
	        data:"phone="+user+"&verify="+verify,
	        dataType:"json",
	        success:function(data){
	        	 if(data.status=='0'){
	            	 alert(data.info);	
	            	 return;
	            }else{
	            	$.ajax({
				        type: "POST",
				        url:"{:U('w3g/Passport/ajaxReg')}",
				        data:"login="+user+"&password="+password+"&verify="+verify+"&type=2",
				        dataType:"text",
				        success:function(data){
				            if(isNaN(data)){
				                alert(data);
				            }else{
				            	alert(data);
				              window.location.href="{:U('w3g/Index/index')}";
				            }
				        }
				    });
	            }
	           
	        }
	    }); 
	}
}
function getPhoneVerify(){
        user=$.trim($("#prphone").val());//获取用户手机号
        //检查手机号格式
        if(!user.match(/^1[3|4|5|7|8][0-9]\d{8}$/)){
             alert('对不起，请填写正确的手机号!');
             return;
        }else{
            //验证此手机是否已被注册
            $.ajax({
                type: "POST",
                url:CLICK_PHONE,
                data:"phone="+user,
                dataType:"text",
                success:function(data){
                    if(data==0){
                         alert('对不起，此手机已被注册，请更换!');
                         return;
                    }else{
                    	alert(data);
                        phoneVerify();
                    }
                   
                }
            }); 
        }
        var phoneVerify=function(){
            //获取手机验证码
            $.ajax({
                type: "POST",
                url:GET_PHONEVERIFY,
                data:"phone="+user,
                dataType:"json",
                success:function(data){
                    if(data.status=='0'){
                        alert(data.info);
                        return;
                    }else{
                        alert(data.info);
                        $('.width80').css("display","none");
                        $('.width97').removeAttr("style");
                        timerc = 60;
                        dtime();
                        return;
                    }
                }
            }); 
        }
    }
    //临时处理方法
	var timerc; 
	function dctime(){
	    if(timerc > 1){ 
	        timerc=timerc-1; 
	        $("#dctime").text(timerc);
	        setTimeout("dctime()", 1000); //设置1000毫秒以后执行一次本函数
	    }else{
	        $('.width97').css("display","none");
	        $('.width80').removeAttr("style");
	    }
	}
	/**
 * 找回密码发送手机验证码
 */
