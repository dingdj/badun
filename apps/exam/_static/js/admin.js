

//清空考试信息回收站
admin.mzTableclear = function(){
	var str="确定要清空回收站?";
   if(confirm(str)){
   		$.post(U('exam/AdminExam/delTable'),{},function(txt){
			if(txt.status == 0){
				ui.error(txt.info);
			} else {
				ui.success(txt.info);
				window.location.href = window.location.href;
			}
		},'json');
   }	
};
//处理考试信息
admin.mzExamEdit = function(_id,is_del,action,title,type){
	if(is_del==0){
		is_del=1;
	}else{
		is_del=0;
	}
	var id = ("undefined"== typeof(_id)|| _id=='') ? admin.getChecked() : _id;
    if(id==''){
        ui.error(L('PUBLIC_SELECT_TITLE_TYPE',{'title':title,'type':type}));
        return false;
   }
   if(confirm(L('PUBLIC_CONFIRM_DO',{'title':title,'type':type}))){
   		$.post(U('exam/AdminExam/'+action),{id:id,is_del:is_del},function(txt){
			if(txt.status == 0){
				ui.error(txt.info);
			} else {
				ui.success(txt.info);
				window.location.href = window.location.href;
			}
		},'json');
   }	
};
//处理试卷信息
admin.mzPaperEdit = function(_id,is_del,action,title,type){
	if(is_del==0){
		is_del=1;
	}else{
		is_del=0;
	}
	var id = ("undefined"== typeof(_id)|| _id=='') ? admin.getChecked() : _id;
    if(id==''){
        ui.error(L('PUBLIC_SELECT_TITLE_TYPE',{'title':title,'type':type}));
        return false;
   }
   if(confirm(L('PUBLIC_CONFIRM_DO',{'title':title,'type':type}))){
   		$.post(U('exam/AdminPaper/'+action),{id:id,is_del:is_del},function(txt){
			if(txt.status == 0){
				ui.error(txt.info);
			} else {
				ui.success(txt.info);
				window.location.href = window.location.href;
			}
		},'json');
   }	
};
//处理考试信息
admin.mzCategoryEdit = function(_id,action,title,type){
	var id = ("undefined"== typeof(_id)|| _id=='') ? admin.getChecked() : _id;
    if(id==''){
        ui.error(L('PUBLIC_SELECT_TITLE_TYPE',{'title':title,'type':type}));
        return false;
   }
   if(confirm(L('PUBLIC_CONFIRM_DO',{'title':title,'type':type}))){
   		$.post(U('exam/AdminCategory/'+action),{id:id},function(txt){
			if(txt.status == 0){
				ui.error(txt.info);
			} else {
				ui.success(txt.info);
				window.location.href = window.location.href;
			}
		},'json');
   }	
};
//删除数据
admin.delObject= function(id,type,property) {
	if(!type){
		return false;
	}
	if( confirm('确定要删除吗?') ){
		$.post(U('exam/Admin'+type+'/del'+type),{id:id,is_del:property},function(txt){
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

//恢复数据
admin.recObject= function(id,type,property) {
	if(!type){
		return false;
	}
	if( confirm('确定要恢复吗?') ){
		$.post(U('exam/Admin'+type+'/del'+type),{id:id,question_is_del:property},function(txt){
			if(txt.status == 0){
				
			} else {
				ui.success(txt.info);
				window.location.href = window.location.href;
			}
		},'json');
	}
	return true;
};

//对用户考试数据进行隐藏/恢复
admin.mzUserExam = function(_id,is_del,action,title,type){
	if(is_del==0){
		is_del=1;
	}else{
		is_del=0;
	}
	var id = ("undefined"== typeof(_id)|| _id=='') ? admin.getChecked() : _id;
    if(id==''){
        ui.error(L('PUBLIC_SELECT_TITLE_TYPE',{'title':title,'type':type}));
        return false;
   }
   if(confirm(L('PUBLIC_CONFIRM_DO',{'title':title,'type':type}))){
   		$.post(U('exam/AdminUserExam/'+action),{id:id,is_del:is_del},function(txt){
			if(txt.status == 0){
				ui.error(txt.info);
			} else {
				ui.success(txt.info);
				window.location.href = window.location.href;
			}
		},'json');
   }	
};
//删除分类列表
admin.mzOptionCategoryEdit = function(_id,action,title,type){
	var id = ("undefined"== typeof(_id)|| _id=='') ? admin.getChecked() : _id;
    if(id==''){
        ui.error(L('PUBLIC_SELECT_TITLE_TYPE',{'title':title,'type':type}));
        return false;
   }
   if(confirm(L('PUBLIC_CONFIRM_DO',{'title':title,'type':type}))){
   		$.post(U('exam/AdminCategory/'+action),{id:id},function(txt){
			if(txt.status == 0){
				ui.error(txt.info);
			} else {
				ui.success(txt.info);
				window.location.href = window.location.href;
			}
		},'json');
   }	
};
//编辑试卷状态
admin.updatePaperStatus = function(id,status){
	if(status==0){
		status=1;
	}else{
		status=0;
	}
	$.post(U('exam/AdminPaper/update_paper_status'),{id:id,status:status},function(txt){
		if(txt.status == 0){
			ui.error(txt.info);
		} else {
			ui.success(txt.info);
			window.location.href = window.location.href;
		}
	},'json');

};
//清空试卷回收站
admin.mzPaperclear = function(){
	var str="确定要清空回收站?";
    if(confirm(str)){
   		$.post(U('exam/AdminPaper/delTable'),{},function(txt){
			if(txt.status == 0){
				ui.error(txt.info);
			} else {
				ui.success(txt.info);
				window.location.href = window.location.href;
			}
		},'json');
    }
};
//清空试题回收站
admin.mzRecycleClear = function(){
	var str="确定要清空回收站?";
    if(confirm(str)){
   		$.post(U('exam/AdminQuestion/delRecycle'),{},function(txt){
			if(txt.status == 0){
				ui.error(txt.info);
			} else {
				ui.success(txt.info);
				window.location.href = window.location.href;
			}
		},'json');
    }
};
//考试信息导出
admin.mzExport = function(id){
	window.location.href = U('exam/AdminExam/doExport')+"&id="+id; 
};

//考试证书锁定
admin.rmExamCert = function(cert_id){ 
    var str="确定要锁定该证书吗?";
    if(confirm(str)){
   		$.post(U('exam/AdminExamCert/rmCert'),{cert_id:cert_id},function(res){
			if(res.status == 0){
				ui.error(res.info);
			} else {
				ui.success(res.info);
				setTimeout(function(){
				    window.location.href = U('exam/AdminExamCert/index');
				},1800);
			}
		},'json');
    }
};

//考试证书恢复
admin.reExamCert = function(cert_id){ 
    var str="确定要解锁该证书吗?";
    if(confirm(str)){
   		$.post(U('exam/AdminExamCert/reCert'),{cert_id:cert_id},function(res){
			if(res.status == 0){
				ui.error(res.info);
			} else {
				ui.success(res.info);
				setTimeout(function(){
				    window.location.href = U('exam/AdminExamCert/locked')+'&tabHash=locked';
				},1800);
			}
		},'json');
    }
};

//批量放入回收站
admin.mzPutRecycle = function(action){

	var ids=admin.getChecked();
	ids = ("undefined"== typeof(ids)|| ids=='') ? admin.getChecked() : ids;
	if(ids==''){
		ui.error("请选择要删除的对象!");
		return false;
	}
	if(!confirm("确定要执行此操作？")){
		return false;
	}
	$.post(U('exam/Admin'+action+'/putRecycle'),{ids:ids},function(msg){
		admin.ajaxReload(msg);
	},'json');
};
//清空用户考试记录回收站
admin.mzRecycleClear = function(){
	var str="确定要清空回收站?";
	if(confirm(str)){
		$.post(U('exam/AdminUserExam/delRecycle'),{},function(txt){
			if(txt.status == 0){
				ui.error(txt.info);
			} else {
				ui.success(txt.info);
				window.location.href = window.location.href;
			}
		},'json');
	}
};