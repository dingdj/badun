<?php
/**
 * 云课堂后台配置
 * 1.课程管理 - 目前支持1级分类
 * @author ashangmanage <ashangmanage@phpzsm.com>
 * @version CY1.0
 */
tsload(APPS_PATH.'/admin/Lib/Action/AdministratorAction.class.php');
require_once './api/qiniu/rs.php';
require_once './api/cc/notify.php';

class AdminLineClassAction extends AdministratorAction
{
    /**
     * 初始化，配置内容标题
     * @return void
     */
    public function _initialize(){
        parent::_initialize();
    }

    //通过审核课程列表
    public function index(){
        $this->_initClassroomListAdminMenu();
        $this->_initClassroomListAdminTitle();
        $this->pageKeyList = array('course_id','course_name','cover','course_price','user_title','teacher_name','course_order_count','activity','is_charge','ctime','DOACTION');
        $this->pageButton[] =  array('title'=>'搜索','onclick'=>"admin.fold('search_form')");
        $this->pageButton[]  = array('title'=>'禁用','onclick'=>"admin.delLineClassAll('LineClass','delLineClassAll',1)");
        $this->searchKey = array('course_id','course_name','course_uid','teacher_name');
        $this->searchPostUrl = U('classroom/AdminLineClass/index',array('tabHash'=>index));
        $listData = $this->_getData(20,0,1);
        $this->_listpk = 'course_id';
        $this->displayList($listData);
    }

    //待审核课程列表
    public function unauditList(){
        $this->_initClassroomListAdminMenu();
        $this->_initClassroomListAdminTitle();
        $this->pageButton[] = array("title"=>"批量审核","onclick"=>"admin.crossVideos('','crossVideos','批量审核','课程')");
        $this->pageKeyList = array('course_id','course_name','cover','course_price','user_title','teacher_name','course_order_count','activity','is_charge','ctime','DOACTION');
        $listData = $this->_getData(20,0,0);
        $this->displayList($listData);
    }

    //课程回收站(被隐藏的课程)
    public function recycle(){
        $this->_initClassroomListAdminMenu();
        $this->_initClassroomListAdminTitle();
        $this->pageButton[] = array("title"=>"彻底删除","onclick"=>"admin.delLineClassAll('LineClass','delLineClassAll',2)");
        $this->pageKeyList = array('course_id','course_name','cover','course_price','user_title','teacher_name','course_order_count','activity','is_charge','ctime','DOACTION');
        $listData = $this->_getData(20,1,1);
        $this->_listpk = 'course_id';
        $this->displayList($listData);
    }

    //编辑、添加课程
    public function addVideo(){
        $this->_initClassroomListAdminMenu();
        $this->_initClassroomListAdminTitle();
        if($_GET['id']){
            $data = M('zy_teacher_course')->where('course_id='.$_GET['id'])->find();
            $data['cover_path'] = getAttachUrlByAttachId($data['cover']);
            $this->assign($data);
            $this->assign('id',$_GET['id']);
            //获取机构
            $school_title = model('School')->where('id='.$data['mhm_id'])->getField('title');
            $this->assign('school_title' , $school_title);
            $mhm_id = $data['mhm_id'];
        }else{
            $this->assign("listingtime",time());
            $this->assign("uctime",time()+604800);
            $this->assign("course_intro","这是课程详情。。");
            //获取机构
            $school_title = model('School')->where('id=1')->getField('title');
            $this->assign('school_title' , $school_title);
            $mhm_id = 1;
        }

        //查询讲师列表
        $trlist = $this->teacherList($mhm_id);
        //获取会员等级
        $vip_levels = M('user_vip')->where('is_del=0')->order('sort desc')->getField('id,title');

        $this->assign('vip_levels' , $vip_levels);
        $this->assign('trlist' , $trlist);
        $this->assign('mhm_id' , $mhm_id);
        $this->display();
    }

