// JavaScript Document

admin.zyPageBack = function(){
    window.history.back();
    return false;
}
//彻底删除用户积分及其关联流水
admin.delUserGreditAFlow=function(ids){

    var ids = ids ? ids : admin.getChecked();
    ids = ("undefined"== typeof(ids)|| ids=='') ? admin.getChecked() : ids;
    if(ids==''){
        ui.error("请选择你要删除的相关记录!");
        return false;
    }
    if(!confirm("你确定要删除此人的相关记录吗？")){
        return false;
    }
    $.post(U('mall/AdminGlobal/delUserGreditAFlow'),{ids:ids},function(msg){
        admin.ajaxReload(msg);
    },'json');
};