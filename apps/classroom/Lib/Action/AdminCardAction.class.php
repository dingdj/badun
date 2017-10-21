<?php
/**
 * 银行卡号信息管理配置
 * @author ashangmanage <ashangmanage@phpzsm.com>
 * @version CY1.0
 */
tsload(APPS_PATH.'/admin/Lib/Action/AdministratorAction.class.php');
class AdminCardAction extends AdministratorAction
{
	
	/**
	 * 初始化，配置内容标题
	 * @return void
	 */
	public function _initialize()
	{
		parent::_initialize();
	}

	/**
	 * 银行卡号列表管理
	 * @return void
	 */
	public function index()
	{
		// 页面具有的字段，可以移动到配置文件中！！！
        $this->pageKeyList = array('id','uid','accountmaster','accounttype','account','location','bankofdeposit','tel_num','is_school','DOACTION');

		$this->pageButton[] = array('title'=>'搜索','onclick'=>"admin.fold('search_form')");
		$this->pageButton[] = array('title'=>'删除','onclick'=>"admin.BankCardEdit('','delbackcard','删除','银行卡号')");
		
		$this->searchKey = array('uid','account','accountmaster','is_school');
		$this->opt['is_school'] = array('0'=>'不限','1'=>'用户','2'=>'机构',);

		if(isset($_POST)){
			$is_school = intval(t($_POST['is_school']));
			if($is_school == 1){
				$map['is_school'] = 0;
			}else if($is_school == 2){
				$map['is_school'] = 1;
			}
		}

        $list = model('ZyBcard')->getBCardList(20,$map);
		foreach($list['data'] as $key=>$value){
			$list['data'][$key]['uid']      = getUserSpace($value['uid'], null, '_blank');
			if($value['is_school'] == 1){
				$list['data'][$key]['is_school'] = '机构';
			}else{
				$list['data'][$key]['is_school'] = '用户';
			}
			$list['data'][$key]['DOACTION'] = '<a href="javascript:admin.BankCardEdit('.$value['id'].',\'delbackcard\',\'删除\',\'银行卡号\');">删除</a>';
		}
		
        $this->_listpk = 'id';
        $this->allSelected = true;
		
		$this->assign('pageTitle','卡号列表');
		
        $this->displayList($list);
	}

	/**
	 * 删除银行卡号
	 * @return void
	 */
	public function delbackcard()
	{
		$return =  model('ZyBcard')->doDeleteBankCard($_POST['id']);
		
		if($return['status'] == 1){
			$return['data'] = L('PUBLIC_DELETE_SUCCESS');
		}elseif($return['status'] === false){
			$return['data'] = L('PUBLIC_DELETE_FAIL');
		}elseif($return['status'] == 100001){
			$return['data'] = '存在提现记录';
		}elseif($return['status'] == 100003){
			$return['data'] = '请选择要删除的内容';
		}else{
			$return['data'] = '操作错误';	
		}
		echo json_encode($return);exit();
	}


}