    //添加课程操作
    public function doAddVideo(){

        $post = $_POST;
        if(empty($post['course_name'])) exit(json_encode(array('status'=>'0','info'=>"请输入课程标题")));
        if(empty($post['video_levelhidden'])) exit(json_encode(array('status'=>'0','info'=>"请选择课程分类")));
        if(empty($post['course_binfo'])) exit(json_encode(array('status'=>'0','info'=>"请输入课程简介")));
        if(empty($post['course_intro'])) exit(json_encode(array('status'=>'0','info'=>"请输入课程详情介绍")));
        /*if(empty($post['mhm_id'])) exit(json_encode(array('status'=>'0','info'=>"课程所属机构不能为空")));*/
        if(empty($post['cover_ids'])) exit(json_encode(array('status'=>'0','info'=>"请上传课程封面")));
        if(empty($post['trid'])) exit(json_encode(array('status'=>'0','info'=>"请选择讲师")));
        if(empty($post['listingtime'])) exit(json_encode(array('status'=>'0','info'=>"请选择上架时间")));
        if(empty($post['uctime'])) exit(json_encode(array('status'=>'0','info'=>"请选择下架时间")));
        if(empty($post['course_price'])) exit(json_encode(array('status'=>'0','info'=>"价格不能为空")));
        if(!is_numeric($post['course_price']) || preg_match("/^-[0-9]+(\.[0-9]+)?$/",$post['course_price']))exit(json_encode(array('status'=>'0','info'=>"价格格式错误")));
        //if(ereg("^[0-9]*[1-9][0-9]*$",$_POST['course_price'])!=1) exit(json_encode(array('status'=>'0','info'=>"课程价格必须为正整数")));
        /*$t_mhm_id = M('zy_teacher')->where(['id'=>$post['trid']])->getField('mhm_id');
        $mhm_id =  M('zy_teacher_course')->where('course_id = '.$post['id'])->getField('mhm_id');
        $v_mhm_id = $mhm_id ? $mhm_id : is_school($this->mid);
        if($v_mhm_id != $t_mhm_id){
            exit(json_encode(array('status'=>'0','info'=>"讲师所属机构和课程所属机构不一致")));
        }*/
        $data['listingtime']  = $post['listingtime'] ? strtotime($post['listingtime']) : 0; //开课时间
        $data['uctime'] 	  = $post['uctime'] ? strtotime($post['uctime']) : 0; //结束时间
        if( $data['uctime'] < $data['listingtime'] ){
            exit(json_encode(array('status'=>'0','info'=>'开课时间不能小于结束时间')));
        }

        $myAdminLevelhidden 		= getCsvInt(t($post['video_levelhidden']),0,true,true,',');  //处理分类全路径
        $fullcategorypath 			= explode(',',$post['video_levelhidden']);
        $category 					= array_pop($fullcategorypath);
        $category					= $category == '0' ? array_pop($fullcategorypath) : $category; //过滤路径最后为0的情况
        $data['fullcategorypath'] 	= $myAdminLevelhidden; //分类全路径
        $data['course_category']	= $category == '0' ? array_pop($fullcategorypath) : $category;
        $data['course_name'] 		= t($post['course_name']); //课程名称
        $data['course_binfo'] 		= $post['course_binfo']; //课程简介
        $data['course_intro'] 		= $post['course_intro']; //课程详情介绍
        $data['mhm_id'] 			= intval($_POST['mhm_id']); //机构id
        $data['course_price']       = $post['course_price']; //市场价格
        $data['teacher_id']         = intval($_POST['trid']);//获取讲师
        $data['cover'] 			 	= intval($post['cover_ids']); //封面
        $data['is_charge'] 			= intval($post['is_charge']); //免费
        $data['is_activity']        = 1;
        $data['ctime']              = time();

        if($post['id']){
            $is_activity = M('zy_teacher_course')->where('course_id = '.$post['id'])->getField('is_activity');
            if($is_activity == -1){
                $data['is_activity']     = 0;
            }
            $result = M('zy_teacher_course')->where('course_id = '.$post['id'])->data($data)->save();
        } else {
            $data['course_uid']   = $this->mid;
            $result = M('zy_teacher_course')->data($data)->add();
        }
        if($result){
            if($post['id']){
                exit(json_encode(array('status'=>'1','info'=>'编辑成功')));
            } else {
                exit(json_encode(array('status'=>'1','info'=>'添加成功')));
            }
        } else {
            exit(json_encode(array('status'=>'0','info'=>'操作失败，请检查数据是否完整')));
        }
    }

    //批量审核课程
    public function crossVideos(){
        $map['id'] = is_array($_POST['id']) ? array('IN',$_POST['id']) : intval($_POST['id']);
        $table = M('zy_video');
        $data['is_activity']  = 1;
        $result = $table->where($map)->data($data)->save();
        if($result){
            $this->ajaxReturn('审核成功');
        } else {
            $this->ajaxReturn('系统繁忙，稍后再试');
        }
    }

