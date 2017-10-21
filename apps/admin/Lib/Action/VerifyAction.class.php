<?php
/**
 * 验证码管理
 */
// 加载后台控制器
tsload ( APPS_PATH . '/admin/Lib/Action/AdministratorAction.class.php' );
class VerifyAction extends AdministratorAction {	

	/**
	 * 初始化，配置内容标题
	 * @return void
	 */
	public function _initialize(){
		$this->pageTitle ['index'] = '验证码管理';

		parent::_initialize();
	}
	
	/**
	 * 验证码列表
	 */
	public function index(){
		$this->pageKeyList = array ('id', 'phone', 'code', 'stime', 'DOACTION');
        $this->pageButton[] = array('title'=>'删除','onclick'=>"admin.delVerify()");

        $list = M('resphone_code')->order('stime desc')->findPage(20);
		foreach($list['data'] as &$val){
			$val['stime'] = date('Y-m-d H:i' , $val['stime']);
			$val['DOACTION'] = '<a href="javascript:;" onclick="admin.delVerify(\''.$val['id'].'\')">彻底删除</a>';
		}
		$this->displayList($list);
	}

    /**
     * 删除验证码操作
     */
    public function delVerify(){
        if(is_array($_POST['id'])){
            $_POST['id'] = implode(',', $_POST['id']);
        }
        $id = "'".str_replace(array("'", ','), array('', "','"), $_POST['id'])."'";

        if(!$id){
            $this->ajaxReturn(null,"参数错误",0);
        }
		$res = M('resphone_code')->where("id IN($id)")->delete();
		if($res){
			$this->ajaxReturn(null,"删除成功",1);
		}else{
			$this->ajaxReturn(null,"删除失败",0);
		}
    }

}