/**
 * 定义考试系统的后台js
 * @Author MartinSun<syh@sunyonghong.com>
 * @Date   2017-10-17
 * @return {[type]} [description]
 */
function exams() {
	return this;
}

exams.prototype = {
	/**
	 * post请求方法
	 * @Author MartinSun<syh@sunyonghong.com>
	 * @Date   2017-10-17
	 * @param  {[type]} url [description]
	 * @param  {[type]} args [description]
	 * @param  {Function} callback [description]
	 * @return {[type]} [description]
	 */
	post:function(url,args,callback){
		$.post(url,args,function(res){
			if(typeof(res) != "object"){
				try{
					var res = JSON.parse(res);
				}catch(e){
					ui.error("请求出错,请稍后再试");
					return false;
				}
			}
			if($.isFunction(callback)){
				return callback(res);
			}
			return res;
		});
	},
	/**
	 * 窗口重载
	 * @Author MartinSun<syh@sunyonghong.com>
	 * @Date   2017-10-17
	 * @return {[type]} [description]
	 */
	windowReload:function(){
		setTimeout(function(){
			window.location.reload();
		},1500);
	},
	/**
	 * 删除版块
	 * @Author MartinSun<syh@sunyonghong.com>
	 * @Date   2017-10-17
	 * @param  {[type]} module_id [description]
	 * @return {[type]} [description]
	 */
	deleteMoudle:function(module_id){
		if(!module_id){
			ui.error('请选择需要删除的版块');
			return false;
		}
		if(confirm("确认要删除版块吗?")){
			exams.post(U("exams/AdminCategory/deleteModule"),{module_id:module_id},function(res){
				if(res.status == 1){
					ui.success(res.data.info);
					exams.windowReload();
				}else{
					ui.error(res.message);
					return false;
				}
			});
		}
	},
	/**
	 * 删除考点
	 * @Author MartinSun<syh@sunyonghong.com>
	 * @Date   2017-10-17
	 * @param  {[type]} point_id [description]
	 * @return {[type]} [description]
	 */
	deletePoint:function(point_id){
		if(!point_id){
			ui.error('请选择需要删除的考点');
			return false;
		}
		if(confirm("确认要删除考点吗?")){
			exams.post(U("exams/AdminPoint/deletePoint"),{point_id:point_id},function(res){
				if(res.status == 1){
					ui.success(res.data.info);
					exams.windowReload();
				}else{
					ui.error(res.message);
					return false;
				}
			});
		}
	},
	/**
	 * 删除试题
	 * @Author MartinSun<syh@sunyonghong.com>
	 * @Date   2017-10-17
	 * @param  {[type]} question_id [description]
	 * @return {[type]} [description]
	 */
	deleteQuestion:function(question_id){
		if(!question_id){
			ui.error('请选择需要删除的试题');
			return false;
		}
		if(confirm("确认要删除试题吗?")){
			exams.post(U("exams/AdminQuestion/deleteQuestion"),{question_id:question_id},function(res){
				if(res.status == 1){
					ui.success(res.data.info);
					exams.windowReload();
				}else{
					ui.error(res.message);
					return false;
				}
			});
		}
	},
	/**
	 * 删除试题类型
	 * @Author MartinSun<syh@sunyonghong.com>
	 * @Date   2017-10-17
	 * @param  {[type]} question_type_id [description]
	 * @return {[type]} [description]
	 */
	deleteQuestionType:function(question_type_id){
		if(!question_type_id){
			ui.error('请选择需要删除的试题类型');
			return false;
		}
		if(confirm("确认要删除试题类型吗?")){
			exams.post(U("exams/AdminCategory/deleteQuestionType"),{question_type_id:question_type_id},function(res){
				if(res.status == 1){
					ui.success(res.data.info);
					exams.windowReload();
				}else{
					ui.error(res.message);
					return false;
				}
			});
		}
	},
	/**
	 * 删除试卷
	 * @Author MartinSun<syh@sunyonghong.com>
	 * @Date   2017-10-17
	 * @param  {[type]} paper_id [description]
	 * @return {[type]} [description]
	 */
	deletePaper:function(paper_id){
		if(!paper_id){
			ui.error('请选择需要删除的试卷');
			return false;
		}
		if(confirm("确认要删除试卷吗?")){
			exams.post(U("exams/AdminPaper/deletePaper"),{paper_id:paper_id},function(res){
				if(res.status == 1){
					ui.success(res.data.info);
					exams.windowReload();
				}else{
					ui.error(res.message);
					return false;
				}
			});
		}
	},
	/**
	 * 导出试卷
	 * @Author MartinSun<syh@sunyonghong.com>
	 * @Date   2017-10-25
	 * @param  {[type]} explod [description]
	 * @return {[type]} [description]
	 */
	exportExams:function(explod){
		if(explod ==''){
			ui.error( "暂无试卷可以导出" );return false;
		}
		location.href = U('exams/AdminExamsUser/export')+"&explod="+explod;
	},
	/**
	 * 批量删除
	 * @Author MartinSun<syh@sunyonghong.com>
	 * @Date   2017-10-25
	 * @return {[type]} [description]
	 */
	batchDelete:function(callback){
		var checkboxObj = $("#list input[name='checkbox']:checked");
		var ids = [];
		checkboxObj.each(function(i,v){
			ids.push($(this).val());
		});
		switch(callback){
			case 'deleteQuestion':
				return exams.deleteQuestion(ids);
			case 'deletePaper':
				return exams.deletePaper(ids);
			default:
				break;
		}
		if($.isFunction(callback)){
			return callback(ids);
		}
		return ids;

	}
}

var exams = new exams();