    //删除(隐藏)课程
    public function delVideo(){
        if(!$_POST['id']){
            exit(json_encode(array('status'=>0,'info'=>'请选择要删除的对象!')));
        }
        $map['id'] = intval($_POST['id']);
        $data['is_del'] = $_POST['is_del'] ? 0 : 1; //传入参数并设置相反的状态
        if(M('zy_video')->where($map)->data($data)->save()){
            exit(json_encode(array('status'=>1,'info'=>'操作成功')));
        } else {
            exit(json_encode(array('status'=>1,'info'=>'操作失败')));
        }
    }

    //讲师列表
    private function teacherList($mhm_id = 1){
        $map['is_del'] = 0;
        $map['mhm_id'] = $mhm_id;
        $teacherlist = D('ZyTeacher')->where($map)->order("ctime DESC")->select();
        return $teacherlist;
    }

    //获取课程数据
    private function _getData($limit = 20, $is_del = 0, $is_activity = 1){
        if(isset($_POST)){
            $_POST['course_id'] && $map['course_id'] = intval($_POST['course_id']);
            $_POST['teacher_name'] && $map['teacher_id'] = M('zy_teacher')->where(array('name'=>t($_POST['teacher_name'])))->getField('id');
            $_POST['course_name'] && $map['course_name'] = array('like', '%'.t($_POST['course_name']).'%');
            $_POST['course_uid'] && $map['course_uid'] = intval($_POST['course_uid']);
        }
        if($is_del == 1) {
            $map['is_del'] = $is_del; //搜索非隐藏内容
        }else{
            $map['is_del'] = 0;
        }
        if(isset($is_activity) && $is_activity != 3){
            $map['is_activity'] = $is_activity;
        }
        $list = M('zy_teacher_course')->where($map)->order('ctime desc')->findPage($limit);
        foreach ($list['data'] as &$value){
            $value['course_name'] = msubstr($value['course_name'],0,20);
            $url = U('classroom/LineClass/view', array('id' => $value['course_id']));
            $value['course_name'] = getQuickLink($url,$value['course_name'],"未知课程");
            $value['user_title']  = getUserSpace($value['course_uid'], null, '_blank');
            $value['activity']    = $value['is_activity'] == '1' ? '<span style="color:green">已审核</span>' : '<span style="color:red">未审核</span>';
            //处理讲师信息
            $teacher_name = M('zy_teacher')->where(array('id'=>$value['teacher_id']))->getField('name');
            $value['teacher_name']  = getQuickLink(U('classroom/Teacher/view',['id'=>$value['teacher_id']]),$teacher_name,'未知讲师');

            $value['ctime'] = friendlyDate($value['ctime']);
            $value['cover'] = "<img src=".getCover($value['cover'] , 60 ,60)." width='60px' height='60px'>";
            if($value['is_charge'] == 0){
                $value['is_charge'] = "<p style='color: red;'>否</p>";
            }else if($value['is_charge'] == 1){
                $value['is_charge'] = "<p style='color: green;'>是</p>";
            }

            if($value['is_activity'] == 1){
                $value['DOACTION'] .= '<a target="_blank" href=" '.U('classroom/LineClass/view',array('id'=>$value['course_id'])).' ">查看</a> | ';
                $value['DOACTION'] .= '<a href="'.U('classroom/AdminLineClass/reviewVideo',array('tabHash'=>'reviewVideo','id'=>$value['course_id'])).'">评价</a> | ';
                $value['DOACTION'] .='<a href="'.U('classroom/AdminLineClass/addVideo',array('id'=>$value['course_id'],'tabHash'=>'editVideo')).'">编辑</a> |';
                if($value['is_del'] == 1) {
                    $value['DOACTION'] .=   '<a onclick="admin.openObject('.$value['course_id'].',\'LineClass\','.$value['is_del'].');" href="javascript:void(0)">启用</a> ';
                }else if($value['is_del'] == 0) {
                    $value['DOACTION'] .=   '<a onclick="admin.closeObject('.$value['course_id'].',\'LineClass\','.$value['is_del'].');" href="javascript:void(0)">禁用</a> ';
                }
            }else{
                $value['activity'] = '<span style="color:green;">等待审核</span>';
                $value['DOACTION'] .= '<a href="javascript:void();" onclick="admin.activeVideo('.$value['course_id'].',\'AdminLineClass\',true)">通过审核</a> | ';
                $value['DOACTION'] .= '<a href="javascript:void();" onclick="admin.activeVideo('.$value['course_id'].',\'AdminLineClass\',false)">驳回</a>  | ';
                $value['DOACTION'] .=  ' | <a href="'.U('classroom/LineClass/view',array('id'=>$value['id'],'is_look'=>1)).'" target="_blank">查看课程</a> ';
            }
        }
        return $list;
    }

