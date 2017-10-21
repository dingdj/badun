// JavaScript Document
// 检查文章表单提交
admin.checklzarticleInfo = function(form) {
	if(form.title.value.replace(/^ +| +$/g,'')==''){
		ui.error('文章标题不能为空!');
		return false;
	}
	if(form.description.value.replace(/^ +| +$/g,'')==''){
		ui.error('文章描述不能为空!');
		return false;
	}
	if(form.source.value.replace(/^ +| +$/g,'')==''){
		ui.error('文章来源不能为空!');
		return false;
	}
	return true;
};
// 检查连载内容表单提交
var checklzindexInfo = function(form) {
	if(form.find('select[name="title"]:selected').val() <= 0){
		ui.error('连载分期不能为空!');
		return false;
	}
	if(form.find('input[name="title"]').val().replace(/^ +| +$/g,'')==''){
		ui.error('连载标题不能为空!');
		return false;
	}
	if(form.find('textarea[name="description"]').val().replace(/^ +| +$/g,'')==''){
		ui.error('连载描述不能为空!');
		return false;
	}
	if(form.find('input[name="source"]').val().replace(/^ +| +$/g,'')==''){
		ui.error('连载来源不能为空!');
		return false;
	}
	return true;
};
// 检查分期表单提交
admin.checkxdateInfo = function(form,$type) {
	if($type == 'add' && form.cid.value <= 0){
		ui.error('连载栏目不能为空!');
		return false;
	}
	if(form.name.value.replace(/^ +| +$/g,'')==''){
		ui.error('分期名称不能为空!');
		return false;
	}
	return true;
};
// 检查专题分类表单提交
admin.checkCategoryInfo= function(form) {
	if(form.title.value.replace(/^ +| +$/g,'')==''){
		ui.error('专题分类标题不能为空!');
		return false;
	}
	if(form.templet.value.replace(/^ +| +$/g,'')==''){
		ui.error('模板名称不能为空!');
		return false;
	}else{
		//只能输入英文
		var reg = /^[a-zA-Z0-9]{1,}$/;     
        var r = form.templet.value.match(reg);     
        if(r==null){  
            ui.error('模板名称可以是英文或者数字!');
			return false;
        }   
	}
	return true;
};
//检查讲师表单提交
admin.checkTeacher=function(form) {
	if(form.name.value.replace(/^ +| +$/g,'')==''){
		ui.error('讲师姓名不能为空!');
		return false;
	}
	if(form.inro.value.replace(/^ +| +$/g,'')==''){
		ui.error('讲师简介不能为空!');
		return false;
	}
	if(form.title.value.replace(/^ +| +$/g,'')==''){
		ui.error('讲师职称不能为空!');
		return false;
	}
	if(form.head_id.value.replace(/^ +| +$/g,'')==''){
		ui.error('请上传讲师照片!');
		return false;
	}
	return true;
};
//检查文库表单提交
admin.checkLibrary=function(form) {
	if(form.title.value.replace(/^ +| +$/g,'')==''){
		ui.error('文库名称不能为空!');
		return false;
	}
	if($('.mzTopLevel option:selected').val() <= 0){
		ui.error('请选择文库分类!');
		return false;
	}
	if(form.info.value.replace(/^ +| +$/g,'')==''){
		ui.error('文库信息不能为空!');
		return false;
	}
	if(form.price.value.replace(/^ +| +$/g,'')==''){
		ui.error('文库价格不能为空!');
		return false;
	}
	if(isNaN(form.price.value)){
		ui.error('文库价格必须为数字!');
		return false;
	}

	return true;
};

// 检查专题表单提交
admin.checkSpecialInfo = function(form) {
	if(form.sc_id.value <= 0){
		ui.error('专题分类不能为空!');
		return false;
	}
	if(form.title.value.replace(/^ +| +$/g,'')==''){
		ui.error('专题名称不能为空!');
		return false;
	}
	if(form.foldername.value.replace(/^ +| +$/g,'')==''){
		ui.error('文件夹不能为空!');
		return false;
	}else{
		//只能输入英文
		var reg = /^[a-zA-Z0-9]{1,}$/;     
        var r = form.foldername.value.match(reg);     
        if(r==null){  
            ui.error('文件夹名可以是英文或者数字!');
			return false;
        }   
	}
	return true;
};


