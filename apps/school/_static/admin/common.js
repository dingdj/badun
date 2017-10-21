// JavaScript Document

/**
 * 删除意见反馈
 * @param integer id 反馈ID
 * @return void
 */
admin.delSuggest = function(id){
	if('undefined' == typeof(id)||!id) id = admin.getChecked();
	if(!id){
        ui.error('请选择要删除的反馈');
		return false;
    }
	if(confirm('确定要删除此反馈吗？')){
        $.post(U('classroom/AdminSuggest/del'),{id:id},function(msg){
            admin.ajaxReload(msg);
        },'json');
    }
}
/**
 * 删除讲师
 * @param _id
 * @param action
 * @returns {boolean}
 */
admin.delTeacher=function(_id,action,title,category){

    var id = ("undefined"== typeof(_id)|| _id=='') ? admin.getChecked() : _id;
    if(id==''){
        ui.error("请选择你要彻底删除的"+title+"");
        return false;
    }
    if(confirm("你确定要彻底删除此"+title+"？")){
    $.post(U('school/AdminTeacher/'+action),{ids:id,category:category},function(msg){
        admin.ajaxReload(msg);
    },'json');
    }
}
admin.delTeacherAll=function(action,category){

    var ids=admin.getChecked();
    ids = ("undefined"== typeof(ids)|| ids=='') ? admin.getChecked() : ids;
    if(ids==''){
        ui.error("id不能为null");
        return false;
    }
    if(!confirm("是否确认？")){
        return false;
    }
    $.post(U('school/AdminTeacher/'+action),{ids:ids,category:category},function(msg){
        admin.ajaxReload(msg);
    },'json');
}

/**
 * 删除机构等级
 * @param _id
 * @param action
 * @returns {boolean}
 */
admin.delVip=function(_id,action){
    var id = ("undefined"== typeof(_id)|| _id=='') ? admin.getChecked() : _id;
    if(id==''){
        ui.error("请选择你要删除的机构等级");
        return false;
    }
    if(!confirm("是否确认删除此机构等级？")){
        return false;
    }
    $.post(U('school/AdminVip/'+action),{ids:id},function(msg){
        admin.ajaxReload(msg);
    },'json');
}

/**
 * 删除提现记录
 * @param integer id 提现记录ID
 * @return void
 */
admin.delWithdraw = function(id){
	if('undefined' == typeof(id)||!id) id = admin.getChecked();
	if(!id){
        ui.error('请选择要删除的记录');
		return false;
    }
	if(confirm('确定要删除此记录吗？')){
        $.post(U('classroom/AdminWithdraw/del'),{id:id},function(msg){
            admin.ajaxReload(msg);
        },'json');
    }
}

admin.zyPageBack = function(){
	window.history.back();
	return false;
}


//添加视频
admin.addVideoLib = function(){
     location.href = U('school/AdminVideo/addVideoLib')+'&tabHash=addVideoLib';
};

/**
 * 操作视频状态
 * @param integer id 提现记录ID
 * @return void
 */
admin.opervideo = function(id , field ,val){
    if('undefined' == typeof(id)||!id) id = admin.getChecked();
    if(!id){
        ui.error('请选择要操作的记录');
        return false;
    }
    if( confirm('确定要执行此操作吗？') ){
        $.post(U('classroom/AdminVideo/opervideo'),{id:id,field:field,val:val},function(msg){
            admin.ajaxReload(msg);
        },'json');
    }
}


//彻底删除文库
admin.deleteLibrary = function(library_id){
    if(library_id == ''){
        ui.error("请选择你要彻底删除的文库");
        return false;
    }
    if(confirm("你确定要彻底删除此文库？")){
        $.post(U('classroom/AdminLibrary/deleteLibrary'),{library_id:library_id},function(msg){
            if(msg.status==0){
                ui.error(msg.info);
            }else{
                ui.success(msg.info);
                window.location.href = window.location.href;
            }
        },'json');
    }
}
//删除文库
admin.delLibrary = function(library_id){
    if(library_id == ''){
        ui.error("请选择你要删除的文库");
        return false;
    }
    if(confirm("你确定要删除此文库？")){
        $.post(U('classroom/AdminLibrary/delLibrary'),{library_id:library_id},function(msg){
            if(msg.status==0){
                ui.error(msg.info);
            }else{
                ui.success(msg.info);
                window.location.href = window.location.href;
            }
        },'json');
    }
}

//禁用文库
admin.closeLibrary = function(library_id){
    if(library_id == ''){
        ui.error("请选择你要禁用的文库");
        return false;
    }
    if(confirm("你确定要禁用用此文库？")){
        $.post(U('classroom/AdminLibrary/closeLibrary'),{library_id:library_id},function(msg){
            if(msg.status==0){
                ui.error(msg.info);
            }else{
                ui.success(msg.info);
                window.location.href = window.location.href;
            }
        },'json');
    }
}
//启用文库
admin.openLibrary = function(library_id){
    if(library_id == ''){
        ui.error("请选择你要启用的文库");
        return false;
    }
    if(confirm("你确定要启用此文库？")){
        $.post(U('classroom/AdminLibrary/openLibrary'),{library_id:library_id},function(msg){
            if(msg.status==0){
                ui.error(msg.info);
            }else{
                ui.success(msg.info);
                window.location.href = window.location.href;
            }
        },'json');
    }
}
//批量禁用卡券
admin.delCouponAll=function(action){

    var ids=admin.getChecked();
    ids = ("undefined"== typeof(ids)|| ids=='') ? admin.getChecked() : ids;
    if(ids==''){
        ui.error("卡券id不能为null");
        return false;
    }
    if(!confirm("是否确认？")){
        return false;
    }
    $.post(U('classroom/AdminVideoCoupon/'+action),{ids:ids},function(msg){
        admin.ajaxReload(msg);
    },'json');
}

