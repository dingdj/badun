// JavaScript Document

//���ؿγ̡�ר��
/**
	id Ҫ���ص�id
	type: Album ���� Video
	property ��ǰ�����״̬
*/
admin.delObject= function(id,type,property) {
	if(!type){
		return false;
	}
	if( confirm('你确定要执行此操作？') ){
		$.post(U('school/Admin'+type+'/del'+type),{id:id,is_del:property},function(txt){
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
admin.openObject= function(id,type,property) {
	if(!type){
		return false;
	}
	if( confirm('你确定要启用吗？') ){
		$.post(U('school/Admin'+type+'/open'+type),{id:id,is_del:property},function(txt){
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


admin.closeObject= function(id,type,property) {
	if(!type){
		return false;
	}
	if( confirm('你确定要禁用吗？') ){
		$.post(U('school/Admin'+type+'/close'+type),{id:id,is_del:property},function(txt){
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

/**
	ɾ��γ̻���ר��
	id Ҫɾ���id
	type: Album ���� Video
	property Ҫɾ�����ݶ���(����ask���ʼ�note������review)
*/
admin.delContent = function(id,type,property) {
	if(!type){
		return false;
	}
	if( confirm('你确定要删除吗？') ){
		$.post(U('classroom/Admin'+type+'/delProperty'),{id:id,property:property},function(txt){
			if(txt.status == 0){
				ui.error(txt.info);
			} else {
				ui.success(txt.info);
				window.location.href = window.location.href;
			}
		},'json');
	}
};

//��ʼ�γ����
admin.crossVideo = function(id,cross){
	if(!id){
		return false;
	}
	$.post(U('classroom/AdminVideo/crossVideo'),{id:id,cross:cross},function(txt){
		if(txt.status == 0){
			ui.error(txt.info);
		} else {
			ui.success(txt.info);
			window.location.href = window.location.href;
		}
	},'json');
};


//视频批量审核的JS
admin.crossVideos = function(action){

	var ids=admin.getChecked();
	ids = ("undefined"== typeof(ids)|| ids=='') ? admin.getChecked() : ids;
	if(ids==''){
		ui.error("id不能为null");
		return false;
	}
	if(!confirm("是否确认审核？")){
		return false;
	}
	$.post(U('school/AdminVideo/'+action),{ids:ids},function(msg){
		admin.ajaxReload(msg);
	},'json');
};

/**
销课班主任
 */
admin.editMountTeacher = function(id,type) {
	if(!type){
		return false;
	}
	if( confirm('您确定要操作吗？') ){
		$.post(U('school/AdminUser/editMountTeacher'),{id:id,type:type},function(txt){
			if(txt.status == 0){
				ui.error(txt.info);
			} else {
				ui.success(txt.info);
				window.location.href = window.location.href;
			}
		},'json');
	}
};

//批量禁用课程列表
admin.delVideoAll=function(action,type,status){

	var ids=admin.getChecked();
	ids = ("undefined"== typeof(ids)|| ids=='') ? admin.getChecked() : ids;
	if(ids==''){
		ui.error("id不能为null");
		return false;
	}
	if(!confirm("是否确认？")){
		return false;
	}
	$.post(U('school/Admin'+action+'/'+type),{ids:ids,status:status},function(msg){
		admin.ajaxReload(msg);
	},'json');
};

//批量禁用(删除)视频库
admin.delVideoLib=function(type){

    var ids=admin.getChecked();
    ids = ("undefined"== typeof(ids)|| ids=='') ? admin.getChecked() : ids;
    if(ids==''){
        ui.error("id不能为null");
        return false;
    }
    if(!confirm("是否确认？")){
        return false;
    }
    $.post(U('school/AdminVideo/delVideoLib'),{ids:ids,type:type},function(msg){
        admin.ajaxReload(msg);
    },'json');
};

//批量删除班级
admin.delAlbumAll=function(action,status){

    var ids=admin.getChecked();
    ids = ("undefined"== typeof(ids)|| ids=='') ? admin.getChecked() : ids;
    if(ids==''){
        ui.error("id不能为null");
        return false;
    }
    if(!confirm("是否确认？")){
        return false;
    }
    $.post(U('school/AdminAlbum/'+action),{ids:ids,status:status},function(msg){
        admin.ajaxReload(msg);
    },'json');
};

//批量操作订单
admin.delOrders=function(type){

	var ids=admin.getChecked();
	ids = ("undefined"== typeof(ids)|| ids=='') ? admin.getChecked() : ids;
	if(ids==''){
		ui.error("请选择要操作的订单!");
		return false;
	}
	if(!confirm("确定要执行此操作？")){
		return false;
	}
	$.post(U('school/AdminOrder/delOrders'),{ids:ids,type:type},function(msg){
		admin.ajaxReload(msg);
	},'json');
};

//批量操作分成明细列表
admin.delSplits=function(type){

    var ids=admin.getChecked();
    ids = ("undefined"== typeof(ids)|| ids=='') ? admin.getChecked() : ids;
    if(ids==''){
        ui.error("请选择要操作的分成明细!");
        return false;
    }
    if(!confirm("确定要执行此操作？")){
        return false;
    }
    $.post(U('school/AdminSplit/delSplits'),{ids:ids,type:type},function(msg){
        admin.ajaxReload(msg);
    },'json');
};

//批量删除学习记录
admin.delLearns=function(type){

	var ids=admin.getChecked();
	ids = ("undefined"== typeof(ids)|| ids=='') ? admin.getChecked() : ids;
	if(ids==''){
		ui.error("请选择要删除的学习记录!");
		return false;
	}
	if(!confirm("确定要删除此学习记录？")){
		return false;
	}
	$.post(U('school/AdminLearnRecord/delLearn'),{id:ids,type:type},function(msg){
		admin.ajaxReload(msg);
	},'json');
};