//处理银行卡
admin.BankCardEdit = function(_id,action,title,type){
	var id = ("undefined"== typeof(_id)|| _id=='') ? admin.getChecked() : _id;
    if(id==''){
        ui.error(L('PUBLIC_SELECT_TITLE_TYPE',{'title':title,'type':type}));
        return false;
   }
   if(confirm(L('PUBLIC_CONFIRM_DO',{'title':title,'type':type}))){
	   $.post(U('classroom/AdminCard/'+action),{id:id},function(msg){
			admin.ajaxReload(msg);
  	 },'json');
   }	
};

//处理笔记
admin.mzNoteEdit = function(_id,action,title,type){
	var id = ("undefined"== typeof(_id)|| _id=='') ? admin.getChecked() : _id;
	   $.post(U('classroom/AdminNote/'+action),{id:id},function(msg){
			admin.ajaxReload(msg);
  	 },'json');
};

//处理学习记录
admin.mzLearnEdit = function(_id,action,title,type){
	var id = ("undefined"== typeof(_id)|| _id=='') ? admin.getChecked() : _id;
    if(id==''){
        ui.error(L('PUBLIC_SELECT_TITLE_TYPE',{'title':title,'type':type}));
        return false;
   }
   if(confirm(L('PUBLIC_CONFIRM_DO',{'title':title,'type':type}))){
	   $.post(U('classroom/AdminVideo/'+action),{id:id},function(msg){
			admin.ajaxReload(msg);
  	 },'json');
   }	
};

////处理笔记
admin.delNoteEdit=function(_id,action){
	var id = ("undefined"== typeof(_id)|| _id=='') ? admin.getChecked() : _id;
	if(id==''){
		ui.error("问答id不能为null");
		return false;
	}
	if(!confirm("是否确认删除此笔记？")){
		return false;
	}
	$.post(U('classroom/AdminNote/'+action),{ids:id},function(msg){
		admin.ajaxReload(msg);
	},'json');
}

//处理所有笔记
admin.delNoteAllEdit = function(action){

	var ids=admin.getChecked();
	ids = ("undefined"== typeof(ids)|| ids=='') ? admin.getChecked() : ids;
	if(ids==''){
		ui.error("问答id不能为null");
		return false;
	}
	if(!confirm("是否确认删除此笔记？")){
		return false;
	}
	$.post(U('classroom/AdminNote/'+action),{ids:ids},function(msg){
		admin.ajaxReload(msg);
	},'json');
};


//处理评论
admin.mzNotecomment = function(_id,action,title,type){
	var id = ("undefined"== typeof(_id)|| _id=='') ? admin.getChecked() : _id;
	if(id==''){
		ui.error(L('PUBLIC_SELECT_TITLE_TYPE',{'title':title,'type':type}));
		return false;
	}
	if(confirm(L('是否确认删除此评论',{'title':title,'type':type}))){
		$.post(U('classroom/AdminNote/'+action),{id:id},function(msg){
			admin.ajaxReload(msg);
		},'json');
	}
};

//无对话框处理
admin.mzNotecomment = function(_id,action,title,type){
	var id = ("undefined"== typeof(_id)|| _id=='') ? admin.getChecked() : _id;
	if(id==''){
		ui.error(L('PUBLIC_SELECT_TITLE_TYPE',{'title':title,'type':type}));
		return false;
	}
		$.post(U('classroom/AdminNote/'+action),{id:id},function(msg){
			admin.ajaxReload(msg);
		},'json');
};


//处理提问
admin.mzQuestionEdit = function(action){
	var ids=admin.getChecked();
	ids = ("undefined"== typeof(ids)|| ids=='') ? admin.getChecked() : ids;
	if(ids==''){
		ui.error("问答id不能为null");
		return false;
	}
	if(!confirm("是否确认删除此提问？")){
		return false;
	}
	$.post(U('classroom/AdminQuestion/'+action),{ids:ids},function(msg){
		admin.ajaxReload(msg);
	},'json');
};

admin.mzdelquest=function(_id,action){
	var id = ("undefined"== typeof(_id)|| _id=='') ? admin.getChecked() : _id;
	if(id==''){
		ui.error("问答id不能为null");
		return false;
	}
	if(!confirm("是否确认删除此提问？")){
		return false;
	}
	$.post(U('classroom/AdminQuestion/'+action),{ids:id},function(msg){
		admin.ajaxReload(msg);
	},'json');
}


