// JavaScript Document

//检查商品表单提交
admin.checkGoods=function(form) {
	if(form.title.value.replace(/^ +| +$/g,'')==''){
		ui.error('商品名称不能为空!');
		return false;
	}
    if($('.mzTopLevel option:selected').val() <= 0){
		ui.error('请选择商品分类!');
		return false;
    }
    if(form.cover.value.replace(/^ +| +$/g,'')==''){
        ui.error('请上传商品封面!');
        return false;
    }
	if(form.info.value.replace(/^ +| +$/g,'')==''){
		ui.error('商品简介不能为空!');
		return false;
	}
	if(form.price.value.replace(/^ +| +$/g,'')==''){
		ui.error('商品价格不能为空!');
		return false;
	}
    if(isNaN(form.price.value)){
        ui.error('商品价格必须为数字!');
        return false;
    }
	if(form.stock.value.replace(/^ +| +$/g,'')==''){
		ui.error('商品库存不能为空!');
		return false;
	}
    if(isNaN(form.stock.value)){
        ui.error('商品库存必须为数字!');
        return false;
    }
	if(form.fare.value.replace(/^ +| +$/g,'')==''){
		ui.error('商品运费不能为空!');
		return false;
	}
    if(isNaN(form.fare.value)){
        ui.error('商品运费必须为数字!');
        return false;
    }

	return true;
};

//批量删除商品
admin.delGoodsAll = function(action,status){
    var ids=admin.getChecked();
    ids = ("undefined"== typeof(ids)|| ids=='') ? admin.getChecked() : ids;
    if(ids==''){
        ui.error("请选择你要操作的商品");
        return false;
    }
    if(!confirm("你确定要执行此操作？")){
        return false;
    }
    $.post(U('mall/AdminGoods/'+action),{ids:ids,status:status},function(msg){
        admin.ajaxReload(msg);
    },'json');
};
//彻底删除商品
admin.deleteGoods = function(goods_id){
    if(goods_id == ''){
        ui.error("请选择你要彻底删除的商品");
        return false;
    }
	if(confirm("你确定要彻底删除此商品？")){
		$.post(U('mall/AdminGoods/deleteGoods'),{goods_id:goods_id},function(msg){
			if(msg.status==0){
				ui.error(msg.info);
			}else{
				ui.success(msg.info);
				window.location.href = window.location.href;
			}
		},'json');
	}
};
//删除商品
admin.delGoods = function(goods_id){
    if(goods_id == ''){
        ui.error("请选择你要删除的商品");
        return false;
    }
	if(confirm("你确定要删除此商品？")){
		$.post(U('mall/AdminGoods/delGoods'),{goods_id:goods_id},function(msg){
			if(msg.status==0){
				ui.error(msg.info);
			}else{
				ui.success(msg.info);
				window.location.href = window.location.href;
			}
		},'json');
	}
};

//禁用商品
admin.closeGoods = function(goods_id){
    if(goods_id == ''){
        ui.error("请选择你要禁用的商品");
        return false;
    }
	if(confirm("你确定要禁用用此商品？")){
		$.post(U('mall/AdminGoods/closeGoods'),{goods_id:goods_id},function(msg){
			if(msg.status==0){
				ui.error(msg.info);
			}else{
				ui.success(msg.info);
				window.location.href = window.location.href;
			}
		},'json');
	}
};
//启用商品
admin.openGoods = function(goods_id){
    if(goods_id == ''){
        ui.error("请选择你要启用的商品");
        return false;
    }
	if(confirm("你确定要启用此商品？")){
		$.post(U('mall/AdminGoods/openGoods'),{goods_id:goods_id},function(msg){
			if(msg.status==0){
				ui.error(msg.info);
			}else{
				ui.success(msg.info);
				window.location.href = window.location.href;
			}
		},'json');
	}
};

//处理商品评论
admin.GoodsComment = function(_id,action,title,type){
	var id = ("undefined"== typeof(_id)|| _id=='') ? admin.getChecked() : _id;
	if(confirm(L('是否执行此操作',{'title':title,'type':type}))) {
		$.post(U('mall/AdminGoodsComment/' + action), {id: id}, function (msg) {
			admin.ajaxReload(msg);
		}, 'json');
	}
};



// 获取选中的id
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
 * 彻底删除收货地址
 * @param integer id 收货地址ID
 * @return void
 */
 admin.delAddressAll=function(action){

    var ids=admin.getChecked();
    ids = ("undefined"== typeof(ids)|| ids=='') ? admin.getChecked() : ids;
    if(ids==''){
        ui.error("收货地址id不能为null");
        return false;
    }
    if(!confirm("是否确认？")){
        return false;
    }
    $.post(U('mall/AdminGoodsAddress/'+action),{ids:ids},function(msg){
        admin.ajaxReload(msg);
    },'json');
}

admin.delAddress = function(_id){
	var id = ("undefined"== typeof(_id)|| _id=='') ? admin.getChecked() : _id;
	if(id==""){
		ui.error('请选择要删除的收货地址!');
		return false;
	}
	if(confirm('确定要删除此收货地址吗？')){
		$.post(U('mall/AdminGoodsAddress/delAddress'),{ids:id},function(msg){
	        admin.ajaxReload(msg);
	    },'json');
	}
}
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
// 批量删除收货地址
admin.batchDelAddress = function(_id){
	var id = ("undefined"== typeof(_id)|| _id=='') ? admin.getChecked() : _id;
	if(id==''){
		ui.error(L('PUBLIC_SELECT_TITLE_TYPE',{'title':'删除','type':'收货地址'}));
		return false;
	}
	if(confirm(L('PUBLIC_CONFIRM_DO',{'title':'删除','type':'收货地址'}))){
		$.post(U('mall/AdminGoodsAddress/delAddress'),{id:id},function(msg){
			admin.ajaxReload(msg);
		},'json');
	}
};

//禁用收货地址
admin.closeAddress= function(address_id){
	if(address_id == ''){
		ui.error("请选择你要禁用的收货地址");
		return false;
	}
	if(confirm("你确定要禁用此收货地址？")){
		$.post(U('mall/AdminGoodsAddress/closeAddress'),{address_id:address_id},function(msg){
			if(msg.status==0){
				ui.error(msg.info);
			}else{
				ui.success(msg.info);
				window.location.href = window.location.href;
			}
		},'json');
	}
}
//启用收货地址
admin.openAddress = function(address_id){
	if(address_id == ''){
		ui.error("请选择你要启用的收货地址");
		return false;
	}
	if(confirm("你确定要启用此收货地址吗？")){
		$.post(U('mall/AdminGoodsAddress/openAddress'),{address_id:address_id},function(msg){
			if(msg.status==0){
				ui.error(msg.info);
			}else{
				ui.success(msg.info);
				window.location.href = window.location.href;
			}
		},'json');
	}
};

//批量商品订单
admin.delGoodsOrder=function(){

	var ids=admin.getChecked();
	ids = ("undefined"== typeof(ids)|| ids=='') ? admin.getChecked() : ids;
	if(ids==''){
		ui.error("请选择你要删除的订单!");
		return false;
	}
	if(!confirm("你确定要删除此订单吗？")){
		return false;
	}
	$.post(U('mall/AdminGoodsOrder/delGoodsOrder'),{ids:ids},function(msg){
		admin.ajaxReload(msg);
	},'json');
};