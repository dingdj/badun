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
};
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
    $.post(U('classroom/AdminTeacher/'+action),{ids:id,category:category},function(msg){
        admin.ajaxReload(msg);
    },'json');
    }
};
//批量删除讲师相关
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
    $.post(U('classroom/AdminTeacher/'+action),{ids:ids,category:category},function(msg){
        admin.ajaxReload(msg);
    },'json');
};

/**
 * 删除vip等级
 * @param _id
 * @param action
 * @returns {boolean}
 */
admin.delVip=function(_id,type,action){
    var id = ("undefined"== typeof(_id)|| _id=='') ? admin.getChecked() : _id;
    if(id==''){
        ui.error("请选择你要删除的vip等级");
        return false;
    }
    if(!confirm("是否确认删除此vip等级？")){
        return false;
    }
    $.post(U('classroom/AdminVip/'+action),{ids:id},function(msg){
        admin.ajaxReload(msg);
    },'json');
};
/**
 * 禁用vip等级
 * @param _id
 * @param action
 * @returns {boolean}
 */
admin.closeVip=function(_id,type,action){
    var id = ("undefined"== typeof(_id)|| _id=='') ? admin.getChecked() : _id;
    if(id==''){
        ui.error("请选择你要禁用的"+type);
        return false;
    }
    if(!confirm("是否确认禁用此"+type)){
        return false;
    }
    $.post(U('classroom/'+action+'/closeVip'),{ids:id},function(msg){
        admin.ajaxReload(msg);
    },'json');
}
/**
 * 启用vip等级
 * @param _id
 * @param action
 * @returns {boolean}
 */
admin.openVip=function(_id,type,action){
    var id = ("undefined"== typeof(_id)|| _id=='') ? admin.getChecked() : _id;
    if(id==''){
        ui.error("请选择你要启用的"+type);
        return false;
    }
    if(!confirm("是否确认启用此"+type)){
        return false;
    }
    $.post(U('classroom/'+action+'/closeVip'),{ids:id},function(msg){
        admin.ajaxReload(msg);
    },'json');
};

admin.delVipAll=function(type,action){

    var ids=admin.getChecked();
    ids = ("undefined"== typeof(ids)|| ids=='') ? admin.getChecked() : ids;
    if(ids==''){
        ui.error("请选择你要删除的"+type);
        return false;
    }
    if(!confirm("是否确认？")){
        return false;
    }
    $.post(U('classroom/'+action+'/delVip'),{ids:ids},function(msg){
        admin.ajaxReload(msg);
    },'json');
};

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
};

admin.zyPageBack = function(){
	window.history.back();
	return false;
};


//添加视频
admin.addVideoLib = function(){
     location.href = U('classroom/AdminVideo/addVideoLib')+'&tabHash=addVideoLib';
};

/**
 * 操作视频状态
 * @param integer id 视频ID
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
};
/**
 * 同步上传视频
 * @param integer id 视频ID
 * @return void
 */
admin.auditVideo = function(id){
    if('undefined' == typeof(id)||!id) id = admin.getChecked();
    if(!id){
        ui.error('请选择要同步的视频');
        return false;
    }
    if( confirm('确定要执行此操作吗？') ){
        $.post(U('classroom/AdminVideo/verifyVideo'),{id:id},function(msg){
            if(msg.status == 1){
                ui.success(msg.info);
                window.location.href = window.location.href;
            }else{
                ui.error(msg.info);
            }
        },'json');
    }
};


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
};
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
};

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
};
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
};
//禁用 用户卡券
admin.mzUserCardEdit = function(_id,action,title,type){
    var id = ("undefined"== typeof(_id)|| _id=='') ? admin.getChecked() : _id;
    if(id==''){
        ui.error(L('PUBLIC_SELECT_TITLE_TYPE',{'title':title,'type':type}));
        return false;
    }
    if(confirm(L('PUBLIC_CONFIRM_DO',{'title':title,'type':type}))){
        $.post(U('classroom/AdminUserCard/'+action),{id:id},function(msg){
            admin.ajaxReload(msg);
        },'json');
    }
};