//处理点评
admin.mzReviewEdit = function(_id,action,title,type){
	var id = ("undefined"== typeof(_id)|| _id=='') ? admin.getChecked() : _id;
    if(id==''){
        ui.error(L('PUBLIC_SELECT_TITLE_TYPE',{'title':title,'type':type}));
        return false;
   }
	   $.post(U('classroom/AdminReview/'+action),{id:id},function(msg){
			admin.ajaxReload(msg);
  	 },'json');
};

//处理课程卡
admin.mzVideoCardEdit = function(_id,action,title,type){
	var id = ("undefined"== typeof(_id)|| _id=='') ? admin.getChecked() : _id;
    if(id==''){
        ui.error(L('PUBLIC_SELECT_TITLE_TYPE',{'title':title,'type':type}));
        return false;
   }
   if(confirm(L('PUBLIC_CONFIRM_DO',{'title':title,'type':type}))){
	   $.post(U('classroom/AdminVideoCard/'+action),{id:id},function(msg){
			admin.ajaxReload(msg);
  	 },'json');
   }	
};

//处理讲师
admin.mzTeacherEdit = function(_id,action,title,type,category){
	var id = ("undefined"== typeof(_id)|| _id=='') ? admin.getChecked() : _id;
    if(id==''){
        ui.error(L('PUBLIC_SELECT_TITLE_TYPE',{'title':title,'type':type}));
        return false;
   }
   if(confirm(L('PUBLIC_CONFIRM_DO',{'title':title,'type':type}))){
	   $.post(U('school/AdminTeacher/'+action),{id:id,category:category},function(msg){
			admin.ajaxReload(msg);
  	 },'json');
   }	
};
//处理专题
admin.mzSpecialEdit = function(_id,action,title,type){
	var id = ("undefined"== typeof(_id)|| _id=='') ? admin.getChecked() : _id;
    if(id==''){
        ui.error(L('PUBLIC_SELECT_TITLE_TYPE',{'title':title,'type':type}));
        return false;
   }
   if(confirm(L('PUBLIC_CONFIRM_DO',{'title':title,'type':type}))){
	   $.post(U('classroom/AdminSpecial/'+action),{id:id},function(msg){
			admin.ajaxReload(msg);
  	 },'json');
   }	
};
//专题添加封面
admin.mzSpecialAddCover = function(_id){
	if(!_id){
		ui.error('专题信息不正确!');
	}
	ui.box.load(U('classroom/AdminSpecial/addcover')+'&sid='+_id+'&a='+Math.random(), "添加专题封面");
}

	//处理点评
admin.delReviewAll = function(action){

	var ids=admin.getChecked();
	ids = ("undefined"== typeof(ids)|| ids=='') ? admin.getChecked() : ids;
	if(ids==''){
		ui.error("问答id不能为null");
		return false;
	}
	if(!confirm("是否确认删除此点评？")){
		return false;
	}
	$.post(U('classroom/AdminReview/'+action),{ids:ids},function(msg){
		admin.ajaxReload(msg);
	},'json');
};

admin.delReview=function(_id,action){
	var id = ("undefined"== typeof(_id)|| _id=='') ? admin.getChecked() : _id;
	if(id==''){
		ui.error("问答id不能为null");
		return false;
	}
	if(!confirm("是否确认删除此点评？")){
		return false;
	}
	$.post(U('classroom/AdminReview/'+action),{ids:id},function(msg){
		admin.ajaxReload(msg);
	},'json');
}







//处理连载分期
admin.mzXdateEdit = function(_id,action,title,type){
	var id = ("undefined"== typeof(_id)|| _id=='') ? admin.getChecked() : _id;
    if(id==''){
        ui.error(L('PUBLIC_SELECT_TITLE_TYPE',{'title':title,'type':type}));
        return false;
   }
   if(confirm(L('PUBLIC_CONFIRM_DO',{'title':title,'type':type}))){
	   $.post(U('classroom/AdminLianZai/'+action),{id:id},function(msg){
			admin.ajaxReload(msg);
  	 },'json');
   }	
};
//处理连载内容
admin.mzLzContentEdit = function(_id,action,title,type){
	var id = ("undefined"== typeof(_id)|| _id=='') ? admin.getChecked() : _id;
    if(id==''){
        ui.error(L('PUBLIC_SELECT_TITLE_TYPE',{'title':title,'type':type}));
        return false;
   }
   if(confirm(L('PUBLIC_CONFIRM_DO',{'title':title,'type':type}))){
	   $.post(U('classroom/AdminLianZai/'+action),{id:id},function(msg){
			admin.ajaxReload(msg);
  	 },'json');
   }	
};

