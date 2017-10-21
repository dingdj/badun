<?php
/**
 * 课程卡管理
 * @author ashangmanage <ashangmanage@phpzsm.com>
 * @version CY1.0
 */
tsload(APPS_PATH.'/admin/Lib/Action/AdministratorAction.class.php');
class AdminVideoCardAction extends AdministratorAction{
    /**
     * 初始化，配置内容标题
     * @return void
     */
    public function _initialize()
    {
        $this->pageTitle['index']      = '课程卡列表';

        $this->pageTab [] = array ('title' => '课程卡列表','tabHash' => 'index','url' => U ( 'classroom/AdminVideoCard/index' ));
        $this->pageTab [] = array ('title' => '添加课程卡','tabHash' => 'addCard','url' => U ( 'classroom/AdminVideoCard/addCard' ));

        parent::_initialize();
    }


    public function index(){
        $id     =  intval($_POST['id']);
        $uid    =  intval($_POST['uid']);
        $vid    =  intval($_POST['vid']);
        $use_id =  intval($_POST['use_id']);
        $title  =  t($_POST['title']);
        $_REQUEST['tabHash'] = 'index';

        //页面配置
        $this->pageKeyList = array('id','creator','title','video_title','use_id','ctime','is_use','is_del','DOACTION');
        $this->searchKey = array('id','uid','vid','use_id','title');
        $this->pageButton[] = array('title'=>'搜索','onclick'=>"admin.fold('search_form')");
        $map=[];
        if(!empty($id))$map['id']=$id;
        if(!empty($uid))$map['uid']=$uid;
        if(!empty($vid))$map['vid']=$vid;
        if(!empty($use_id))$map['use_id']=$use_id;
        if(!empty($title))$map['title']=array("like","%$title%");
        //数据列表
        $listData = M('zy_video_card')->where($map)->order('ctime DESC,id DESC')->findPage();
        foreach($listData['data'] as $key=>$val){
            $val['creator']  = getUserSpace($val['uid']);
            $val['video_title'] = D('ZyVideo')->where(array('id'=>$val['vid']))->getField('video_title');
            $val['ctime']    = date("Y-m-d H:i:s", $val['ctime']);
            if($val['is_use'] == 0){
                $val['is_use'] = "<span style='color: green;'>未使用</span>";
            }else if($val['is_use'] == 1){
                $val['is_use'] = "<span style='color: red;'>已使用</span>";
            }

            if($val['is_del'] == 1) {
               $val['DOACTION'] ='<a href="javascript:admin.mzVideoCardEdit('.$val['id'].',\'closeCard\',\'启用\',\'课程卡\');">启用</a>';
            }else {
                $val['DOACTION'] ='<a href="javascript:admin.mzVideoCardEdit('.$val['id'].',\'closeCard\',\'禁用\',\'课程卡\');">禁用</a>';
            }
            
            if($val['is_del'] == 0){
                $val['is_del'] = "<span style='color: green;'>正常</span>";
            }else if($val['is_del'] == 1){
                $val['is_del'] = "<span style='color: red;'>禁用</span>";
            }
            $val['DOACTION'].=" | <a href=".U('classroom/AdminVideoCard/addCard',array('id'=>$val['id'],'tabHash'=>'revise')).";>编辑</a>";
            $listData['data'][$key] = $val;
        }
        $this->_listpk = 'id';
        $this->displayList($listData);
    }

     /**
     * 添加课程卡
     * Enter description here ...
     */
    public function addCard(){
        $id   = intval($_GET['id']);
        $this->pageKeyList = array ('title','vid');
        $this->notEmpty = array ('title','vid');
        if($id){
            $this->savePostUrl = U ( 'classroom/AdminVideoCard/doAddCard','type=save&id='.$id);
            $zyVideoCard = M('zy_video_card')->where( 'id='.$id  )->find ();
            $this->assign('pageTitle','修改课程卡');
            //说明是编辑
            $this->displayConfig($zyVideoCard);
        }else{
            $this->savePostUrl = U ('classroom/AdminVideoCard/doAddCard','type=add');
            $this->assign('pageTitle','添加课程卡');
            //说明是添加
            $this->displayConfig();
        }
    }
    
    /**
     * 处理添加讲师
     * Enter description here ...
     */
    public function doAddCard(){
        $id=intval($_GET['id']);
        $type= t($_GET['type']);
        //要添加的数据
        //数据验证
        if(!$_POST['title']){
            $this->error('机构不能为空!');
        }
        if(!$_POST['vid']){
            $this->error('课程不能为空!');
        }
        
        $map=array( 
                'uid'=>intval($this->mid),
                'title'=>t($_POST['title']),
                'vid'=>intval($_POST['vid']),
                'ctime'=>time(),
                );
        if($type == 'add'){
            $res=M('zy_video_card')->add($map);
            if(!$res)$this->error("对不起，添加失败！");
            $this->success("添加课程卡成功！");
        }else if($type=='save' && $id){
            $res=M('zy_video_card')->where("id=$id")->save($map);
            if(!$res)$this->error("对不起，修改失败！");
            $this->success("修改课程卡成功!");
        }
    }
    /**
     * 禁用/启用 课程卡
     */
    public function closeCard()
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
        $is_del = M('zy_video_card')->where($where)->getField('is_del');
        if ($is_del == 1) {
            $data['is_del'] = 0;
        } else {
            $data['is_del'] = 1;
        }
        $res = M('zy_video_card')->where($where)->save($data);

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
	/**
     * 查询课程名称
     */
	 public function getVideoTitle(){
		 $video_id =intval($_POST['video_id']);
		 $video_title = D('ZyVideo','classroom')->getVideoTitleById($video_id);
		 $res=[];
		 if($video_title){
			$res['status'] = 1;
			$res['data'] = $video_title;
		 }else{
			$res['status'] = 0;
			$res['message'] = '未找到该课程'; 
		 }
		 echo json_encode($res);exit;
	 }
}
?>