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
		ui.error('讲师照片!');
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
admin.mzNoteEdit = function(_id,action,title,type,uid,note_title,ctime,dotype){
	var id = ("undefined"== typeof(_id)|| _id=='') ? admin.getChecked() : _id;



	var uid = uid;
	var content =note_title;
	var ctime = ctime;
	var dotype =title;
	var nowboject = '笔记';
	$.post(U('public/Message/doPost'),{to:uid,content:content,ctime:ctime,dotype:dotype,nowboject:nowboject},function(msg) {
	},'json');


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
admin.delNoteEdit=function(_id,action,uid,note_title,ctime,dotype){
	var id = ("undefined"== typeof(_id)|| _id=='') ? admin.getChecked() : _id;
	if(id==''){
		ui.error("问答id不能为null");
		return false;
	}
	if(!confirm("是否确认删除此笔记？")){
		return false;
	}


	var uid = uid;
	var content =note_title;
	var ctime = ctime;
	var dotype = '删除';
	var nowboject = '笔记';
	$.post(U('public/Message/doPost'),{to:uid,content:content,ctime:ctime,dotype:dotype,nowboject:nowboject},function(msg) {
	},'json');

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

admin.mzdelquest=function(_id,action,qst_description,uid,ctime){
	var id = ("undefined"== typeof(_id)|| _id=='') ? admin.getChecked() : _id;
	if(id==''){
		ui.error("问答id不能为null");
		return false;
	}
	if(!confirm("是否确认删除此提问？")){
		return false;
	}


	var uid = uid;
	var content =qst_description;
	var ctime = ctime;
	var dotype = '删除';
	var nowboject = '提问';


	$.post(U('public/Message/doPost'),{to:uid,content:content,ctime:ctime,dotype:dotype,nowboject:nowboject},function(msg) {
	},'json');

	$.post(U('classroom/AdminQuestion/'+action),{ids:id},function(msg){
		admin.ajaxReload(msg);
	},'json');
}


//处理点评
admin.mzReviewEdit = function(_id,action,title,type,uid,description,ctime){

	var id = ("undefined"== typeof(_id)|| _id=='') ? admin.getChecked() : _id;
    if(id==''){
        ui.error(L('PUBLIC_SELECT_TITLE_TYPE',{'title':title,'type':type}));
        return false;
   }
	var uid = uid;
	var content =description;
	var ctime = ctime;
	var nowboject = type;
	var dotype = title;



	$.post(U('public/Message/doPost'),{to:uid,content:content,ctime:ctime,dotype:dotype,nowboject:nowboject},function(msg) {
	},'json');

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
	   $.post(U('classroom/AdminTeacher/'+action),{id:id,category:category},function(msg){
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




admin.delReview=function(_id,action,uid,ctime,review_description){
	var id = ("undefined"== typeof(_id)|| _id=='') ? admin.getChecked() : _id;
	var uid = uid;
	var content =review_description;
	var ctime = ctime;
	var dotype = '删除';
	var nowboject = '评论';
	if(id==''){
		ui.error("问答id不能为null");
		return false;
	}
	if(!confirm("是否确认删除此点评？")){
		return false;
	}

	$.post(U('public/Message/doPost'),{to:uid,content:content,ctime:ctime,dotype:dotype,nowboject:nowboject},function(msg) {

	},'json');

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

//禁用 处理卡券
admin.mzCouponCardEdit = function(_id,action,title,type){
	var id = ("undefined"== typeof(_id)|| _id=='') ? admin.getChecked() : _id;
    if(id==''){
        ui.error(L('PUBLIC_SELECT_TITLE_TYPE',{'title':title,'type':type}));
        return false;
   }
   if(confirm(L('PUBLIC_CONFIRM_DO',{'title':title,'type':type}))){
	   $.post(U('classroom/AdminVideoCoupon/'+action),{id:id},function(msg){
			admin.ajaxReload(msg);
  	 },'json');
   }	
};

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
//折扣类型验证
admin.mzclearNoNum=function(obj){
    obj.value = obj.value.replace(/[^\d.]/g,""); //清除"数字"和"."以外的字符
    obj.value = obj.value.replace(/^\./g,""); //验证第一个字符是数字而不是
    obj.value = obj.value.replace(/\.{2,}/g,"."); //只保留第一个. 清除多余的
    obj.value = obj.value.replace(".","$#$").replace(/\./g,"").replace("$#$",".");
    obj.value = obj.value.replace(/^(\-)*(\d+)\.(\d\d).*$/,'$1$2.$3'); //只能输入两个小数
}



admin.delWenda=function(_id,action,uid,wd_description,ctime){
    var id = ("undefined"== typeof(_id)|| _id=='') ? admin.getChecked() : _id;
    if(id==''){
        ui.error("问答id不能为null");
        return false;
    }
    if(!confirm("是否确认删除此问答？")){
        return false;
    }
	var uid = uid;
	var content =wd_description;
	var ctime = ctime;
	var dotype = '删除';
	var nowboject = '问答';
	$.post(U('public/Message/doPost'),{to:uid,content:content,ctime:ctime,dotype:dotype,nowboject:nowboject},function(msg) {
	},'json');

	$.post(U('classroom/AdminWenda/'+action),{ids:id},function(msg){
        admin.ajaxReload(msg);
    },'json');
}

admin.delWendaAll=function(action){

  var ids=admin.getChecked();
   ids = ("undefined"== typeof(ids)|| ids=='') ? admin.getChecked() : ids;
    if(ids==''){
        ui.error("问答id不能为null");
        return false;
    }
    if(!confirm("是否确认删除此问答？")){
        return false;
    }
    $.post(U('classroom/AdminWenda/'+action),{ids:ids},function(msg){
        admin.ajaxReload(msg);
    },'json');
}


admin.delWendahuifu=function(_id,action,uid,description,ctime){
    var id = ("undefined"== typeof(_id)|| _id=='') ? admin.getChecked() : _id;
    if(id==''){
        ui.error("问答id不能为null");
        return false;
    }
    if(!confirm("是否确认删除此回复？")){
        return false;
    }
	var uid = uid;
	var content =description;
	var ctime = ctime;
	var dotype = '删除';
	var nowboject = '问答回复';
	$.post(U('public/Message/doPost'),{to:uid,content:content,ctime:ctime,dotype:dotype,nowboject:nowboject},function(msg) {

	},'json');
    $.post(U('classroom/AdminWenda/'+action),{ids:id},function(msg){
        admin.ajaxReload(msg);
    },'json');
}

admin.delWendafuihuAll=function(action){

    var ids=admin.getChecked();
    ids = ("undefined"== typeof(ids)|| ids=='') ? admin.getChecked() : ids;
    if(ids==''){
        ui.error("问答id不能为null");
        return false;
    }
    if(!confirm("是否确认删除此回复？")){
        return false;
    }
    $.post(U('classroom/AdminWenda/'+action),{ids:ids},function(msg){
        admin.ajaxReload(msg);
    },'json');
}

admin.Mountacivity = function(id,action,type) {
	if (id == '') {
		ui.error('ID信息为空');
		return false;
	}
	if (confirm('确定审核？')) {
		$.post(U('classroom/AdminVideoMount/' + action), {id: id, type: type}, function (msg) {
			admin.ajaxReload(msg);
		}, 'json');
	}
}


/**
 * 认证通过
 * @param  integer id  认证ID
 * @param  integer status 认证状态
 * @param  string info 认证资料
 * @return void
 */
admin.doThroughAudit = function(id,type){
	if("undefined" == typeof(id) || id=='')
		id = admin.getChecked();
	if(id == ''){
				ui.error('订单ID不正确');
				return false;
			}
		ui.box.load(U('classroom/AdminApplirefund/doThroughAudit')+'&id='+id+'&type='+type,'审核中');
		return false;
};


/**
 * 认证驳回弹窗
 * @param integer id 驳回ID
 * @return void
 */
admin.doOverruleAudit = function (id,type) {
	if (typeof id === 'undefined') {
		return false;
	}
	ui.box.load(U('classroom/AdminApplirefund/doOverruleAudit') + '&id=' + id+'&type='+type, '驳回理由');
	return false;
};

admin.openAlbumMount= function(id,property) {
	if(!id){
		ui.error("请选择操作的课程");
		return false;
	}
	if( confirm('你确定要挂载此课程吗？') ){
		$.post(U('classroom/AdminAlbum/openMount'),{id:id,is_mount:property},function(txt){
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
admin.closeAlbumMount= function(id,property) {
	if(!id){
		ui.error("请选择操作的课程");
		return false;
	}
	if( confirm('你确定要取消挂载此课程吗？') ){
		$.post(U('classroom/AdminAlbum/closeMount'),{id:id,is_mount:property},function(txt){
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

//导出分成报表
admin.exportResult = function(id,type){
	if(id ==''){
		ui.error( "还没数据喏。。" );return false;
	}

	location.href = U('classroom/AdminSplit/splitExport')+'&id='+id+'&type='+type;
};

//导出卡券列表
admin.exportCoupon = function(explod){
	if(explod ==''){
		ui.error( "还没数据喏。。" );return false;
	}
	location.href = U('classroom/AdminEntityCard/exportCoupon')+"&explod="+explod;
};

//课时审核
admin.crossVideoSection = function(id,vid,type,ctype){

	if(type == 1){
		var ids=admin.getChecked();
		ids = ("undefined"== typeof(ids)|| ids=='') ? admin.getChecked() : ids;
	}else{
		ids = id;
	}

	if(ids==''){
		ui.error("请选择要操作的课时");
		return false;
	}
	if(!confirm("是否确认？")){
		return false;
	}
	$.post(U('classroom/AdminVideo/crossVideoSection'),{ids:ids,vid:vid,type:type,ctype:ctype},function(msg){
		admin.ajaxReload(msg);
	},'json');
};