//处理连载内容----选择内容类型的点击事件
admin.mzchangeContent = function(_this){
	var val = $(_this).val();
	//内容类型【1:图文类型;2:文章类型;3:视频类型】
	if(val == 1){
		$('#txtmzimage').show();
		$('#txtmzvideo').hide();
	}else if(val == 3){
		$('#txtmzimage').hide();
		$('#txtmzvideo').show();
	}
};


addcontentcheckForm = function(_this){
	if(_this.find('input[name="title"]').val().replace(/^ +| +$/g,'')==''){
		ui.error('连载标题不能为空!');
		return false;
	}
	if(_this.find('textarea[name="description"]').val().replace(/^ +| +$/g,'')==''){
		ui.error('连载描述不能为空!');
		return false;
	}
	if(_this.find('input[name="source"]').val().replace(/^ +| +$/g,'')==''){
		ui.error('连载来源不能为空!');
		return false;
	}
	return true;
}
addcontentpost_callback = function(_this,data){
	if(data.status != undefined){
		if(data.status == '0'){
			ui.error(data.info);
		} else {
			ui.success(data.info);
			setTimeout(function(){
				window.location.href = data.data.jumpUrl;	
			},1255);
		}
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
				//mzgaojiaowang.ajaxDone(xMLHttpRequest, textStatus, errorThrown);
				if(typeof callback != 'undefined' && callback instanceof Function){   
					callback($form,xMLHttpRequest);
				}  
			},
			error: function(xhr, ajaxOptions, thrownError){
				ui.error("未知错误!");
				//mzgaojiaowang.ajaxError(xhr, ajaxOptions, thrownError);
			}
		});
	}
	_submitFn();
	return false;
}
admin.addSubjectCategory = function(){
    ui.box.load(U('classroom/AdminVideoCategory/addSubjectCategory'), "添加科目分类");
}
admin.editSubjectCategory = function(subject_id){
    ui.box.load(U('classroom/AdminVideoCategory/editSubjectCategory')+'&subject_id='+subject_id, "编辑科目分类");
}
admin.delSubject = function(subject_id){
   if(confirm("你确定要删除此科目分类？")){
	   $.post(U('classroom/AdminVideoCategory/delSubjectCategory'),{subject_id:subject_id},function(msg){
			if(msg.status==0){
	        	ui.error(msg.data);
	        }else{
	        	ui.success(msg.data);
	        	window.location.href = window.location.href;
	        }
  	 	},'json');
   }
}


admin.reviewhuifu=function(_id,action){
	var id = ("undefined"== typeof(_id)|| _id=='') ? admin.getChecked() : _id;
	if(id==''){
		ui.error("问答id不能为null");
		return false;
	}
	if(!confirm("是否确认删除此回复？")){
		return false;
	}
	$.post(U('classroom/AdminVideo/'+action),{ids:id},function(msg){
		admin.ajaxReload(msg);
	},'json');
}

//删除 课程优惠券
admin.mzdelcoupon=function(_id,action){

	var id = ("undefined"== typeof(_id)|| _id=='') ? admin.getChecked() : _id;
	if(id==''){
		ui.error("卡券id不能为null");
		return false;
	}
	if(!confirm("是否确认删除此卡券？")){

		
		return false;
	}
	$.post(U('classroom/AdminVideoCoupon/'+action),{ids:id},function(msg){
		admin.ajaxReload(msg);
	},'json');
}

//处理机构
admin.mzSchool=function(_id,action,title,type,uid){

	var id = ("undefined"== typeof(_id)|| _id=='') ? admin.getChecked() : _id;
	if(id==''){
		ui.error("请选择要操作的机构!");
		return false;
	}

	$.post(U('public/Message/doPost'),{to:uid,content:'123',school:'234',dotype:title},function(msg) {
	},'json');

	if(confirm(L('PUBLIC_CONFIRM_DO',{'title':title,'type':type}))) {
		$.post(U('school/AdminSchool/' + action), {id: id}, function (msg) {
			admin.ajaxReload(msg);
		}, 'json');

	}
};

