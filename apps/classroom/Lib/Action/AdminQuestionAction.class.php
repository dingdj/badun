<?php
/**
 * 提问管理配置
 * @author ashangmanage <ashangmanage@phpzsm.com>
 * @version CY1.0
 */
tsload(APPS_PATH.'/admin/Lib/Action/AdministratorAction.class.php');
class AdminQuestionAction extends AdministratorAction
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
	 * 提问列表管理
	 * @return void
	 */
	public function index()
	{
		// 页面具有的字段，可以移动到配置文件中！！！
        $this->pageKeyList = array(
			'id','uid','qst_title','qst_description','type','parent_id','oid',
			'qst_help_count','qst_comment_count','qst_source','ctime','DOACTION'
		);

		$this->pageButton[] = array('title'=>'搜索','onclick'=>"admin.fold('search_form')");
        $this->pageButton[] = array('title' => '删除', 'onclick' => "admin.mzQuestionEdit('delquestion')");
		
		$this->searchKey = array('id','uid','type','qst_title',array('ctime','ctime1'));
		$this->opt['type']    = array('0'=>'不限','1'=>'课程','2'=>'班级');
		
        $list = model('ZyQuestion')->getQuestionList('20',array('parent_id'=>array('eq',0)));
		
		foreach($list['data'] as $key=>$value){
			$list['data'][$key]['uid']      = getUserSpace($value['uid'], null, '_blank');
			if($value['type']==1){
				$url = U('classroom/Video/view', array('id'=>$value['oid']));
			}else{
				$url = U('classroom/Album/view', array('id'=>$value['oid']));
			}
			$list['data'][$key]['qst_title']  = '<a href="'.$url.'" target="_bank">'.$value['qst_title'].'</a>';
			$list['data'][$key]['qst_description']  = $value['qst_description'];

			if($value['type']==1){
				$list['data'][$key]['oid']  = getVideoNameForID($value['oid']);
			}else if($value['type']==2){
				$list['data'][$key]['oid']  = getAlbumNameForID($value['oid']);
			}else{
				$list['data'][$key]['oid']  = '不存在';
			}
//			$list['data'][$key]['oid'] = '<div style="width:160px;height:30px;overflow:hidden;">'.$list['data'][$key]['oid'].'</div>';
			$video_title = D('ZyVideo','classroom')->getVideoTitleById($value['oid']);
			$url = U('classroom/Video/view', array('id' => $value['oid']));
			$list['data'][$key]['oid'] = getQuickLink($url,$video_title,"未知课程");

			$list['data'][$key]['type']     = ($value['type']==1)?'课程':'班级';
			$list['data'][$key]['ctime']    = date('Y-m-d',$value['ctime']);
            $list['data'][$key]['DOACTION'] = ' <a href="javascript:admin.mzdelquest('.$value['id'].',\'delquestion\',' ."'{$value['qst_description']}'".','.$value['uid'].','.$value['ctime'].');">删除</a>';
			if($value['type'] == 1){
				$list['data'][$key]['DOACTION'] .= ' | <a href="'.U('classroom/AdminVideo/answerVideo',array('oid'=>$value['oid'],'id'=>$value['id'],'tabHash'=>'answerVideo')).'">查看回答</a>';
			} else {
				$list['data'][$key]['DOACTION'] .= ' | <a href="'.U('classroom/AdminAlbum/answerAlbum',array('oid'=>$value['oid'],'id'=>$value['id'],'tabHash'=>'answerAlbum')).'">查看回答</a>';
			}
		}
		$this->assign('pageTitle','提问管理');
        $this->_listpk = 'id';
        $this->allSelected = true;	
        $this->displayList($list);
	}

	/**
	 * 删除提问
	 * @return void
	 */
	public function delquestion()
	{
        $ids = implode(",", $_POST['ids']);
        $ids = trim(t($ids), ",");
        if ($ids == "") {
            $ids = intval($_POST['ids']);
        }
		$return =  model('ZyQuestion')->doDeleteQuestion($_POST['ids']);

		if($return['status'] == 1){
			$return['data'] = "操作成功";
		}elseif($return['status'] === false){
			$return['data'] = L('PUBLIC_DELETE_FAIL');
		}elseif($return['status'] == 100003){
			$return['data'] = '请选择要删除的内容';
		}else{
			$return['data'] = '操作错误';	
		}
		echo json_encode($return);exit();
	}


}