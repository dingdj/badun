
//添加在线调查分类
admin.surveyCate = function(ids) {
    location.href = U('survey/Admin/addCate');
};

//删除在线调查分类
admin.delSurveyCate = function(id){
    if(confirm('确定删除此分类吗？')){
        $.post(U('survey/Admin/delCate'), {ids:id}, function(msg){
              admin.ajaxReload(msg);
        },'json');
    }
};

//删除在线调查
admin.delSurvey = function(id){
    if(confirm('确定删除此在线调查吗？')){
        $.post(U('survey/Admin/delSurvey'), {ids:id}, function(msg){
              admin.ajaxReload(msg);
        },'json');
    }
};

//批量删除在线调查
admin.delSurveys = function(){
	var ids=admin.getChecked();

    ids = ("undefined"== typeof(ids)|| ids=='') ? admin.getChecked() : ids;
    if(ids==''){
        ui.error("请选择你要删除的在线调查");
        return false;
    }
    if(!confirm("确定删除选中的在线调查吗？")){
        return false;
    }
    $.post(U('survey/Admin/delSurvey'),{ids:ids},function(msg){
        admin.ajaxReload(msg);
    },'json');
};