//检查机构表单提交
admin.checkSchool=function(form) {
	if(form.title.value.replace(/^ +| +$/g,'')==''){
		ui.error('机构名称不能为空!');
		return false;
	}
	if($('.mzTopLevel option:selected').val() <= 0){
		ui.error('请选择机构分类!');
		return false;
	}
	if(form.logo.value.replace(/^ +| +$/g,'')==''){
		ui.error('请上传机构logo!');
		return false;
	}
	if(form.cover.value.replace(/^ +| +$/g,'')==''){
		ui.error('请上传机构封面!');
		return false;
	}
    if(form.buid.value.replace(/^ +| +$/g,'')==''){
        ui.error('绑定用户名不能为空!');
        return false;
    }
    if(form.doadmin.value.replace(/^ +| +$/g,'')==''){
        ui.error('机构域名不能为空!');
        return false;
    }
    if(form.videoSpace.value.replace(/^ +| +$/g,'')==''){
        ui.error('机构视频空间不能为空!');
        return false;
    }
    // if(form.school_and_teacher.value.replace(/^ +| +$/g,'')==''){
    //     ui.error('机构与教师分成比例不能为空!');
    //     return false;
    // }
    // var sat = form.school_and_teacher.value;
    // var school_and_teacher = sat.split(":");
    // if(parseFloat(school_and_teacher[0]) + parseFloat(school_and_teacher[1]) != 1){
    //     ui.error('机构与教师分成比例之和须为1!');
    //     return false;
    // }
    var saot = form.school_and_oschool.value;
    var school_and_oschool = saot.split(":");
    if(parseFloat(school_and_oschool[0]) + parseFloat(school_and_oschool[1]) != 1){
        ui.error('机构与挂载机构分成比例之和须为1!');
        return false;
    }
    if(form.type.value.replace(/^ +| +$/g,'')==''){
        ui.error('机构类型不能为空!');
        return false;
    }
    if(form.info.value.replace(/^ +| +$/g,'')==''){
        ui.error('机构简介不能为空!');
        return false;
    }

	return true;
};
/**
 * 申请通过 ( 机构 )
 */
admin.mzVerifySchool= function(id,status){
    if("undefined" == typeof(id) || id=='')
        id = admin.getChecked();
    if(id == ''){
        if(status == 1){
            ui.error('请选择要通过机构');
            return false;
        }else{
            ui.error('请选择要驳回机构');
            return false;
            }
        }
    if(id){
		$.post(U('school/AdminSchool/doVerify'),{id:id,status:status},function(msg){
        	admin.ajaxReload(msg);
    	},'json');
    }
};
/**
 * 认证驳回弹窗
 * @param integer id 驳回ID
 * @return void
 */
admin.mzGetVerifyBox = function (id) {
	if (typeof id === 'undefined') {
		return false;
	}
	ui.box.load(U('school/AdminSchool/getVerifyBox') + '&id=' + id, '驳回理由');
	return false;
};
/**
 * 申请通过 ( 机构 信息)
 */
admin.mzEditVerifySchool= function(id,status){
	if("undefined" == typeof(id) || id=='')
		id = admin.getChecked();
	if(id == ''){
		if(status == 1){
			ui.error('请选择要通过机构');
			return false;
		}else{
			ui.error('请选择要驳回机构');
			return false;
		}
	}
	if(id){
		$.post(U('school/AdminSchool/doEditVerify'),{id:id,status:status},function(msg){
			admin.ajaxReload(msg);
		},'json');
	}
};
/**
 * 信息驳回弹窗
 * @param integer id 驳回ID
 * @return void
 */
admin.mzEditGetVerifyBox = function (id) {
	if (typeof id === 'undefined') {
		return false;
	}
	ui.box.load(U('school/AdminSchool/getEditVerifyBox') + '&id=' + id, '驳回理由');
	return false;
};

/**
 * 审核讲师
 */