    /**
     * 启用课程
     */
    public function openLineClass()
    {
        $id = intval($_POST['id']);
        if(!$id){
            $this->ajaxReturn(null,'参数错误',0);
        }

        $map['course_id'] = $id;
        $data['is_del']= 0;
        $video =M('zy_teacher_course');
        $result = $video ->where($map)-> save($data);
        if(!$result)
        {
            $this->ajaxReturn(null,'启用失败',0);
            return;
        }

        $this->ajaxReturn(null,'启用成功',1);
    }

    /**
     * 批量禁用课程
     */
    public function delLineClassAll(){
        $ids = implode(",",$_POST['ids']);
        $ids = trim(t($ids),",");
        if($ids==""){
            $ids=intval($_POST['ids']);
        }
        $msg = array();
        $where = array(
            'course_id'=>array('in',$ids)
        );

        $data['is_del'] = intval($_POST['status']);
        $res = M('zy_teacher_course')->where($where)->save($data);
        if( $res !== false){
            $msg['data']   = '操作成功';
            $msg['status'] = 1;
            echo json_encode($msg);
        }else{
            $msg['data'] = "操作失败!";
            $msg['status'] = 0;
            echo json_encode($msg);
        }
    }
    /**
     * 禁用课程
     */
    public function closeLineClass()
    {
        $id = intval($_POST['id']);
        if(!$id){
            $this->ajaxReturn(null,'参数错误',0);
        }

        $map['course_id'] = $id;
        $data['is_del']= 1;
        $video =M('zy_teacher_course');
        $result = $video ->where($map)-> save($data);
        if(!$result)
        {
            $this->ajaxReturn(null,'禁用失败',0);
            return;
        }

        $this->ajaxReturn(null,'禁用失败',1);
    }

    /*******************************************笔记操作结束,评论开始******************/
    /**
     * 课程对应的评价
     */
    public function reviewVideo(){
        $this->_initClassroomListAdminTitle();
        $this->_initClassroomListAdminMenu();
        $this->pageTab[] = array('title'=>'课程评价列表','tabHash'=>'reviewVideo','url'=>U('classroom/AdminLineClass/reviewVideo',array('id'=>$_GET['id'])));
        $this->pageTitle['reviewVideo'] = '课程评价列表';
        if(!$_GET['id']) $this->error('请选择要查看的评价');
        $field = 'id,uid,oid,review_description,star,review_comment_count';
        $this->pageKeyList = array('id','review_description','uid','oid','star','review_comment_count','DOACTION');
        $map['oid'] = intval($_GET['id']);
        $map['parent_id'] = 0; //父类id为0
        $map['type'] = 3;
        $data = D('ZyReview','classroom')->getListForId($map,20,$field);
        foreach ($data['data'] as $key => $vo){
            $data['data'][$key]['oid'] = D('ZyLineClass','classroom')->getLineclassTitleById($vo['oid']);
            $url = U('classroom/LineClass/view', array('id' => $vo['oid']));
            $data['data'][$key]['oid'] = getQuickLink($url,$data['data'][$key]['oid'],"未知线下课程");
            $data['data'][$key]['uid'] = getUserSpace($vo['uid'], null, '_blank');
            $data['data'][$key]['DOACTION'] = '<a href="'.U('classroom/AdminLineClass/reviewCommentAlbum',array('oid'=>$vo['oid'],'id'=>$vo['id'],'tabHash'=>'reviewCommentAlbum')).'">查看评论</a> | <a href="javascript:void();" onclick="admin.delContent('.$vo['id'].',\'Video\',\'review\')">删除(连带删除回复)</a>';
            $data['data'][$key]['start'] = $vo['start']/ 20;
        }
        $this->displayList($data);
    }