//批量禁用 用户卡券
admin.delUserCardAll=function(action){
    var ids=admin.getChecked();
    ids = ("undefined"== typeof(ids)|| ids=='') ? admin.getChecked() : ids;
    if(ids==''){
        ui.error("卡券id不能为null");
        return false;
    }
    if(!confirm("是否确认？")){
        return false;
    }
    $.post(U('classroom/AdminUserCard/'+action),{ids:ids},function(msg){
        admin.ajaxReload(msg);
    },'json');
};

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
};







admin.delWenda=function(_id,action){
    var id = ("undefined"== typeof(_id)|| _id=='') ? admin.getChecked() : _id;
    if(id==''){
        ui.error("问答id不能为null");
        return false;
    }
    if(!confirm("是否确认删除此问答？")){
        return false;
    }
    $.post(U('classroom/AdminWenda/'+action),{ids:id},function(msg){
        admin.ajaxReload(msg);
    },'json');
};

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
};


admin.delWendahuifu=function(_id,action){
    var id = ("undefined"== typeof(_id)|| _id=='') ? admin.getChecked() : _id;
    if(id==''){
        ui.error("问答id不能为null");
        return false;
    }
    if(!confirm("是否确认删除此回复？")){
        return false;
    }
    $.post(U('classroom/AdminWenda/'+action),{ids:id},function(msg){
        admin.ajaxReload(msg);
    },'json');
};

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
};

/**
 * 同步讲师上传视频信息
 * @param _id
 * @returns {boolean}
 */
admin.auditTeacherVideo = function(_id) {
    var id = ("undefined" == typeof(_id) || _id == '') ? admin.getChecked() : _id;
    if (id == '') {
        ui.error("请选择你要同步的视频");
        return false;
    }
    if (!confirm("是否确认同步此视频信息？")) {
        return false;
    }
    $.post(U('classroom/AdminTeacher/doSysTeacherVideo'), {id: id}, function (msg) {
        if(msg.status == 1){
            ui.success(msg.info);
            window.location.href = window.location.href;
        }else{
            ui.error(msg.info);
        }
    }, 'json');
};

//批量删除众筹列表
admin.delCrowAll=function(action){

    var ids=admin.getChecked();
    ids = ("undefined"== typeof(ids)|| ids=='') ? admin.getChecked() : ids;
    if(ids==''){
        ui.error("id不能为null");
        return false;
    }
    if(!confirm("是否确认？")){
        return false;
    }
    $.post(U('classroom/AdminCrow/'+action),{ids:ids},function(msg){
        admin.ajaxReload(msg);
    },'json');
};

//批量禁用课程列表
admin.delVideoAll=function(action){

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
    $.post(U('classroom/AdminVideo/delVideoLib'),{ids:ids,type:type},function(msg){
        admin.ajaxReload(msg);
    },'json');
};

//批量删除套餐
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
    $.post(U('classroom/AdminAlbum/'+action),{ids:ids,status:status},function(msg){
        admin.ajaxReload(msg);
    },'json');
};

//批量删除文库
admin.delLibraryAll=function(action,status){

    var ids=admin.getChecked();
    ids = ("undefined"== typeof(ids)|| ids=='') ? admin.getChecked() : ids;
    if(ids==''){
        ui.error("id不能为null");
        return false;
    }
    if(!confirm("是否确认？")){
        return false;
    }
    $.post(U('classroom/AdminLibrary/'+action),{ids:ids,status:status},function(msg){
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
    $.post(U('classroom/AdminOrder/delOrders'),{ids:ids,type:type},function(msg){
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
    $.post(U('classroom/AdminLearnRecord/delLearn'),{id:ids,type:type},function(msg){
        admin.ajaxReload(msg);
    },'json');
};