admin.mzTeacherVerify= function(id,status){
	if("undefined" == typeof(id) || id=='')
		id = admin.getChecked();
	if(id == ''){
		if(status == 1){
			ui.error('请选择要通过讲师');
			return false;
		}else{
			ui.error('请选择要驳回讲师');
			return false;
		}
	}
	if(id){
		$.post(U('school/AdminSchool/saveVerify'),{id:id,status:status},function(msg){
			admin.ajaxReload(msg);
		},'json');
	}
};
/**
 * 讲师驳回弹窗
 * @param integer id 驳回ID
 * @return void
 */
admin.mzTeacherVerifyBox = function (id) {
	if (typeof id === 'undefined') {
		return false;
	}
	ui.box.load(U('school/AdminSchool/getTeacherVerifyBox') + '&id=' + id, '驳回理由');
	return false;
};

/**
 * 申请通过、驳回 ( 视频空间,独立域名 )
 * @param  integer id  申请ID
 * @param  integer status 申请状态
 */
admin.mzVerify = function(id,status,type){
  	
    if("undefined" == typeof(id) || id=='')
        id = admin.getChecked();
    if(id == ''){
        if(status == 1){
            if(type == 1){
                ui.error('请选择要通过视频空间');
                return false;
            }else if(type == 2){
                ui.error('请选择要通过独立域名');
                return false;
            }
        }else{
            if(type == 1){
                ui.error('请选择要驳回视频空间');
                return false;
            }else if(type == 2){
                ui.error('请选择要驳回独立域名');
                return false;
            }
        }
    }

    if(id){
    	if(type ==1){
    		$.post(U('school/AdminVideoSpace/doVerify'),{id:id,type:type,status:status},function(msg){
            	admin.ajaxReload(msg);
        	},'json');
   		}else if(type ==2){
   			$.post(U('school/AdminDomaiName/doVerify'),{id:id,type:type,status:status},function(msg){
            	admin.ajaxReload(msg);
        	},'json');
   		}
        
    }

};

/**
 * 申请通过、驳回 ( 独立财务账号 )
 */
admin.mzVerified= function(id,status){
    if("undefined" == typeof(id) || id=='')
        id = admin.getChecked();
    if(id == ''){
        if(status == 1){
            ui.error('请选择要通过独立财务账号');
            return false;
        }else{
            ui.error('请选择要驳回独立财务账号');
            return false;
            }
        }
    if(id){
		$.post(U('school/AdminFinance/doVerify'),{id:id,status:status},function(msg){
        	admin.ajaxReload(msg);
    	},'json');
    }
};

admin.getChecked = function() {
    var ids = new Array();
    $.each($('.table_dl input:checked,#list input:checked'), function(i, n){
        if($(n).val() !='0' && $(n).val()!='' ){
            ids.push( $(n).val() );    
        }
    });
    return ids;
};

/**
 * 申请通过、驳回 ( 独立财务账号 )
 */
admin.mzVerified= function(id,status){
    if("undefined" == typeof(id) || id=='')
        id = admin.getChecked();
    if(id == ''){
        if(status == 1){
            ui.error('请选择要通过独立财务账号');
            return false;
        }else{
            ui.error('请选择要驳回独立财务账号');
            return false;
            }
        }
    if(id){
		$.post(U('school/AdminFinance/doVerify'),{id:id,status:status},function(msg){
        	admin.ajaxReload(msg);
    	},'json');
    }
};

//处理约课
admin.meetcourse=function(_id,action){
	var id = ("undefined"== typeof(_id)|| _id=='') ? admin.getChecked() : _id;
	if(id==''){
		ui.error("问答id不能为null");
		return false;
	}
	if(!confirm("是否确认执行此操作？")){
		return false;
	}
	$.post(U('school/AdminCourse/'+action),{ids:id},function(msg){
		admin.ajaxReload(msg);
	},'json');
}

/**
 * 处理ajax返回数据之后的刷新操作
 */
admin.ajaxReloads = function(obj){
	if(obj.status == 1){
		ui.success(obj.info,3);
		window.location.reload();
	}else{
		ui.error(obj.info,3);
	}
};

/**
 *
 * @param id
 * @returns {boolean}
 * 直播操作
 */
admin.livedoaction = function(id,action,type){
	if("undefined" == typeof(id) || id=='')
		id = admin.getChecked();
	if(id == ''){
		ui.error( '请选择直播课堂' );return false;
	}
	if(confirm( '确定操作该课堂直播？' )){
		$.post(U('school/AdminLive/doaction'+action),{id:id,type:type},function(obj){
			admin.ajaxReloads(obj);
		},'json');
	}
};