    /**
     * 评价对应的回复
     */
    public function reviewCommentAlbum(){
        if(!$_GET['id']) $this->error('请选择要查看的评论');
        $this->_initClassroomListAdminTitle();
        $this->_initClassroomListAdminMenu();
        $this->pageTab[] = array('title'=>'评论列表','tabHash'=>'reviewCommentAlbum','url'=>U('classroom/AdminLineClass/reviewCommentAlbum',array('oid'=>$_GET['oid'],'id'=>$_GET['id'])));;
        $this->pageButton[] = array('title' => '删除回复', 'onclick' => "admin.delReviewAll('delReview')");
        $this->pageTitle['reviewCommentAlbum'] = '评论列表';
        $field = 'id,uid,oid,review_description';
        $this->pageKeyList = array('id','uid','review_description','oid','DOACTION');
        $map['parent_id'] = intval($_GET['id']); //父类id为问题id
        $map['oid'] = intval($_GET['oid']);
        $map['type'] = 1;
        $data = D('ZyReview','classroom')->getListForId($map,20,$field);
        foreach ($data['data'] as $key => $vo){
            $data['data'][$key]['oid'] = D('ZyVideo','classroom')->getVideoTitleById($vo['oid']);
            $data['data'][$key]['uid'] =getUserSpace($vo['uid'], null, '_blank');
            $data['data'][$key]['DOACTION'] ="<a href=javascript:admin.reviewhuifu(" . $vo['id'] . ",'delreviewhuifu');>删除回复</a>";
        }
        $this->displayList($data);
    }

    //删除问答回复
    public function delreviewhuifu()
    {
        $ids = implode(",", $_POST['ids']);

        $ids = trim(t($ids), ",");
        if ($ids == "") {
            $ids = intval($_POST['ids']);
        }
        $msg = array();
        $where = array(
            'id' => array('in', $ids)
        );
        $res = M('zy_review')->where($where)->delete();
        if ($res !== false) {
            $msg['data'] = "刪除成功！";
            $msg['status'] = 1;
            echo json_encode($msg);
        } else {
            $msg['data'] = "删除失败!";
            echo json_encode($msg);
        }
    }
    //****************************评论结束***********************//
    /**
     * 删除提问、回答、评论
     *
     */
    public function delProperty(){
        if(!$_POST['id']) exit(json_encode(array('status'=>0,'info'=>'错误的参数')));
        if(!$_POST['property'] || !in_array($_POST['property'], array('ask','note','review'))) exit(json_encode(array('status'=>0,'info'=>'参数错误')));
        if($_POST['property'] == 'ask'){
            $result = D('ZyQuestion','classroom')->doDeleteQuestion(intval($_POST['id']));
        }  else if($_POST['property'] == 'note'){
            $result = D('ZyNote','classroom')->doDeleteNote(intval($_POST['id']));
        } else if($_POST['property']){
            $result = D('ZyReview','classroom')->doDeleteReview(intval($_POST['id']));
        }
        if($result['status'] == 1){
            exit(json_encode(array('status'=>1,'info'=>'删除成功')));
        } else {
            exit(json_encode(array('status'=>0,'info'=>'删除失败，请稍后再试')));
        }
    }

    /**
     * 审核课程
     */
    public function crossVideo(){
        if(!$_POST['id']) exit(json_encode(array('status'=>0,'info'=>'错误的参数')));
        $map['course_id'] = intval($_POST['id']);
        $data['is_activity'] = $_POST['cross'] == 'true' ? 1 : -1; //-1为未通过状态
        if(M('zy_teacher_course')->where($map)->data($data)->save()){
            if($data['is_activity'] == -1){
                $uid = M('zy_teacher_course')->where($map)->getField('course_uid');
                $message['title']   = "课程审核被驳回";
                $message['body'] = "您好，您上传的线下课已被机构驳回，请修改信息后重新提交审核。";
                $message['uid']      = $uid;
                $message['ctime'] = time();
                model('Notify')->sendMessage($message);
            }
            exit(json_encode(array('status'=>1,'info'=>'操作成功')));
        } else {
            exit(json_encode(array('status'=>0,'info'=>'操作失败')));
        }
    }


    /**
     * 课程后台管理菜单
     * @return void
     */
    private function _initClassroomListAdminMenu(){
        $this->pageTab[] = array('title'=>'已审','tabHash'=>'index','url'=>U('classroom/AdminLineClass/index'));
        //$this->pageTab[] = array('title'=>'待审核课程列表','tabHash'=>'unauditList','url'=>U('classroom/AdminLineClass/unauditList'));
        $this->pageTab[] = array('title'=>'回收站','tabHash'=>'recycle','url'=>U('classroom/AdminLineClass/recycle'));
        $this->pageTab[] = array('title'=>'添加','tabHash'=>'addVideo','url'=>U('classroom/AdminLineClass/addVideo'));
    }

    /**
     * 课程后台的标题
     */
    private function _initClassroomListAdminTitle(){
        $this->pageTitle['index'] = '已审';
        //$this->pageTitle['unauditList'] = '待审核课程';
        $this->pageTitle['recycle'] 	= '回收站';
        $this->pageTitle['addVideo']    = '添加';
    }


}
