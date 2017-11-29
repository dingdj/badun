<?php
/**
 * 点评管理配置
 * @author ashangmanage <ashangmanage@phpzsm.com>
 * @version CY1.0
 */
tsload(APPS_PATH.'/admin/Lib/Action/AdministratorAction.class.php');
class AdminReviewAction extends AdministratorAction
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
	 * 点评列表管理
	 * @return void
	 */
	public function index()
	{
		// 页面具有的字段，可以移动到配置文件中！！！
        $this->pageKeyList = array(
			'id','uid','review_description','type','parent_id','oid','star',
			'review_vote_count','review_comment_count','review_source','ctime','DOACTION'
		);

        $this->pageButton[] = array('title'=>'搜索','onclick'=>"admin.fold('search_form')");
        $this->pageButton[] = array('title' => '删除', 'onclick' => "admin.delReviewAll('delReview')");
		
		$this->searchKey = array('id','uid','type','star','review_description','oid',array('ctime','ctime1'));
		$this->opt['type']    = array('0'=>'不限','1'=>'课程','2'=>'班级','3'=>'线下课','4'=>'讲师');
		$this->opt['star']    = array('0'=>'不限','1'=>'1星','2'=>'2星','3'=>'3星','4'=>'4星','5'=>'5星');
		
        $list = model('ZyReview')->getReviewList('20',array('parent_id'=>array('eq',0)));
		
		foreach($list['data'] as $key=>$value) {
            $list['data'][$key]['uid'] = getUserSpace($value['uid'], null, '_blank');
            $list['data'][$key]['star'] = intval($value['star'] / 20);
            $video_type = D('ZyVideo','classroom')->where('id='.$value['oid'])->getField('type');

            if ($value['type'] == 1) {
                $video_title = D('ZyVideo','classroom')->getVideoTitleById($value['oid']);
                if($video_type == 1){
                    $url = U('classroom/Video/view', array('id' => $value['oid']));
                    $type = "未知课程";
                }else{
                    $url = U('live/Index/view', array('id' => $value['oid']));
                    $type = "未知直播";
                }
                $list['data'][$key]['oid'] = getQuickLink($url,$video_title,$type);
            } else if ($value['type'] == 2) {
                $url = U('classroom/Album/view', array('id' => $value['oid']));
                $type = "未知班级";
                $list['data'][$key]['oid'] = getQuickLink($url,getAlbumNameForID($value['oid']),$type);
            } else if ($value['type'] == 3){
                $title = M('zy_teacher_course')->where('course_id='.$value['oid'])->getField('course_name');
                $url = U('classroom/LineClass/view', array('id' => $value['oid']));
                $type = "未知线下课";
                $list['data'][$key]['oid'] = getQuickLink($url,$title,$type);
            } else if ($value['type'] == 4){
                $name = M('zy_teacher')->where('id='.$value['oid'])->getField('name');
                $url = U('classroom/Teacher/view', array('id' => $value['oid']));
                $type = "未知讲师";
                $list['data'][$key]['oid'] = getQuickLink($url,$name,$type);
            }
            $list['data'][$key]['review_description'] = '<a href="' . $url . '" target="_bank">' . $value['review_description'] . '</a>';

         if($value['type'] == 1) {
             $list['data'][$key]['type'] = '课程' ;
         } else if($value['type'] == 2) {
             $list['data'][$key]['type'] = '班级' ;
         } else if($value['type'] == 3) {
             $list['data'][$key]['type'] = '线下课' ;
         } else if($value['type'] == 4) {
                $list['data'][$key]['type'] = '讲师' ;
            }
            $list['data'][$key]['ctime'] = date('Y-m-d', $value['ctime']);
            if($value['is_del'] == 1) {
                $list['data'][$key]['DOACTION'] = '  <a href="javascript:admin.mzReviewEdit(' . $value['id'] . ',\'closereview\',\'显示\',\'点评\','.$value['uid'].',' ."'{$value['review_description']}'".' ,'.$value['ctime'].');">显示</a>';
            }
            else {
				$list['data'][$key]['DOACTION'] .= ' <a href="javascript:admin.mzReviewEdit('.$value['id'].',\'closereview\',\'隐藏\',\'点评\','.$value['uid'].','."'{$value['review_description']}'".','.$value['ctime'].');">隐藏</a>';

			}
            $list['data'][$key]['DOACTION'] .= ' | <a href="javascript:admin.delReview('.$value['id'].',\'delreview\','.$value['uid'].','.$value['ctime'].','."'{$value['review_description']}'".','.$value['ctime'].');">删除</a>';
            if ($value['type'] == 1) {
                $list['data'][$key]['DOACTION'] .= ' | <a href="' . U('classroom/AdminVideo/reviewCommentVideo', array('oid' => $value['oid'], 'id' => $value['id'], 'tabHash' => 'reviewCommentVideo')) . '">查看回复</a>';
            } else if ($value['type'] == 2){
                $list['data'][$key]['DOACTION'] .= ' | <a href="' . U('classroom/AdminAlbum/reviewCommentAlbum', array('oid' => $value['oid'], 'id' => $value['id'], 'tabHash' => 'reviewCommentAlbum')) . '">查看回复</a>';
            } else if ($value['type'] == 3){
                $list['data'][$key]['DOACTION'] .= ' | <a href="' . U('classroom/AdminLineClass/reviewCommentAlbum', array('oid' => $value['oid'], 'id' => $value['id'], 'tabHash' => 'reviewCommentAlbum')) . '">查看回复</a>';
            } else if ($value['type'] == 4){
                $list['data'][$key]['DOACTION'] .= ' | <a href="' . U('classroom/AdminTeacher/reviewCommentAlbum', array('oid' => $value['oid'], 'id' => $value['id'], 'tabHash' => 'reviewCommentAlbum')) . '">查看回复</a>';
            }
            if (!empty($_POST['oid'])) {
                if (!strstr($list['data'][$key]['oid'], $_POST['oid'])) {
                    unset($list['data'][$key]);
                }
            }
        }
		$this->assign('pageTitle','点评管理');
        $this->_listpk = 'id';
        $this->allSelected = true;
        array_values($list);
        $this->displayList($list);
	}

	/**
	 * 删除点评
	 * @return void
	 */
	public function delreview()
	{

        $ids = implode(",", $_POST['ids']);

        $ids = trim(t($ids), ",");
        if ($ids == "") {
            $ids = intval($_POST['ids']);
        }
		$return =  D('ZyReview')->doDeleteReview($_POST['ids']);
		
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


    /**
     * 显示/隐藏笔记
     */
    public function closereview()
    {
        $id = implode(",", $_POST['id']);
        $id = trim(t($id), ",");
        if ($id == "") {
            $id = intval($_POST['id']);
        }
        $msg = array();
        $where = array(
            'id' => array('in', $id)
        );
        $is_del = M('zy_review')->where($where)->getField('is_del');
        if ($is_del == 1) {
            $data['is_del'] = 0;
        } else {
            $data['is_del'] = 1;
        }
        $res = M('zy_review')->where($where)->save($data);

        if ($res !== false) {
            $msg['data'] = '操作成功';
            $msg['status'] = 1;
            echo json_encode($msg);
        } else {
            $msg['data'] = "操作失败!";
            $msg['status'] = 0;
            echo json_encode($msg);
        }
    }
}