/**
 *
 * @param id
 * @returns {boolean}
 * 直播操作
 */
admin.switchdoaction = function(id,action,type){
	if("undefined" == typeof(id) || id=='')
		id = admin.getChecked();
	if(id == ''){
		ui.error( '请选择直播课堂' );return false;
	}
	if(confirm( '确定操作该课堂直播？' )){
		$.post(U('school/AdminLive/doaction'+action),{id:id,type:type},function(obj){
			admin.ajaxReloads(obj);
		},'json');
	}
};


/**
 *
 * @param id
 * @returns {boolean}
 * 课程操作
 */
admin.switchvideo = function(id,action,type){
	if("undefined" == typeof(id) || id=='')
		id = admin.getChecked();
	if(id == ''){
		ui.error( '请选择课程' );return false;
	}
	if(confirm( '确定操作该课程？' )){
		$.post(U('school/AdminVideo/doaction'+action),{id:id,type:type},function(obj){
			admin.ajaxReloads(obj);
		},'json');
	}
};

//禁用 处理卡券
admin.mzCouponCardEdit = function(_id,action,title,type){
	var id = ("undefined"== typeof(_id)|| _id=='') ? admin.getChecked() : _id;
	if(id==''){
		ui.error(L('PUBLIC_SELECT_TITLE_TYPE',{'title':title,'type':type}));
		return false;
	}
	if(confirm(L('PUBLIC_CONFIRM_DO',{'title':title,'type':type}))){
		$.post(U('school/AdminVideoCoupon/'+action),{id:id},function(msg){
			admin.ajaxReload(msg);
		},'json');
	}
};



//审核
admin.activeVideo = function(id,action,cross){
	if(!id){
		ui.error("id不能为null");
		return false;
	}

	if( confirm('是否确认？') ) {
		$.post(U('school/'+action+'/crossVideo'), {id: id, cross: cross}, function (txt) {
			if (txt.status == 0) {
				ui.error(txt.info);
			} else {
				ui.success(txt.info);
				window.location.href = window.location.href;
			}
		}, 'json');
	}
};
//机构分成比例审核
admin.mzSaveDivide = function (id,type,status){
	if("undefined" == typeof(id) || id=='')
		id = admin.getChecked();
	if(id == ''){
		if(status == 1){
			ui.error('请选择要通过的机构');
			return false;
		}
	}
	if(id){
		$.post(U('school/AdminSchool/doSaveDivide'),{id:id,type:type,status:status},function(msg){
			if (msg.status == 0) {
				ui.error(msg.info);
			} else {
				ui.success(msg.info);
				window.location.href = window.location.href;
			}
		},'json');
	}
};


admin.openMount= function(id,property) {
	if(!id){
		ui.error("请选择操作的课程");
		return false;
	}
	if( confirm('你确定要挂载此课程吗？') ){
		$.post(U('school/AdminVideo/openMount'),{id:id,is_mount:property},function(txt){
			if(txt.status == 0){
				ui.error(txt.info);
			} else {
				ui.success(txt.info);
				window.location.href = window.location.href;
			}
		},'json');
	}
	return true;
};
admin.closeMount= function(id,property) {
	if(!id){
		ui.error("请选择操作的课程");
		return false;
	}
	if( confirm('你确定要取消挂载此课程吗？') ){
		$.post(U('school/AdminVideo/closeMount'),{id:id,is_mount:property},function(txt){
			if(txt.status == 0){
				ui.error(txt.info);
			} else {
				ui.success(txt.info);
				window.location.href = window.location.href;
			}
		},'json');
	}
	return true;
};

admin.gotoarrcourse = function () {
	window.open(U('school/User/addArrCourse'));
}

//导出分成报表
admin.exportResult = function(id,type){
	if(id ==''){
		ui.error( "还没数据喏。。" );return false;
	}

	location.href = U('school/AdminSplit/splitExport')+'&id='+id+'&type='+type;
};
//导出分成报表
admin.exportOResult = function(id,type){
	if(id ==''){
		ui.error( "还没数据喏。。" );return false;
	}

	location.href = U('school/AdminOSplit/splitExport')+'&id='+id+'&type='+type;
};