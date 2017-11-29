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
	if( confirm('你确定要删除吗？') ){
		$.post(U('classroom/Admin'+type+'/del'+type),{id:id,is_del:property},function(txt){
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


admin.openObject= function(id,type,property,title,uid) {
	if(!type){
		return false;
	}

	var dotype = '启用';
	var videotype = '课程';
	var uid = uid;
	var content =title;

	$.post(U('public/Message/doPost'),{to:uid,content:content,dotype:dotype,videotype:videotype},function(msg) {

	},'json');

	if( confirm('你确定要启用吗？') ){
		$.post(U('classroom/Admin'+type+'/open'+type),{id:id,is_del:property},function(txt){
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

admin.openMount= function(id,property,title,uid) {
	if(!id){
        ui.error("请选择操作的课程");
        return false;
	}




	if( confirm('你确定要挂载此课程吗？') ){
		$.post(U('classroom/AdminVideo/openMount'),{id:id,is_mount:property},function(txt){
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
admin.closeMount= function(id,property,title,uid) {
	if(!id){
        ui.error("请选择操作的课程");
        return false;
	}




	if( confirm('你确定要取消挂载此课程吗？') ){
		$.post(U('classroom/AdminVideo/closeMount'),{id:id,is_mount:property},function(txt){
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


admin.closeObject= function(id,type,property,title,uid) {
	if(!type){
		return false;
	}

	var dotype = '禁用';
	var videotype = '课程';
	var uid = uid;
	var content =title;

	$.post(U('public/Message/doPost'),{to:uid,content:content,dotype:dotype,videotype:videotype},function(msg) {

	},'json');

	if( confirm('你确定要禁用吗？') ){
		$.post(U('classroom/Admin'+type+'/close'+type),{id:id,is_del:property},function(txt){
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


admin.delAlbums = function(id,type,property) {
	if(!type){
		return false;
	}
	if( confirm('你确定要删除吗？') ){
		$.post(U('classroom/Admin'+type+'/del'+type),{id:id,is_del:property},function(txt){
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


///删除课程
admin.delcourse=function(_id,action){
	var id = ("undefined"== typeof(_id)|| _id=='') ? admin.getChecked() : _id;
	if(id==''){
		ui.error("id不能为null");
		return false;
	}
	if(!confirm("是否确认删除？")){
		return false;
	}
	$.post(U('classroom/AdminVideo/'+action),{ids:id},function(msg){
		admin.ajaxReload(msg);
	},'json');
};


/**
 * 处理ajax返回数据之后的刷新操作
 */
admin.ajaxReload = function(obj,callback){
	if("undefined" == typeof(callback)){
		callback = "location.href = location.href";
	}else{
		callback = 'eval('+callback+')';
	}
	if(obj.status == 1){
		ui.success(obj.data);
		setTimeout(callback,1500);
	}else{
		ui.error(obj.data);
	}
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
admin.crossVideo = function(id,cross,title,uid){
	if(!id){
		ui.error("id不能为null");
		return false;
	}
	if(!confirm("是否确认？")){
		return false;
	}

	if(cross){
		var dotype = '已审核';
	}

	var videotype = '课程';
	var uid = uid;
	var content =title;

	$.post(U('public/Message/doPost'),{to:uid,content:content,dotype:dotype,videotype:videotype},function(msg) {

	},'json');


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
    if(!confirm("是否确认？")){
        return false;
    }
    $.post(U('classroom/AdminVideo/'+action),{ids:ids},function(msg){
        admin.ajaxReload(msg);
    },'json');
};

//立即上架、下架
admin.putawayObject= function(id,type,title,uid,status) {
    if(!id){
        ui.error("id不能为null");
        return false;
    }
	if(status == 1) {
		var dotype = '上架';
	}else{
		var dotype = '下架';
	}
		var videotype = '课程';
		var uid = uid;
		var content =title;

		$.post(U('public/Message/doPost'),{to:uid,content:content,dotype:dotype,videotype:videotype},function(msg) {

		},'json');

    if( confirm('是否确认？') ){
        $.post(U('classroom/AdminVideo/putaway'),{id:id,type:type},function(txt){
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